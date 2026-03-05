<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Coupon::with(['usages', 'user:id,name,email', 'plan:id,name'])->latest();
            return DataTables::of($data)
                ->addColumn('select', fn($row) => '<input type="checkbox" class="form-check-input coupon-select" value="'.$row->id.'">')
                ->addColumn('value_formatted', function($row) {
                    if ($row->type === 'percent') {
                        return $row->value . '%';
                    }
                    return number_format($row->value, 0, ',', ' ') . ' FCFA';
                })
                ->addColumn('status', fn($row) => $row->is_active ? '<span class="badge bg-label-success">Actif</span>' : '<span class="badge bg-label-secondary">Inactif</span>')
                ->addColumn('validity', function ($row) {
                    $from = $row->starts_at ? $row->starts_at->format('d/m/Y H:i') : 'Immédiate';
                    $to = $row->ends_at ? $row->ends_at->format('d/m/Y H:i') : 'Sans fin';
                    return "{$from} → {$to}";
                })
                ->addColumn('scope', function ($row) {
                    $user = $row->user?->email ?? 'Tous les utilisateurs';
                    $plan = $row->plan?->name ?? 'Tous les plans';
                    return "{$user}<br><small class='text-muted'>{$plan}</small>";
                })
                ->addColumn('usage', fn ($row) => $row->usages->count() . ' usage(s)')
                ->addColumn('action', function($row){
                    return '
                        <div class="d-inline-block">
                            <a href="javascript:;" class="btn btn-md text-primary btn-icon item-edit" data-id="'.$row->id.'"><i class="icon-base ti tabler-edit"></i></a>
                            <a href="javascript:;" class="btn btn-md text-danger btn-icon item-delete" data-id="'.$row->id.'"><i class="icon-base ti tabler-trash"></i></a>
                        </div>';
                })
                ->rawColumns(['select', 'status', 'scope', 'action'])
                ->make(true);
        }

        return view('content.admin.coupons.index', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'plans' => Plan::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCoupon($request);

        $generateCount = (int) ($validated['generate_count'] ?? 1);
        $prefix = trim((string) ($validated['prefix'] ?? ''));
        $created = collect();

        if ($generateCount > 1 || !empty($validated['auto_generate'])) {
            for ($i = 0; $i < max(1, $generateCount); $i++) {
                $code = $this->generateCode($prefix);
                $created->push(Coupon::create($this->couponPayload($validated, $code)));
            }
            return response()->json(['success' => $created->count() . ' bons de réduction générés avec succès.']);
        }

        Coupon::create($this->couponPayload($validated, (string) $validated['code']));
        return response()->json(['success' => 'Bon de réduction créé avec succès.']);
    }

    public function edit(Coupon $coupon)
    {
        return response()->json($coupon);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $this->validateCoupon($request, $coupon->id);
        $coupon->update($this->couponPayload($validated, (string) $validated['code']));
        return response()->json(['success' => 'Bon de réduction mis à jour avec succès.']);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(['success' => 'Bon de réduction supprimé avec succès.']);
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:coupons,id',
        ]);

        $deleted = Coupon::whereIn('id', $data['ids'])->delete();

        return response()->json([
            'success' => $deleted . ' bon(s) supprimé(s) avec succès.',
        ]);
    }

    private function validateCoupon(Request $request, $couponId = null)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:255|unique:coupons,code,' . $couponId,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'user_id' => 'nullable|integer|exists:users,id',
            'plan_id' => 'nullable|integer|exists:plans,id',
            'auto_generate' => 'nullable|boolean',
            'prefix' => 'nullable|string|max:20',
            'generate_count' => 'nullable|integer|min:1|max:500',
        ]);

        $autoGenerate = (bool) ($validated['auto_generate'] ?? false);
        $generateCount = (int) ($validated['generate_count'] ?? 1);
        if (!$autoGenerate && $generateCount <= 1 && empty($validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Le code est obligatoire quand la génération automatique n’est pas utilisée.',
            ]);
        }

        $validated['is_active'] = $request->has('is_active');
        return $validated;
    }

    private function couponPayload(array $validated, string $code): array
    {
        return [
            'code' => $code,
            'type' => $validated['type'],
            'value' => $validated['value'],
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'plan_id' => $validated['plan_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function generateCode(string $prefix = ''): string
    {
        $prefix = Str::upper(trim($prefix));
        do {
            $candidate = $prefix . Str::upper(Str::random(5));
        } while (Coupon::where('code', $candidate)->exists());

        return $candidate;
    }
}