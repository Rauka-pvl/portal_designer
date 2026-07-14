<?php

namespace App\Http\Middleware;

use App\Support\DesignerSubscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDesignerSubscription
{
    /**
     * Без активной подписки / триала дизайнер может открывать
     * только страницы подписки и logout.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'designer') {
            return $next($request);
        }

        if ($request->routeIs('subscription.*', 'logout', 'language.switch')) {
            return $next($request);
        }

        if (DesignerSubscription::hasAccess($user)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription required',
                'code' => 'subscription_required',
            ], 402);
        }

        return redirect()->route('subscription.index');
    }
}
