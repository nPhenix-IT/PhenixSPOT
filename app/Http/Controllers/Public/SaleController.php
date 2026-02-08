<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Models\PendingTransaction;
use App\Services\MoneyFusionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function show($slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();
        $profiles = $user->profiles()->get();
        $settings = $user->salePageSetting;
        $commissionPercent = $settings->commission_percent ?? config('fees.sales_commission_percent');
        $pageConfigs = ['myLayout' => 'blank'];
        return view('content.public.sale', compact('user', 'profiles', 'pageConfigs', 'settings', 'commissionPercent'));
    }

    public function purchase(Request $request, $slug)
    {
        $request->validate([
            'profile_id' => 'required|exists:profiles,id',
            'customer_name' => 'required|string',
            'customer_number' => 'required|string',
            'login_url' => 'nullable|url',
            'router_id' => 'nullable|exists:routers,id',
        ]);
    
        $user = User::where('slug', $slug)->firstOrFail();
        $profile = Profile::findOrFail($request->profile_id);
        $transactionId = 'TXN-' . strtoupper(Str::random(12));
        $settings = $user->salePageSetting;
        $commissionPercent = $settings->commission_percent ?? config('fees.sales_commission_percent');
        $commissionPayer = $settings->commission_payer ?? 'seller';
        $commissionAmount = round(($profile->price * $commissionPercent) / 100, 2);
        $totalPrice = $commissionPayer === 'client'
            ? $profile->price + $commissionAmount
            : $profile->price;
<<<<<<< HEAD

=======
    
        $loginUrl = $request->input('login_url');
    
>>>>>>> master
        PendingTransaction::create([
            'transaction_id' => $transactionId,
            'user_id' => $user->id,
            'router_id' => $request->input('router_id'),
            'profile_id' => $profile->id,
            'customer_name' => $request->customer_name,
            'customer_number' => $request->customer_number,
<<<<<<< HEAD
=======
            'login_url' => $loginUrl,
>>>>>>> master
            'commission_payer' => $commissionPayer,
            'commission_amount' => $commissionAmount,
            'total_price' => $totalPrice,
        ]);
<<<<<<< HEAD

        $response = Http::post(config('services.moneyfusion.api_url'), [
            'totalPrice' => $totalPrice,
            'article' => [['name' => $profile->name, 'price' => $totalPrice]],
            'nomclient' => $request->customer_name,
            'numeroSend' => $request->customer_number,
            'personal_Info' => [['transaction_id' => $transactionId]],
            'return_url' => route('public.payment.callback'),
            'webhook_url' => route('public.payment.webhook'),
        ]);

        if ($response->failed() || !$response->json('statut')) {
=======
        
        $moneyFusion = app(MoneyFusionService::class);
        try {
            $paymentData = $moneyFusion->initiatePayment(
                $user,
                $profile,
                $totalPrice,
                $transactionId,
                route('public.payment.callback', ['transaction_id' => $transactionId]),
                route('public.payment.webhook'),
                $request->customer_name,
                $request->customer_number
            );
        } catch (\Exception $exception) {
>>>>>>> master
            return back()->with('error', 'Le service de paiement est indisponible.');
        }

        PendingTransaction::where('transaction_id', $transactionId)->update(['payment_token' => $paymentData['token'] ?? null]);

        return redirect()->away($paymentData['url']);
    }
}
