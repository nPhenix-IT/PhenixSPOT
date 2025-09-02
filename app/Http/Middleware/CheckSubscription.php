<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Permettre l'accès aux admins et super-admins sans abonnement
        if ($user->hasRole(['Super-admin', 'Admin'])) {
            return $next($request);
        }

        // Récupérer l'abonnement de l'utilisateur
        $subscription = $user->subscription;

        // Vérifier si l'abonnement existe et s'il est actif.
        // Cette vérification en deux temps évite l'erreur "isActive() on null".
        if (!$subscription || !$subscription->isActive()) {
            // Rediriger vers la page des offres avec un message
            return redirect()->route('plans.index')->with('error', 'Votre abonnement est inactif. Veuillez choisir une offre.');
        }

        return $next($request);
    }
}