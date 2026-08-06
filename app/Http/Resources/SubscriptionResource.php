<?php

namespace App\Http\Resources;

use App\Services\Team\TeamService;
use App\Support\AccountPermissions;
use App\Support\DesignerSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        $teamService = app(TeamService::class);
        $team = $teamService->activeTeamFor($user)
            ?? \App\Models\DesignerTeam::query()->where('owner_id', $user->id)->where('status', 'active')->first();
        $teamRole = $team?->roleFor($user);
        $amount = DesignerSubscription::nextChargeAmount($user);
        $primary = DesignerSubscription::primaryAction($user);
        $isCorporate = DesignerSubscription::isCorporatePlanUser($user)
            || ($team && $teamService->isCorporateUser($user));

        return [
            'plan' => $user->subscription_plan,
            'status' => DesignerSubscription::status($user),
            'has_access' => DesignerSubscription::hasAccess($user),
            'is_on_trial' => DesignerSubscription::isOnTrial($user),
            'can_use_trial' => DesignerSubscription::canUseTrial($user),
            'trial_days_left' => DesignerSubscription::trialDaysLeft($user),
            'trial_progress' => DesignerSubscription::trialProgressPercent($user),
            'trial_total_days' => DesignerSubscription::trialDays(),
            'trial_requires_card' => DesignerSubscription::trialRequiresCard(),
            'access_ends_at' => DesignerSubscription::accessEndsAt($user)?->toISOString(),
            'next_charge_at' => DesignerSubscription::nextChargeAt($user)?->toISOString(),
            'next_charge_amount' => $amount === null ? null : DesignerSubscription::formatMoney($amount),
            'auto_renew' => DesignerSubscription::isAutoRenewEnabled($user),
            'payment_method' => $user->subscription_payment_method,
            'card_last4' => DesignerSubscription::cardLast4($user),
            'card_expiry' => DesignerSubscription::cardExpiry($user),
            'cancelled_at' => $user->subscription_cancelled_at?->toISOString(),
            'cancel_reason' => $user->subscription_cancel_reason,
            'can_manage_billing' => AccountPermissions::canManageBilling($user),
            'is_onboarding' => DesignerSubscription::needsOnboardingLayout($user),
            'has_real_payments' => DesignerSubscription::hasRealPayments($user),
            'is_corporate' => $isCorporate,
            'team_role' => $teamRole?->value,
            'team_seats_used' => $team?->usedSeats(),
            'team_seats_max' => $team?->max_members,
            'billing_name' => $user->name,
            'billing_email' => $user->email,
            'primary_action' => [
                'key' => $primary['key'],
                'label' => $primary['label'],
            ],
        ];
    }
}
