<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('supplier.force-password.*', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            abort(403, 'Password change required.');
        }

        return redirect()->route('supplier.force-password.edit');
    }
}
