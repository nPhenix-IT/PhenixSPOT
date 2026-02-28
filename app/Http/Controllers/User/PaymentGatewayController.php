<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        // $user = Auth::user();
        // $gateways = $user->paymentGateways->keyBy('provider_name');
        // $supportedGateways = ['cinetpay', 'paymetrust', 'moneyfusion', 'paypal'];
        // return view('content.gateways.index', compact('gateways', 'supportedGateways'));
        $user = Auth::user();
        $gateways = $user->paymentGateways->keyBy('provider_name');
        // On retire moneyfusion de la liste configurable par l'utilisateur
        $supportedGateways = ['cinetpay', 'paymetrust', 'paypal']; 
        return view('content.gateways.index', compact('gateways', 'supportedGateways'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'provider_name' => 'required|string',
            'api_key' => 'required|string',
            'secret_key' => 'required|string',
        ]);

        $user->paymentGateways()->updateOrCreate(
            ['provider_name' => $data['provider_name']],
            ['api_key' => $data['api_key'], 'secret_key' => $data['secret_key'], 'is_active' => true]
        );

        return redirect()->back()->with('success', 'Vos clés API pour ' . ucfirst($data['provider_name']) . ' ont été enregistrées.');
    }
}