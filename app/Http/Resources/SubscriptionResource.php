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
        $team = app(TeamService::class)->activeTeamFor($this->resource);
        $amount = DesignerSubscription::nextChargeAmount($this->resource);

        return [
            'plan' => $this->subscription_plan,
            'status' => DesignerSubscription::status($this->resource),
            'has_access' => DesignerSubscription::hasAccess($this->resource),
            'is_on_trial' => DesignerSubscription::isOnTrial($this->resource),
            'trial_days_left' => DesignerSubscription::trialDaysLeft($this->resource),
            'access_ends_at' => DesignerSubscription::accessEndsAt($this->resource)?->toISOString(),
            'next_charge_at' => DesignerSubscription::nextChargeAt($this->resource)?->toISOString(),
            'next_charge_amount' => $amount === null ? null : DesignerSubscription::formatMoney($amount),
            'auto_renew' => DesignerSubscription::isAutoRenewEnabled($this->resource),
            'payment_method' => $this->subscription_payment_method,
            'can_manage_billing' => AccountPermissions::canManageBilling($this->resource),
            'team_seats_used' => $team?->usedSeats(),
            'team_seats_max' => $team?->max_members,
        ];
    }
}
