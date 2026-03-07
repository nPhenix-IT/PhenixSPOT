<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\VoucherLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserManagementController extends Controller
{
    public function __construct(private readonly VoucherLifecycleService $voucherLifecycleService)
    {
    }

    public function index(Request $request)
    {
        if ($request->ajax() || $request->expectsJson() || $request->has('draw')) {
            $query = User::query()->with(['roles', 'subscription.plan'])->latest('id');

            return DataTables::eloquent($query)
                ->addColumn('role_names', fn (User $user) => $user->roles->pluck('name')->join(', '))
                ->addColumn('plan_name', fn (User $user) => optional(optional($user->subscription)->plan)->name ?? '—')
                ->addColumn('status_badge', function (User $user) {
                    $class = $user->is_active ? 'bg-label-success' : 'bg-label-secondary';
                    $label = $user->is_active ? 'Actif' : 'Désactivé';

                    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
                })
                ->addColumn('actions', fn (User $user) => view('content.admin.users.partials.actions', compact('user'))->render())
                ->rawColumns(['status_badge', 'actions'])
                ->toJson();
        }

        $roles = Role::query()->orderBy('name')->get();
        $plans = Plan::query()->where('is_active', true)->orderBy('price_monthly')->get();

        $countries = [
            'CI' => 'Côte d’Ivoire',
            'SN' => 'Sénégal',
            'ML' => 'Mali',
            'BF' => 'Burkina Faso',
            'TG' => 'Togo',
            'BJ' => 'Bénin',
            'CM' => 'Cameroun',
            'FR' => 'France',
            'US' => 'États-Unis',
        ];

        return view('content.admin.users.index', compact('roles', 'plans', 'countries'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'country_code' => strtoupper((string) ($data['country_code'] ?? 'CI')),
                'phone_number' => $data['phone_number'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            $user->syncRoles([$data['role']]);

            if (!empty($data['plan_id'])) {
                $this->createSubscriptionForPlan($user, (int) $data['plan_id']);
            }
        });

        return $request->ajax()
            ? response()->json(['message' => 'Utilisateur créé avec succès.'])
            : back()->with('success', 'Utilisateur créé avec succès.');
    }

    public function update(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'country_code' => ['nullable', 'string', 'size:2'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
        ]);

        DB::transaction(function () use ($data, $user) {
            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'country_code' => strtoupper((string) ($data['country_code'] ?? $user->country_code ?? 'CI')),
                'phone_number' => $data['phone_number'] ?? null,
            ];

            if (!empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $user->update($payload);
            $user->syncRoles([$data['role']]);

            if (!empty($data['plan_id'])) {
                $this->createSubscriptionForPlan($user, (int) $data['plan_id']);
            }
        });

        return $request->ajax()
            ? response()->json(['message' => 'Utilisateur modifié avec succès.'])
            : back()->with('success', 'Utilisateur modifié avec succès.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ($user->id === Auth::id()) {
            return $request->ajax()
                ? response()->json(['message' => 'Vous ne pouvez pas désactiver votre propre compte.'], 422)
                : back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return $request->ajax()
            ? response()->json(['message' => 'Statut utilisateur mis à jour.'])
            : back()->with('success', 'Statut utilisateur mis à jour.');
    }

    public function assignPlan(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'duration' => ['nullable', 'in:monthly,annually'],
        ]);

        $duration = $data['duration'] ?? 'monthly';
        $days = $duration === 'annually' ? 365 : 30;

        DB::transaction(function () use ($user, $data, $days) {
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => (int) $data['plan_id'],
                'starts_at' => now(),
                'ends_at' => now()->addDays($days),
                'status' => 'active',
            ]);
        });
        $this->voucherLifecycleService->syncActivationForUser((int) $user->id);

        return $request->ajax()
            ? response()->json(['message' => 'Plan assigné avec succès.'])
            : back()->with('success', 'Plan assigné avec succès.');
    }

    public function impersonate(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Vous êtes déjà connecté sur ce compte.');
        }

        session([
            'impersonator_id' => Auth::id(),
            'impersonated_user_name' => $user->name,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Mode Login as activé.');
    }

    public function leaveImpersonation(): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');
        if (!$impersonatorId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($impersonatorId);
        session()->forget(['impersonator_id', 'impersonated_user_name']);

        if (!$admin) {
            Auth::logout();
            return redirect()->route('login');
        }

        Auth::login($admin);

        return redirect()->route('admin.users.index')->with('success', 'Retour au compte Super-admin.');
    }

    private function createSubscriptionForPlan(User $user, int $planId): void
    {
        Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $planId,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
        ]);
    
    $this->voucherLifecycleService->syncActivationForUser((int) $user->id);
    }
}
