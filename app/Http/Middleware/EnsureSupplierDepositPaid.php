<?php

namespace App\Http\Middleware;

use App\Support\SupplierDeposit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupplierDepositPaid
{
    /**
     * Without a server-confirmed guarantee deposit the supplier may only open
     * deposit pages, logout, language switch, FAQ and force-password.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ($user->role ?? '') !== 'supplier') {
            return $next($request);
        }

        if ($request->routeIs(
            'supplier.deposit.*',
            'logout',
            'language.switch',
            'faq.index',
            'supplier.force-password.*',
        )) {
            return $next($request);
        }

        if (SupplierDeposit::isDepositPaid($user)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Guarantee deposit required',
                'code' => 'deposit_required',
            ], 402);
        }

        return redirect()->route('supplier.deposit.index');
    }
}
