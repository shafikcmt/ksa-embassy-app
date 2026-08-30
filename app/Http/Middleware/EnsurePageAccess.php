<?php

namespace App\Http\Middleware;

use App\Support\PagePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level enforcement for agency "page access".
 *
 * Applied as page-access:{module} on each gated module route group. Direct URL
 * access by a restricted staff account is blocked here (nav hiding alone is not
 * enough). Agency admins and super admins always pass via PagePermissions.
 */
class EnsurePageAccess
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (PagePermissions::userCanAccess($user, $module)) {
            return $next($request);
        }

        // Restricted staff hitting a module they were not granted. Send them back
        // to the dashboard (always accessible) with a clear message rather than
        // rendering the page or leaking a 403 on a valid, tenant-owned URL.
        $label = PagePermissions::MODULES[$module]['label'] ?? 'that section';

        return redirect()->route('dashboard')->with(
            'error',
            "You don't have access to {$label}. Ask your agency admin to enable it."
        );
    }
}
