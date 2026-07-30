<?php

namespace App\Http\Middleware;

use App\Models\DesignerTeamMember;
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

        $hadCorporateTeam = DesignerTeamMember::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('team', fn ($q) => $q->where('status', 'active'))
            ->exists();

        if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $hadCorporateTeam
                    ? __('subscription.corporate_expired_title')
                    : 'Subscription required',
                'code' => $hadCorporateTeam ? 'corporate_subscription_expired' : 'subscription_required',
            ], 402);
        }

        return redirect()->route('subscription.index', $hadCorporateTeam ? [
            'reason' => 'corporate_expired',
        ] : []);
    }
}
