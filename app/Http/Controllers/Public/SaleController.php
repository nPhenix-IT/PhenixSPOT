<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Models\PendingTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function show($slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();
        $profiles = $user->profiles()->get();
        $pageConfigs = ['myLayout' => 'blank'];
        return view('content.public.sale', compact('user', 'profiles', 'pageConfigs'));
    }

    public function purchase(Request $request, $slug)
    {
        $request->validate([
            'profile_id' => 'required|exists:profiles,id',
            'customer_name' => 'required|string',
            'customer_number' => 'required|string',
        ]);

        $user = User::where('slug', $slug)->firstOrFail();
        $profile = Profile::findOrFail($request->profile_id);
        $transactionId = 'TXN-' . strtoupper(Str::random(12));

        PendingTransaction::create([
            'transaction_id' => $transactionId,
            'user_id' => $user->id,
            'profile_id' => $profile->id,
        ]);

        $response = Http::post(config('services.moneyfusion.api_url'), [
            'totalPrice' => $profile->price,
            'article' => [['name' => $profile->name, 'price' => $profile->price]],
            'nomclient' => $request->customer_name,
            'numeroSend' => $request->customer_number,
            'personal_Info' => [['transaction_id' => $transactionId]],
            'return_url' => route('public.payment.callback'),
            'webhook_url' => route('public.payment.webhook'),
        ]);

        if ($response->failed() || !$response->json('statut')) {
            return back()->with('error', 'Le service de paiement est indisponible.');
        }

        PendingTransaction::where('transaction_id', $transactionId)->update(['payment_token' => $response->json('token')]);

        return redirect()->away($response->json('url'));
    }
}