<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgencyAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! $user->agency_id || ! $user->agency) {
            abort(403, 'No agency assigned to your account.');
        }

        if ($user->agency->status === 'suspended') {
            return redirect()->route('agency.suspended');
        }

        if (! $user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated.']);
        }

        return $next($request);
    }
}
