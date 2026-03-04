<?php

namespace App\Http\Middleware;

use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanLimit
{
    public function __construct(private readonly PlanLimitService $planLimitService) {}

    public function handle(Request $request, Closure $next, string $key, string $increment = '1'): Response
    {
        $user = $request->user();

        if ($user && !$this->planLimitService->can($user, $key, (int) $increment)) {
            return response('Limite de forfait atteinte.', 403);
        }

        return $next($request);
    }
}
