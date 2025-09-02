<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Coupon::latest();
            return DataTables::of($data)
                ->addColumn('value_formatted', function($row) {
                    if ($row->type === 'percent') {
                        return $row->value . '%';
                    }
                    return number_format($row->value, 0, ',', ' ') . ' FCFA';
                })
                ->addColumn('status', fn($row) => $row->is_active ? '<span class="badge bg-label-success">Actif</span>' : '<span class="badge bg-label-secondary">Inactif</span>')
                ->addColumn('action', function($row){
                    return '
                        <div class="d-inline-block">
                            <a href="javascript:;" class="btn btn-md text-primary btn-icon item-edit" data-id="'.$row->id.'"><i class="icon-base ti tabler-edit"></i></a>
                            <a href="javascript:;" class="btn btn-md text-danger btn-icon item-delete" data-id="'.$row->id.'"><i class="icon-base ti tabler-trash"></i></a>
                        </div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('content.admin.coupons.index');
    }

    public function store(Request $request)
    {
        $validated = $this->validateCoupon($request);
        Coupon::create($validated);
        return response()->json(['success' => 'Bon de réduction créé avec succès.']);
    }

    public function edit(Coupon $coupon)
    {
        return response()->json($coupon);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $this->validateCoupon($request, $coupon->id);
        $coupon->update($validated);
        return response()->json(['success' => 'Bon de réduction mis à jour avec succès.']);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(['success' => 'Bon de réduction supprimé avec succès.']);
    }

    private function validateCoupon(Request $request, $couponId = null)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:coupons,code,' . $couponId,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
        ]);
        $validated['is_active'] = $request->has('is_active');
        return $validated;
    }
}
