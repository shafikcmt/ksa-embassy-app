<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $subscription = $user->agency?->activeSubscription;

        if (! $subscription || ! $subscription->isActive()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Subscription expired.'], 403);
            }
            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}
