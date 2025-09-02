<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::find($request->plan_id);
        $user = Auth::user();

        // Annuler l'ancien abonnement s'il existe
        $user->subscription()->where('status', 'active')->update(['status' => 'cancelled']);

        // Créer le nouvel abonnement
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => $plan->interval == 'month' ? now()->addMonth() : now()->addYear(),
            'status' => 'active',
        ]);

        return redirect()->route('dashboard')->with('success', 'Félicitations ! Votre abonnement est maintenant actif.');
    }
}
