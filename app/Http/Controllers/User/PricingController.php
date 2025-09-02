<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Coupon;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)->get();
        return view('content.user.plans.index', compact('plans'));
    }

    public function payment(Plan $plan, $duration)
    {
        if (!in_array($duration, ['monthly', 'annually'])) {
            abort(404);
        }
        return view('content.user.plans.payment', compact('plan', 'duration'));
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate([
            'coupon_code' => 'required|string|exists:coupons,code',
            'original_price' => 'required|numeric'
        ]);

        $coupon = Coupon::where('code', $data['coupon_code'])->where('is_active', true)->first();

        if (!$coupon) {
            return response()->json(['error' => 'Code promo invalide ou expiré.'], 404);
        }

        $originalPrice = $data['original_price'];
        $discount = 0;

        if ($coupon->type === 'fixed') {
            $discount = $coupon->value;
        } elseif ($coupon->type === 'percent') {
            $discount = ($originalPrice * $coupon->value) / 100;
        }

        $finalPrice = max(0, $originalPrice - $discount);

        return response()->json([
            'success' => 'Code promo appliqué !',
            'discount_amount' => number_format($discount, 0, ',', ' '),
            'final_price' => number_format($finalPrice, 0, ',', ' '),
        ]);
    }
}
