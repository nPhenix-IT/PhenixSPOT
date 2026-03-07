<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SmsPackage;
use App\Models\SmsRechargeTransaction;
use App\Models\SmsTransaction;
use Illuminate\Support\Facades\Auth;

class SmsHistoryController extends Controller
{
    public function index()
    {
        $transactions = SmsTransaction::where('user_id', Auth::id())
            ->latest('id')
            ->paginate(30, ['*'], 'tx_page');

        $recharges = SmsRechargeTransaction::where('user_id', Auth::id())
            ->with('package')
            ->latest('id')
            ->paginate(15, ['*'], 'recharge_page');

        $packages = SmsPackage::query()->where('is_active', true)->orderBy('price_fcfa')->get();
        $moneyfusionPayinPercent = (float) config('fees.moneyfusion_payin_percent', 3);

        return view('content.user.sms-history.index', compact('transactions', 'recharges', 'packages', 'moneyfusionPayinPercent'));
    }
}
