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
            'country_code' => ['required', 'string', 'max:8'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone_number' => $input['phone_number'],
                'country_code' => $input['country_code'],
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
