<?php

namespace App\Actions\Fortify;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\File;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
     
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', 'max:30'],
            'country_code' => ['nullable', 'string', 'max:8'], // backend override
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();
    
        // Nettoyage phone
        $phone = preg_replace('/\s+/', '', (string) $input['phone_number']);
    
        // ----------- DETECTION GEOIP OFFICIELLE MAXMIND -----------
        $countryCode = 'CI'; // fallback
    
        try {
            $databasePath = storage_path('app/geoip/GeoLite2-Country.mmdb');
    
            if (File::exists($databasePath)) {
                $reader = new Reader($databasePath);
    
                $ip = request()->ip();
    
                // Si local/dev
                if ($ip === '127.0.0.1' || $ip === '::1') {
                    $ip = '8.8.8.8'; // IP de test
                }
    
                $record = $reader->country($ip);
    
                $isoCode = strtoupper($record->country->isoCode ?? '');
    
                if (strlen($isoCode) === 2) {
                    $countryCode = $isoCode;
                }
    
                $reader->close();
            }
        } catch (\Throwable $e) {
            // Si erreur -> fallback CI
            $countryCode = 'CI';
        }
    
        // -----------------------------------------------------------
    
        return DB::transaction(function () use ($input, $countryCode, $phone) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone_number' => $phone,
                'country_code' => $countryCode, // ISO2 fiable
                'password' => Hash::make($input['password']),
                'slug' => $this->generateSlug($input['name']),
            ]), function (User $user) {
                $this->createTeam($user);
                $user->assignRole('User');
                Wallet::create(['user_id' => $user->id]);
                $this->assignTrialSubscription($user);
            });
        });
    }

    /**
     * Generate slug: 4 first characters of name + 3 random alphanumeric characters
     * Example: johnA7K
     */
    protected function assignTrialSubscription(User $user): void
    {
        if ($user->trial_used_at) {
            return;
        }

        $trialPlan = Plan::query()
            ->where('is_active', true)
            ->where('trial_enabled', true)
            ->whereIn('trial_days', [7, 14])
            ->orderBy('price_monthly')
            ->first();

        if (!$trialPlan) {
            return;
        }

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $trialPlan->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays((int) $trialPlan->trial_days),
            'status' => 'active',
        ]);

        $user->forceFill(['trial_used_at' => now()])->save();
    }

    protected function generateSlug(string $name): string
    {
        // Nettoyer le nom (enlever espaces et caractères spéciaux)
        $cleanName = Str::slug($name, '');
        // Prendre les 4 premiers caractères (ou moins si nom court)
        $prefix = Str::lower(Str::substr($cleanName, 0, 4));
        // Sécurité si nom < 4 caractères
        if (strlen($prefix) < 4) {
            $prefix = str_pad($prefix, 4, 'x');
        }

        do {
            // Générer 3 caractères alphanumériques aléatoires
            $random = Str::upper(Str::random(3));
            $slug = $prefix . $random;
        } while (User::where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0] . "'s Team",
            'personal_team' => true,
        ]));
    }
}
