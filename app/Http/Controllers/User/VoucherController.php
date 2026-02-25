<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Voucher;
use App\Models\Template;
use App\Models\Radcheck;
use App\Models\Radusergroup;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use tbQuar\Facades\Quar;

class VoucherController extends Controller
{
    private function isUnlimitedValue($value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['-1', 'illimite', 'illimité', 'unlimited', 'infini', 'infinite', '∞'], true);
    }

    private function normalizeLimit($value): int
    {
        if ($this->isUnlimitedValue($value)) {
            return PHP_INT_MAX;
        }

        if (is_numeric($value)) {
            return max(0, (int) $value);
        }

        return 0;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if ($request->ajax()) {
            $query = Voucher::where('user_id', $user->id)->with('profile');

            if ($request->has('profile_id') && $request->profile_id != '') {
                $query->where('profile_id', $request->profile_id);
            }

            return DataTables::of($query)
                ->addColumn('profile_name', fn($row) => $row->profile->name ?? 'N/A')
                ->addColumn('status', function($row) {
                    $colors = ['new' => 'success', 'used' => 'info', 'expired' => 'warning', 'disabled' => 'secondary'];
                    $color = $colors[$row->status] ?? 'secondary';
                    return '<span class="badge bg-label-'.$color.'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function($row){
                    return '<button class="btn btn-md text-danger btn-icon item-delete" data-id="'.$row->id.'"><i class="icon-base ti tabler-trash"></i></button>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $profiles = $user->profiles()->get();
        $hasActiveSubscription = $user->hasRole(['Super-admin', 'Admin']) || ($user->subscription && $user->subscription->isActive());
        
        $planFeatures = $hasActiveSubscription ? ($user->hasRole(['Super-admin', 'Admin']) ? ['active_users' => PHP_INT_MAX] : ($user->subscription->plan->features ?? [])) : [];
        $vouchersCount = $user->vouchers()->count();
        $limit = $this->normalizeLimit($planFeatures['active_users'] ?? ($planFeatures['vouchers'] ?? 0));
        $isUnlimitedLimit = $limit === PHP_INT_MAX;
        $limitLabel = $isUnlimitedLimit ? 'Illimité' : number_format($limit, 0, ',', ' ');
        $usagePercent = (!$isUnlimitedLimit && $limit > 0)
            ? min(100, ($vouchersCount / $limit) * 100)
            : 0;

        return view('content.vouchers.index', compact('profiles', 'hasActiveSubscription', 'vouchersCount', 'limit', 'limitLabel', 'usagePercent', 'isUnlimitedLimit'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'profile_id' => 'required|exists:profiles,id',
            'quantity' => 'required|integer|min:1|max:500',
            'length' => 'required|integer|in:4,6,8,10',
            'charset' => 'required|string|in:ABC,abc,A1B2C,a1b2c',
        ]);

        $user = Auth::user();
        $hasActiveSubscription = $user->hasRole(['Super-admin', 'Admin']) || ($user->subscription && $user->subscription->isActive());
        if (!$hasActiveSubscription) {
            return response()->json(['error' => 'Abonnement inactif.'], 403);
        }

        $planFeatures = $user->hasRole(['Super-admin', 'Admin'])
            ? ['active_users' => PHP_INT_MAX]
            : ($user->subscription->plan->features ?? []);

        $limit = $this->normalizeLimit($planFeatures['active_users'] ?? ($planFeatures['vouchers'] ?? 0));
        $vouchersCount = $user->vouchers()->count();

        if ($limit !== PHP_INT_MAX) {
            if ($limit <= 0) {
                return response()->json(['error' => 'Votre plan ne permet pas de générer des vouchers.'], 403);
            }

            if (($vouchersCount + (int) $data['quantity']) > $limit) {
                $remaining = max(0, $limit - $vouchersCount);
                return response()->json(['error' => "Limite de vouchers atteinte. Il vous reste {$remaining} voucher(s) disponible(s)."], 403);
            }
        }
        $profile = Profile::where('id', $data['profile_id'])->where('user_id', $user->id)->firstOrFail();

        DB::transaction(function () use ($data, $user, $profile) {
            for ($i = 0; $i < $data['quantity']; $i++) {
                $generatedCode = $this->createVoucherWithRetry(
                    $user->id,
                    $profile->id,
                    $profile->name,
                    (int) $data['length'],
                    $data['charset']
                );

                if ($generatedCode === null) {
                    throw new \RuntimeException('Impossible de générer un code voucher unique après plusieurs tentatives.');
                }
            }
        });

        return response()->json(['success' => $data['quantity'] . ' voucher(s) généré(s) avec succès !']);
    }

    public function destroy(Voucher $voucher)
    {
        if ($voucher->user_id !== Auth::id()) { return response()->json(['error' => 'Non autorisé'], 403); }
        DB::transaction(function () use ($voucher) {
            Radcheck::where('username', $voucher->code)->delete();
            Radusergroup::where('username', $voucher->code)->delete();
            $voucher->delete();
        });
        return response()->json(['success' => 'Voucher supprimé.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $vouchers = Voucher::where('user_id', Auth::id())->whereIn('id', $request->ids)->get();
        
        DB::transaction(function () use ($vouchers) {
            $codes = $vouchers->pluck('code');
            Radcheck::whereIn('username', $codes)->delete();
            Radusergroup::whereIn('username', $codes)->delete();
            Voucher::whereIn('id', $vouchers->pluck('id'))->delete();
        });

        return response()->json(['success' => count($vouchers) . ' vouchers supprimés.']);
    }

    public function printByProfile(Request $request)
    {
        $request->validate(['profile_id' => 'required|exists:profiles,id']);
        $vouchers = Voucher::where('user_id', Auth::id())->where('profile_id', $request->profile_id)->where('status', 'new')->with('profile')->get();
        return $this->generatePrintView($vouchers);
    }

    public function print(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $vouchers = Voucher::where('user_id', Auth::id())->whereIn('id', $request->ids)->where('status', 'new')->with('profile')->get();
        return $this->generatePrintView($vouchers);
    }

    private function generatePrintView($vouchers)
    {
        $user = Auth::user();
        $templateContent = optional($user->template)->content ?? file_get_contents(resource_path('views/content/vouchers/_qrcode_template.blade.php'));
        $dnsName = $user?->salePageSetting?->login_dns; 

        $output = '';
        foreach ($vouchers as $voucher) {
            $loginUrl = "http://" . $dnsName . "/login?username=" . $voucher->code . '&password=' .$voucher->code;
            $qrCodeSvg = Quar::eye('rounded')->size(80)->gradient(20, 192, 241 , 164, 29, 52 , 'radial')->generate($loginUrl);

            $voucherHtml = $templateContent;
            $voucherHtml = str_replace('@{{ code }}', $voucher->code, $voucherHtml);
            $voucherHtml = str_replace('@{{ profile_name }}', $voucher->profile->name, $voucherHtml);
            $voucherHtml = str_replace('@{{ price }}', $voucher->profile->price == 0 ? 'Gratuit' : number_format($voucher->profile->price, 0, ',', ' ') . ' FCFA', $voucherHtml);
            $voucherHtml = str_replace('@{{ validity }}', $this->formatSeconds($voucher->profile->validity_period), $voucherHtml);
            $voucherHtml = str_replace('@{{ data_limit }}', $this->formatBytes($voucher->profile->data_limit), $voucherHtml);
            $voucherHtml = str_replace('@{{ contact }}', $user->phone_number ?? 'N/A', $voucherHtml);
            $voucherHtml = str_replace('@{{ qrcode }}', $qrCodeSvg, $voucherHtml);
            $output .= $voucherHtml;
        }

        return response()->json(['html' => $output]);
    }

    public function getTemplate()
    {
        $content = optional(Auth::user()->template)->content ?? file_get_contents(resource_path('views/content/vouchers/_qrcode_template.blade.php'));
        return response()->json(['template' => $content]);
    }

    public function saveTemplate(Request $request)
    {
        $request->validate(['template' => 'required|string']);
        Auth::user()->template()->updateOrCreate(
            ['user_id' => Auth::user()->id],
            ['content' => $request->template]
        );
        return response()->json(['success' => 'Template sauvegardé avec succès.']);
    }

    private function createVoucherWithRetry(int $userId, int $profileId, string $profileName, int $length, string $charset): ?string
    {
        $maxAttempts = 20;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = $this->generateUniqueCode($length, $charset);

            try {
                Voucher::create([
                    'user_id' => $userId,
                    'profile_id' => $profileId,
                    'code' => $code,
                    'source' => 'manual_generation',
                ]);

                Radcheck::create(['username' => $code, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $code]);
                Radusergroup::create(['username' => $code, 'groupname' => $profileName]);

                return $code;
            } catch (QueryException $exception) {
                if (!$this->isDuplicateKeyException($exception)) {
                    throw $exception;
                }
            }
        }

        return null;
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000'
            || str_contains(strtolower($exception->getMessage()), 'duplicate entry');
    }

    private function generateUniqueCode(int $length, string $charset): string
    {
        $characters = '';
        switch ($charset) {
            case 'ABC': $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; break;
            case 'abc': $characters = 'abcdefghjklmnpqrstuvwxyz'; break;
            case 'A1B2C': $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; break;
            case 'a1b2c': $characters = 'abcdefghijklmnpqrstuvwxyz23456789'; break;
        }
        
        $charactersLength = strlen($characters);
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $code;
    }

    private function formatSeconds($seconds) {
        if (!$seconds) return 'N/A';
        if ($seconds >= 2592000) { $value = round($seconds / 2592000); $unit = $value > 1 ? 'Mois' : 'Mois'; }
        elseif ($seconds >= 604800) { $value = round($seconds / 604800); $unit = $value > 1 ? 'Semaines' : 'Semaine'; }
        elseif ($seconds >= 86400) { $value = round($seconds / 86400); $unit = $value > 1 ? 'Jours' : 'Jour'; }
        elseif ($seconds >= 3600) { $value = round($seconds / 3600); $unit = $value > 1 ? 'Heures' : 'Heure'; }
        else { $value = round($seconds / 1800); $unit = $value > 1 ? 'Minutes' : 'Minute'; }
        return "$value $unit";
    }

    private function formatBytes($bytes) {
        if (!$bytes) return 'Illimitées';
        $gb = $bytes / (1024*1024*1024);
        if ($gb >= 1) return round($gb, 2) . ' Go';
        $mb = $bytes / (1024*1024);
        return round($mb, 2) . ' Mo';
    }
}