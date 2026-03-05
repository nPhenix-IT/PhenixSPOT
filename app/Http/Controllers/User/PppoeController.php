<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PppoeAccount;
use App\Models\PppoeAuditLog;
use App\Models\PppoeProfile;
use App\Models\Router;
use App\Services\CurrentSubscriptionService;
use App\Services\PlanLimitService;
use App\Services\PppoeAuditService;
use App\Services\PppoeIpPoolService;
use App\Services\PppoeProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class PppoeController extends Controller
{
    public function __construct(
        private readonly CurrentSubscriptionService $currentSubscriptionService,
        private readonly PlanLimitService $planLimitService,
        private readonly PppoeProvisioningService $provisioningService,
        private readonly PppoeAuditService $auditService,
        private readonly PppoeIpPoolService $ipPoolService,
    ) {}

    public function index(Request $request)
    {
        return redirect()->route('user.pppoe.accounts.index');
    }

    public function pools(Request $request)
    {
        $user = $request->user();

        if (!$this->canUsePppoe($user)) {
            return view('content.pppoe.pools', [
                'hasPppoeAccess' => false,
                'profiles' => collect(),
            ]);
        }

        $profiles = PppoeProfile::query()
            ->where('user_id', $user->id)
            ->withCount('accounts')
            ->orderBy('name')
            ->get();

        return view('content.pppoe.pools', [
            'hasPppoeAccess' => true,
            'profiles' => $profiles,
        ]);
    }

    public function profiles(Request $request)
    {
        $user = $request->user();

        if (!$this->canUsePppoe($user)) {
            return view('content.pppoe.profiles', [
                'hasPppoeAccess' => false,
                'profiles' => collect(),
            ]);
        }

        return view('content.pppoe.profiles', [
            'hasPppoeAccess' => true,
            'profiles' => PppoeProfile::where('user_id', $user->id)->orderBy('name')->get(),
        ]);
    }

    public function accounts(Request $request)
    {
        $user = $request->user();

        if (!$this->canUsePppoe($user)) {
            return view('content.pppoe.accounts', [
                'hasPppoeAccess' => false,
                'routers' => collect(),
                'profiles' => collect(),
                'kpis' => [],
                'sessionHistory' => collect(),
                'timeline' => collect(),
                'alarms' => [],
                'kpiByNas' => collect(),
                'kpiByProfile' => collect(),
                'kpiByZone' => collect(),
                'recentAudits' => collect(),
            ]);
        }

        if ($request->ajax() || $request->expectsJson() || $request->has('draw')) {
            $query = PppoeAccount::query()
                ->with(['router:id,name', 'profile:id,name'])
                ->where('user_id', $user->id)
                ->latest('id');

            return DataTables::eloquent($query)
                ->addColumn('router_name', fn (PppoeAccount $a) => $a->router?->name ?? '—')
                ->addColumn('profile_name', fn (PppoeAccount $a) => $a->profile?->name ?? '—')
                ->addColumn('online_badge', function (PppoeAccount $a) {
                    $online = DB::table('radacct')
                        ->whereNull('acctstoptime')
                        ->whereRaw('LOWER(TRIM(username)) = LOWER(TRIM(?))', [$a->username])
                        ->exists();

                    return $online
                        ? '<span class="badge bg-label-success">En ligne</span>'
                        : '<span class="badge bg-label-secondary">Hors ligne</span>';
                })
                ->addColumn('status_badge', function (PppoeAccount $a) {
                    return $a->is_active
                        ? '<span class="badge bg-label-primary">Actif</span>'
                        : '<span class="badge bg-label-danger">Suspendu</span>';
                })
                ->addColumn('actions', fn (PppoeAccount $a) => view('content.pppoe.partials.actions', ['account' => $a])->render())
                ->rawColumns(['online_badge', 'status_badge', 'actions'])
                ->toJson();
        }

        $accounts = PppoeAccount::query()
            ->with(['router:id,name,ip_address,zone,area,city,location,site', 'profile:id,name'])
            ->where('user_id', $user->id)
            ->get();

        return view('content.pppoe.accounts', [
            'hasPppoeAccess' => true,
            'routers' => Router::where('user_id', $user->id)->orderBy('name')->get(['id', 'name']),
            'profiles' => PppoeProfile::where('user_id', $user->id)->orderBy('name')->get(['id', 'name', 'remote_pool']),
            'kpis' => $this->buildKpis($accounts),
            'sessionHistory' => $this->buildSessionHistory($accounts),
            'timeline' => $this->buildTimeline($accounts),
            'alarms' => $this->buildAlarms($accounts),
            'kpiByNas' => $this->buildKpiByNas($accounts),
            'kpiByProfile' => $this->buildKpiByProfile($accounts),
            'kpiByZone' => $this->buildKpiByZone($accounts),
            'recentAudits' => PppoeAuditLog::where('user_id', $user->id)->latest()->limit(20)->get(),
        ]);
    }

    public function storeProfile(Request $request)
    {
        $user = $request->user();
        abort_unless($this->canUsePppoe($user), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'limit_type' => ['required', 'in:both,time,data,unlimited'],
            'rate_limit' => ['nullable', 'string', 'max:255'],
            'session_duration' => ['required_if:limit_type,both,time', 'nullable', 'integer', 'min:1'],
            'session_unit' => ['required_if:limit_type,both,time', 'nullable', 'in:hours,days,weeks,months'],
            'data_limit_value' => ['required_if:limit_type,both,data', 'nullable', 'integer', 'min:1'],
            'data_unit' => ['required_if:limit_type,both,data', 'nullable', 'in:mb,gb'],
            'validity_duration' => ['required', 'integer', 'min:1'],
            'validity_unit' => ['required', 'in:hours,days,weeks,months'],
            'local_address' => ['nullable', 'string', 'max:255'],
            'remote_pool' => ['nullable', 'string', 'max:255'],
            'pool_exclusions' => ['nullable', 'string', 'max:1000'],
            'dns_server' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $sessionTimeout = in_array($data['limit_type'], ['both', 'time'], true)
            ? $this->convertToSeconds((int) $data['session_duration'], $data['session_unit'])
            : 0;
        $dataLimit = in_array($data['limit_type'], ['both', 'data'], true)
            ? $this->convertToBytes((int) $data['data_limit_value'], $data['data_unit'])
            : 0;
        $validityPeriod = $this->convertToSeconds((int) $data['validity_duration'], $data['validity_unit']);

        unset(
            $data['session_duration'],
            $data['session_unit'],
            $data['data_limit_value'],
            $data['data_unit'],
            $data['validity_duration'],
            $data['validity_unit']
        );

        $data['session_timeout'] = $sessionTimeout;
        $data['data_limit'] = $dataLimit;
        $data['validity_period'] = $validityPeriod;

        $profile = PppoeProfile::create(array_merge($data, ['user_id' => $user->id, 'is_active' => true]));
        $this->auditService->log($user->id, 'pppoe.profile.create', 'ok', 'Profil PPPoE créé', ['profile_id' => $profile->id]);

        return back()->with('success', 'Profil PPPoE créé.');
    }

    public function storeAccount(Request $request)
    {
        $user = $request->user();
        abort_unless($this->canUsePppoe($user), 403);

        $data = $request->validate([
            'router_id' => ['nullable', 'integer', 'exists:routers,id'],
            'pppoe_profile_id' => ['nullable', 'integer', 'exists:pppoe_profiles,id'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $profile = !empty($data['pppoe_profile_id'])
            ? PppoeProfile::where('id', $data['pppoe_profile_id'])->where('user_id', $user->id)->first()
            : null;

        if (!empty($data['ip_address']) && $profile && $this->ipPoolService->isIpTaken($profile->id, $data['ip_address'])) {
            return back()->withErrors(['ip_address' => 'Cette IP est déjà utilisée dans ce profil PPP.'])->withInput();
        }

        $account = DB::transaction(function () use ($data, $user, $profile) {
            $account = PppoeAccount::create([
                'user_id' => $user->id,
                'router_id' => $data['router_id'] ?? null,
                'pppoe_profile_id' => $data['pppoe_profile_id'] ?? null,
                'username' => trim($data['username']),
                'password' => $data['password'],
                'ip_address' => $data['ip_address'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
                'status' => 'active',
            ]);

            if (empty($account->ip_address) && $profile) {
                $ip = $this->ipPoolService->allocate($profile, $user->id, $account->id, $user->id);
                if ($ip) {
                    $account->update(['ip_address' => $ip]);
                }
            }

            if (empty($account->expires_at) && (int) ($profile?->validity_period ?? 0) > 0) {
                $account->update(['expires_at' => now()->addSeconds((int) $profile->validity_period)]);
            }

            $nas = $this->provisioningService->sync($account->load(['profile', 'router']));
            $this->auditService->log(
                $user->id,
                'pppoe.account.create',
                $nas['status'] === 'error' ? 'warning' : 'ok',
                'Compte PPPoE créé',
                ['nas' => $nas],
                $account,
                $nas['status'] === 'ok'
            );
            return $account;
        });

        return back()->with('success', "Compte PPPoE {$account->username} créé et provisionné.");
    }

    public function updateAccount(Request $request, PppoeAccount $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'router_id' => ['nullable', 'integer', 'exists:routers,id'],
            'pppoe_profile_id' => ['nullable', 'integer', 'exists:pppoe_profiles,id'],
            'password' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $profile = !empty($data['pppoe_profile_id'])
            ? PppoeProfile::where('id', $data['pppoe_profile_id'])->where('user_id', $request->user()->id)->first()
            : null;

        if (!empty($data['ip_address']) && $profile && $this->ipPoolService->isIpTaken($profile->id, $data['ip_address'], $account->id)) {
            return back()->withErrors(['ip_address' => 'Cette IP est déjà utilisée dans ce profil PPP.'])->withInput();
        }

        $payload = [
            'router_id' => $data['router_id'] ?? null,
            'pppoe_profile_id' => $data['pppoe_profile_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        DB::transaction(function () use ($account, $payload, $profile, $request) {
            $oldProfile = $account->pppoe_profile_id;
            $oldIp = $account->ip_address;

            $account->update($payload);
            if (empty($account->ip_address) && $profile) {
                $ip = $this->ipPoolService->allocate($profile, $account->user_id, $account->id, $request->user()->id);
                if ($ip) {
                    $account->update(['ip_address' => $ip]);
                }
            }

            if ($oldProfile !== $account->pppoe_profile_id || ($oldIp && $oldIp !== $account->ip_address)) {
                $this->ipPoolService->release($oldProfile, $oldIp);
            }

            $nas = $this->provisioningService->sync($account->fresh()->load(['profile', 'router']));
            $this->auditService->log(
                $account->user_id,
                'pppoe.account.update',
                $nas['status'] === 'error' ? 'warning' : 'ok',
                'Compte PPPoE mis à jour',
                ['nas' => $nas],
                $account,
                $nas['status'] === 'ok'
            );
        });

        return back()->with('success', 'Compte PPPoE mis à jour.');
    }

    public function toggleAccount(Request $request, PppoeAccount $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        $account->update([
            'is_active' => !$account->is_active,
            'status' => $account->is_active ? 'suspended' : 'active',
        ]);

        $nas = $this->provisioningService->sync($account->fresh()->load(['profile', 'router']));
        $this->auditService->log(
            $account->user_id,
            'pppoe.account.toggle',
            $nas['status'] === 'error' ? 'warning' : 'ok',
            'Statut PPPoE mis à jour',
            ['is_active' => $account->is_active, 'nas' => $nas],
            $account,
            $nas['status'] === 'ok'
        );

        return back()->with('success', 'Statut PPPoE mis à jour.');
    }

    public function destroyAccount(Request $request, PppoeAccount $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($account) {
            $this->provisioningService->delete($account->load(['profile', 'router']));
            $this->ipPoolService->release($account->pppoe_profile_id, $account->ip_address);
            $this->auditService->log($account->user_id, 'pppoe.account.delete', 'ok', 'Compte PPPoE supprimé', [], $account);
            $account->delete();
        });

        return back()->with('success', 'Compte PPPoE supprimé.');
    }

    private function canUsePppoe($user): bool
    {
        if ($user->hasRole(['Super-admin', 'Admin'])) {
            return true;
        }

        if (!$this->planLimitService->hasActiveSubscription($user)) {
            return false;
        }

        $plan = $this->currentSubscriptionService->activePlanFor($user);
        $features = is_array($plan?->features) ? $plan->features : [];

        return (bool) ($features['pppoe'] ?? false);
    }

    private function buildKpis($accounts): array
    {
        $usernames = $accounts->pluck('username')->filter()->values();
        $online = $usernames->isEmpty() ? 0 : DB::table('radacct')
            ->whereNull('acctstoptime')
            ->whereIn('username', $usernames)
            ->distinct('username')
            ->count('username');

        $flapping = $usernames->isEmpty() ? 0 : DB::table('radacct')
            ->whereIn('username', $usernames)
            ->where('acctstarttime', '>=', now()->subHour())
            ->groupBy('username')
            ->havingRaw('COUNT(*) >= 3')
            ->get()
            ->count();

        return [
            'accounts_total' => $accounts->count(),
            'accounts_active' => $accounts->where('is_active', true)->count(),
            'accounts_online' => $online,
            'alerts_flapping' => $flapping,
        ];
    }

    private function buildSessionHistory($accounts)
    {
        $usernames = $accounts->pluck('username')->filter()->values();
        if ($usernames->isEmpty()) {
            return collect();
        }

        return DB::table('radacct')
            ->whereIn('username', $usernames)
            ->orderByDesc('radacctid')
            ->limit(20)
            ->get([
                'username',
                'nasipaddress',
                'acctstarttime',
                'acctstoptime',
                'acctsessiontime',
                'acctinputoctets',
                'acctoutputoctets',
                'acctterminatecause',
            ]);
    }

    private function buildTimeline($accounts)
    {
        $rows = $this->buildSessionHistory($accounts);

        return $rows->flatMap(function ($row) {
            $events = [];
            if (!empty($row->acctstarttime)) {
                $events[] = (object) [
                    'username' => $row->username,
                    'event' => 'connect',
                    'at' => $row->acctstarttime,
                    'details' => 'Connexion NAS ' . ($row->nasipaddress ?? 'N/A'),
                ];
            }
            if (!empty($row->acctstoptime)) {
                $events[] = (object) [
                    'username' => $row->username,
                    'event' => 'disconnect',
                    'at' => $row->acctstoptime,
                    'details' => 'Déconnexion (' . ($row->acctterminatecause ?? 'n/a') . ')',
                ];
            }
            return $events;
        })->sortByDesc('at')->take(20)->values();
    }

    private function buildAlarms($accounts): array
    {
        $usernames = $accounts->pluck('username')->filter()->values();
        if ($usernames->isEmpty()) {
            return ['flapping' => collect(), 'short_sessions' => collect(), 'auth_failures' => collect()];
        }

        $flapping = DB::table('radacct')
            ->whereIn('username', $usernames)
            ->where('acctstarttime', '>=', now()->subHour())
            ->selectRaw('username, COUNT(*) as cnt')
            ->groupBy('username')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('cnt')
            ->get();

        $short = DB::table('radacct')
            ->whereIn('username', $usernames)
            ->where('acctstarttime', '>=', now()->subDay())
            ->whereNotNull('acctstoptime')
            ->where('acctsessiontime', '<', 120)
            ->orderByDesc('radacctid')
            ->limit(10)
            ->get(['username', 'acctsessiontime', 'acctterminatecause', 'acctstoptime']);

        $authFailures = collect();
        if (Schema::hasTable('radpostauth')) {
            $authFailures = DB::table('radpostauth')
                ->whereIn('username', $usernames)
                ->where('authdate', '>=', now()->subDay())
                ->whereRaw('LOWER(reply) like ?', ['%reject%'])
                ->orderByDesc('authdate')
                ->limit(10)
                ->get(['username', 'reply', 'authdate']);
        }

        return [
            'flapping' => $flapping,
            'short_sessions' => $short,
            'auth_failures' => $authFailures,
        ];
    }

    private function buildKpiByNas($accounts)
    {
        return $accounts
            ->groupBy(fn ($a) => $a->router?->name ?? 'NAS non assigné')
            ->map(fn ($rows, $nas) => (object) [
                'label' => $nas,
                'accounts' => $rows->count(),
                'active' => $rows->where('is_active', true)->count(),
            ])
            ->values();
    }

    private function buildKpiByProfile($accounts)
    {
        return $accounts
            ->groupBy(fn ($a) => $a->profile?->name ?? 'Profil non assigné')
            ->map(fn ($rows, $profile) => (object) [
                'label' => $profile,
                'accounts' => $rows->count(),
                'active' => $rows->where('is_active', true)->count(),
            ])
            ->values();
    }

    private function buildKpiByZone($accounts)
    {
        $zoneField = collect(['zone', 'area', 'city', 'location', 'site'])
            ->first(fn ($column) => Schema::hasColumn('routers', $column));

        return $accounts
            ->groupBy(function ($a) use ($zoneField) {
                if (!$zoneField || !$a->router) {
                    return 'Zone non définie';
                }
                return $a->router->{$zoneField} ?: 'Zone non définie';
            })
            ->map(fn ($rows, $zone) => (object) [
                'label' => $zone,
                'accounts' => $rows->count(),
            ])
            ->values();
    }

    private function convertToSeconds(int $duration, string $unit): int
    {
        return match ($unit) {
            'hours' => $duration * 3600,
            'days' => $duration * 86400,
            'weeks' => $duration * 604800,
            'months' => $duration * 2592000,
            default => 0,
        };
    }

    private function convertToBytes(int $limit, string $unit): int
    {
        return $unit === 'mb'
            ? $limit * 1024 * 1024
            : $limit * 1024 * 1024 * 1024;
    }
}
