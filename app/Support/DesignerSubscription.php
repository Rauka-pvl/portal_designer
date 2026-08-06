<?php

namespace App\Support;

use App\Models\DesignerSubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DesignerSubscription
{
    public const PLAN_BASE = 'base';

    public const PLAN_STANDARD = 'standard';

    public const PLAN_PRO = 'pro';

    public const PLAN_ECONOMY = 'economy';

    public const PLAN_PROGRESS = 'progress';

    public const PLAN_SUCCESS = 'success';

    public const PERIOD_DAYS = 30;

    public const TRIAL_DAYS = 7;

    /** @deprecated Use config('subscription.promo_code') — kept empty so old references do not leak a secret. */
    public const PROMO_CODE = '';

    public const METHOD_KASPI = 'kaspi';

    public const METHOD_CARD = 'card';

    public const METHOD_PROMO = 'promo';

    public static function periodDays(): int
    {
        return max(1, (int) config('subscription.period_days', self::PERIOD_DAYS));
    }

    public static function trialDays(): int
    {
        return max(1, (int) config('subscription.trial_days', self::TRIAL_DAYS));
    }

    public static function configuredPromoCode(): string
    {
        return trim((string) config('subscription.promo_code', ''));
    }

    public static function trialEnabled(): bool
    {
        return (bool) config('subscription.trial_enabled', false);
    }

    /** Real Kaspi/card acquiring is connected. */
    public static function paymentsEnabled(): bool
    {
        return (bool) config('subscription.payments_enabled', false);
    }

    public static function promoValidDays(): int
    {
        return max(1, (int) config('subscription.promo_valid_days', 7));
    }

    /** Free period granted by a successful promo redemption (default 6 months). */
    public static function promoPeriodDays(): int
    {
        return max(1, (int) config('subscription.promo_period_days', 180));
    }

    /** Plan key granted by a successful promo redemption (default Success). */
    public static function promoPlanKey(): string
    {
        return trim((string) config('subscription.promo_plan', self::PLAN_SUCCESS));
    }

    public static function promoStartsAt(): ?Carbon
    {
        $raw = config('subscription.promo_starts_at');
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function promoEndsAt(): ?Carbon
    {
        $starts = self::promoStartsAt();
        if (! $starts) {
            return null;
        }

        return $starts->copy()->addDays(self::promoValidDays())->endOfDay();
    }

    /** Promo code redemption window is currently open. */
    public static function promoWindowActive(): bool
    {
        if (self::configuredPromoCode() === '') {
            return false;
        }

        $starts = self::promoStartsAt();
        $ends = self::promoEndsAt();
        if (! $starts || ! $ends) {
            return false;
        }

        return now()->greaterThanOrEqualTo($starts) && now()->lessThanOrEqualTo($ends);
    }

    public static function allowsStubPayments(): bool
    {
        return (bool) config('subscription.allow_stub_payments', false);
    }

    /**
     * Single source of truth for designer tariffs — DB-driven via PlanCatalog.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function plans(): array
    {
        return app(\App\Services\Billing\PlanCatalog::class)->keyed();
    }

    /** Designer without cabinet access — onboarding chrome (no full sidebar). */
    public static function needsOnboardingLayout(User $user): bool
    {
        return $user->role === 'designer' && ! self::hasAccess($user);
    }

    /** Card is not required to start a free trial in the current product flow. */
    public static function trialRequiresCard(): bool
    {
        return false;
    }

    /** Real money payments only (trial / zero-amount rows are excluded). */
    public static function hasRealPayments(User $user): bool
    {
        return DesignerSubscriptionPayment::query()
            ->where('user_id', $user->id)
            ->where('amount', '>', 0)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Feature keys for comparison table (union across plans, stable order).
     *
     * @return list<string>
     */
    public static function comparisonFeatureKeys(): array
    {
        $keys = [];
        foreach (self::plans() as $plan) {
            foreach ($plan['feature_keys'] as $key) {
                if (! in_array($key, $keys, true)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    public static function plan(string $key): ?array
    {
        return self::plans()[$key] ?? null;
    }

    public static function isValidPromo(?string $code): bool
    {
        if (! self::promoWindowActive() || ! is_string($code)) {
            return false;
        }

        $expected = self::configuredPromoCode();
        if ($expected === '') {
            return false;
        }

        return hash_equals($expected, trim($code));
    }

    public static function canUseTrial(User $user): bool
    {
        if (! self::trialEnabled()) {
            return false;
        }

        return $user->isDesigner() && ! (bool) $user->subscription_trial_used;
    }

    /** Personal dates only (owner Corporate billing). */
    public static function hasPersonalAccess(User $user): bool
    {
        if (! $user->isDesigner()) {
            return true;
        }

        $subscription = $user->subscription;

        // Corporate seat members inherit plan label from the team; only the owner is billable.
        if ($subscription?->isCorporate()) {
            $ownsActiveTeam = \App\Models\DesignerTeam::query()
                ->where('owner_id', $user->id)
                ->where('status', 'active')
                ->exists();
            if (! $ownsActiveTeam) {
                return false;
            }
        }

        if ($subscription?->expires_at && $subscription->expires_at->isFuture()) {
            return true;
        }

        if ($subscription?->trial_ends_at && $subscription->trial_ends_at->isFuture()) {
            return true;
        }

        return false;
    }

    public static function hasAccess(User $user): bool
    {
        if (! $user->isDesigner()) {
            return true;
        }

        if (self::hasPersonalAccess($user)) {
            return true;
        }

        // Corporate team member: access via owner's active Corporate subscription
        try {
            $teams = app(\App\Services\Team\TeamService::class);
            if ($teams->isCorporateUser($user)) {
                return true;
            }
        } catch (\Throwable) {
            // Service may be unavailable during early boot / migrations
        }

        return false;
    }

    public static function isOnTrial(User $user): bool
    {
        if ($user->role !== 'designer') {
            return false;
        }

        if ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            return false;
        }

        return $user->subscription_trial_ends_at !== null
            && $user->subscription_trial_ends_at->isFuture();
    }

    public static function trialDaysLeft(User $user): int
    {
        if (! self::isOnTrial($user) || ! $user->subscription_trial_ends_at) {
            return 0;
        }

        $seconds = $user->subscription_trial_ends_at->getTimestamp() - now()->getTimestamp();

        return max(0, (int) ceil($seconds / 86400));
    }

    public static function trialProgressPercent(User $user): int
    {
        if (! self::isOnTrial($user) || ! $user->subscription_trial_ends_at) {
            return 0;
        }

        $ends = $user->subscription_trial_ends_at->copy();
        $starts = $ends->copy()->subDays(self::trialDays());
        $total = max(1, $ends->getTimestamp() - $starts->getTimestamp());
        $elapsed = now()->getTimestamp() - $starts->getTimestamp();

        return (int) min(100, max(0, round(($elapsed / $total) * 100)));
    }

    public static function status(User $user): string
    {
        if ($user->subscription_cancelled_at && self::hasAccess($user)) {
            return 'cancelled';
        }

        if ($user->subscription_cancelled_at && ! self::hasAccess($user)) {
            return 'cancelled';
        }

        $lastPayment = DesignerSubscriptionPayment::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($lastPayment && in_array((string) $lastPayment->status, ['pending', 'failed'], true)
            && ! self::hasAccess($user)) {
            return 'payment_pending';
        }

        if (self::isOnTrial($user)) {
            return 'trial';
        }

        if (self::hasAccess($user)) {
            return 'active';
        }

        if ($user->subscription_plan || $user->subscription_trial_used) {
            return 'expired';
        }

        return 'none';
    }

    public static function accessEndsAt(User $user): ?Carbon
    {
        if ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            return $user->subscription_ends_at;
        }

        if ($user->subscription_trial_ends_at && $user->subscription_trial_ends_at->isFuture()) {
            return $user->subscription_trial_ends_at;
        }

        // Seat members: show owner's renewal date for Corporate UI.
        try {
            $team = app(\App\Services\Team\TeamService::class)->activeTeamFor($user);
            if ($team && (int) $team->owner_id !== (int) $user->id) {
                $ownerEnd = $team->owner?->subscription_ends_at;

                return $ownerEnd;
            }
        } catch (\Throwable) {
            // ignore
        }

        return $user->subscription_ends_at ?? $user->subscription_trial_ends_at;
    }

    /** Current plan model of the billing owner; null when unknown/archived. */
    private static function currentPlanModel(User $user): ?\App\Models\SubscriptionPlan
    {
        $key = (string) ($user->subscription_plan ?? '');
        if ($key === '') {
            return null;
        }

        return $user->subscription?->plan
            ?? \App\Models\SubscriptionPlan::findByKeyIncludingArchived($key);
    }

    public static function isCorporatePlanUser(User $user): bool
    {
        return self::currentPlanModel($user)?->isCorporate() ?? false;
    }

    public static function nextChargeAt(User $user): ?Carbon
    {
        if ($user->subscription_cancelled_at) {
            return null;
        }

        // Non-owner corporate members do not get charged personally.
        if (self::isCorporatePlanUser($user)) {
            $ownsActiveTeam = \App\Models\DesignerTeam::query()
                ->where('owner_id', $user->id)
                ->where('status', 'active')
                ->exists();
            if (! $ownsActiveTeam) {
                return null;
            }
        }

        return self::accessEndsAt($user);
    }

    public static function nextChargeAmount(User $user): ?int
    {
        if ($user->subscription_cancelled_at) {
            return null;
        }

        // Non-owner corporate members do not get charged personally.
        if (self::isCorporatePlanUser($user)) {
            $ownsActiveTeam = \App\Models\DesignerTeam::query()
                ->where('owner_id', $user->id)
                ->where('status', 'active')
                ->exists();
            if (! $ownsActiveTeam) {
                return null;
            }
        }

        $planKey = $user->subscription_plan;
        if (! $planKey || ! self::plan($planKey)) {
            return null;
        }

        return (int) self::plan($planKey)['price'];
    }

    public static function isAutoRenewEnabled(User $user): bool
    {
        return self::hasAccess($user) && ! $user->subscription_cancelled_at;
    }

    /**
     * @return array{key: string, label: string, href: string|null}
     */
    public static function primaryAction(User $user): array
    {
        $status = self::status($user);
        $plan = $user->subscription_plan
            ?: app(\App\Services\Billing\PlanCatalog::class)->defaultKey() ?? self::PLAN_STANDARD;

        return match ($status) {
            'trial' => [
                'key' => 'pay_now',
                'label' => __('subscription.cta_pay_now'),
                'href' => route('subscription.checkout', ['plan' => $plan]),
            ],
            'active' => [
                'key' => 'update_payment',
                'label' => __('subscription.cta_update_payment'),
                'href' => null, // modal
            ],
            'cancelled' => [
                'key' => 'resume',
                'label' => __('subscription.cta_resume'),
                'href' => null, // form resume
            ],
            'payment_pending' => [
                'key' => 'retry_payment',
                'label' => __('subscription.cta_retry_payment'),
                'href' => route('subscription.checkout', ['plan' => $plan]),
            ],
            'expired' => [
                'key' => 'resume',
                'label' => __('subscription.cta_resume'),
                'href' => route('subscription.checkout', ['plan' => $plan]),
            ],
            default => [
                'key' => 'connect',
                'label' => __('subscription.cta_connect'),
                'href' => null, // scroll to plans
            ],
        };
    }

    public static function checkout(
        User $user,
        string $planKey,
        string $paymentMethod,
        ?string $promoCode = null,
        ?string $cardLast4 = null,
        ?string $cardExpiry = null
    ): DesignerSubscriptionPayment {
        $plan = self::plan($planKey);
        if (! $plan) {
            throw ValidationException::withMessages([
                'plan' => [__('subscription.invalid_plan')],
            ]);
        }

        $usePromo = self::isValidPromo($promoCode);
        if ($promoCode !== null && trim((string) $promoCode) !== '' && ! $usePromo) {
            $message = self::configuredPromoCode() !== '' && ! self::promoWindowActive()
                ? __('subscription.promo_expired')
                : __('subscription.promo_invalid');

            throw ValidationException::withMessages([
                'promo_code' => [$message],
            ]);
        }

        // A valid promo always activates the configured promo plan (Success),
        // regardless of the plan picked at checkout.
        if ($usePromo) {
            $promoPlan = self::plan(self::promoPlanKey());
            if ($promoPlan) {
                $planKey = self::promoPlanKey();
                $plan = $promoPlan;
            }
        }

        // Downgrade guard when an existing subscriber switches to a different plan via purchase.
        if ((string) ($user->subscription_plan ?? '') !== '' && (string) $user->subscription_plan !== $planKey) {
            app(\App\Services\Billing\PlanLimitService::class)->assertCanSwitchTo($user, $planKey);
        }

        if (! in_array($paymentMethod, [self::METHOD_KASPI, self::METHOD_CARD, self::METHOD_PROMO], true)) {
            throw ValidationException::withMessages([
                'payment_method' => [__('subscription.invalid_payment_method')],
            ]);
        }

        // Acquiring offline: only a valid promo may activate a plan.
        if (! self::paymentsEnabled() && ! $usePromo) {
            throw ValidationException::withMessages([
                'promo_code' => [__('subscription.promo_required')],
            ]);
        }

        if ($usePromo) {
            $paymentMethod = self::METHOD_PROMO;
        }

        // Card / Kaspi cannot complete while acquiring is off (even with stub flag).
        if (! $usePromo && ! self::paymentsEnabled()) {
            throw ValidationException::withMessages([
                'payment_method' => [__('subscription.payment_provider_unavailable')],
            ]);
        }

        $price = (int) $plan['price'];
        $useTrial = ! $usePromo && self::canUseTrial($user);
        $amount = ($usePromo || $useTrial) ? 0 : $price;

        // Paid (non-trial, non-promo) checkout requires a real PSP — stub completion is gated.
        if (! $useTrial && ! $usePromo && $amount > 0 && ! self::allowsStubPayments()) {
            throw ValidationException::withMessages([
                'payment_method' => [__('subscription.payment_provider_unavailable')],
            ]);
        }

        $startsAt = now();
        $periodDays = $usePromo
            ? self::promoPeriodDays()
            : ($useTrial ? self::trialDays() : (int) $plan['period_days']);

        if (! $useTrial && ! $usePromo && $user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            $startsAt = $user->subscription_ends_at->copy();
        }

        $endsAt = $startsAt->copy()->addDays($periodDays);

        $payment = DesignerSubscriptionPayment::create([
            'user_id' => $user->id,
            'plan' => $planKey,
            'amount' => $amount,
            'period_days' => $periodDays,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $useTrial ? 'trial' : 'completed',
            'meta' => [
                'payment_method' => $paymentMethod,
                'promo_code' => $usePromo ? self::configuredPromoCode() : null,
                'discount_percent' => $usePromo ? 100 : 0,
                'is_trial' => $useTrial,
                'is_promo' => $usePromo,
                'list_price' => $price,
                'card_last4' => $cardLast4,
                'card_expiry' => $cardExpiry,
            ],
        ]);
        $planModel = \App\Models\SubscriptionPlan::findByKey($planKey);
        if ($planModel) {
            \App\Models\Subscription::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id' => $planModel->id,
                    'status' => $useTrial ? 'trial' : 'active',
                    'starts_at' => $startsAt,
                    'expires_at' => $useTrial ? null : $endsAt,
                    'trial_ends_at' => $useTrial ? $endsAt : null,
                    'cancelled_at' => null,
                    'cancel_reason' => null,
                ]
            );
        }

        $user->subscription_plan = $planKey;
        $user->subscription_payment_method = $paymentMethod === self::METHOD_PROMO
            ? ($user->subscription_payment_method ?: self::METHOD_KASPI)
            : $paymentMethod;
        $user->subscription_cancelled_at = null;
        $user->subscription_cancel_reason = null;

        if ($useTrial) {
            $user->subscription_trial_ends_at = $endsAt;
            $user->subscription_trial_used = true;
            $user->subscription_ends_at = null;
        } else {
            $user->subscription_ends_at = $endsAt;
        }

        $user->save();

        if ($planModel?->isCorporate()) {
            app(\App\Services\Team\TeamService::class)->activateCorporateForOwner($user, null, $planModel);
        }

        return $payment;
    }

    public static function changePlan(User $user, string $planKey): void
    {
        if (! self::plan($planKey)) {
            throw ValidationException::withMessages([
                'plan' => [__('subscription.invalid_plan')],
            ]);
        }

        if (! self::hasAccess($user)) {
            throw ValidationException::withMessages([
                'plan' => [__('subscription.action_unavailable')],
            ]);
        }

        // Backend downgrade guard: never exceed the new plan's project/user limits.
        app(\App\Services\Billing\PlanLimitService::class)->assertCanSwitchTo($user, $planKey);

        $previous = self::currentPlanModel($user);
        $newPlan = \App\Models\SubscriptionPlan::findByKey($planKey);

        $user->subscription_plan = $planKey;
        $user->subscription_cancelled_at = null;
        $user->subscription_cancel_reason = null;
        $user->save();

        if ($newPlan?->isCorporate()) {
            app(\App\Services\Team\TeamService::class)->activateCorporateForOwner($user, null, $newPlan);
        } elseif ($newPlan?->isIndividual() && $previous?->isCorporate()) {
            // Downgrade: archive active teams owned by this user (data kept).
            \App\Models\DesignerTeam::query()
                ->where('owner_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'archived']);
        }
    }

    public static function updatePaymentMethod(User $user, string $method): void
    {
        if (! in_array($method, [self::METHOD_KASPI, self::METHOD_CARD], true)) {
            throw ValidationException::withMessages([
                'payment_method' => [__('subscription.invalid_payment_method')],
            ]);
        }

        $payment = DesignerSubscriptionPayment::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($payment) {
            $meta = is_array($payment->meta) ? $payment->meta : [];
            $meta['payment_method'] = $method;
            $payment->meta = $meta;
            $payment->save();
        }

        // Keep in-request accessor cache aligned with persisted meta.
        $user->subscription_payment_method = $method;
        $user->save();
    }

    public static function cancel(User $user, ?string $reason = null): void
    {
        $user->subscription_cancelled_at = now();
        $user->subscription_cancel_reason = $reason;
        $user->save();
    }

    public static function resume(User $user): void
    {
        if (! self::hasAccess($user)) {
            throw ValidationException::withMessages([
                'plan' => [__('subscription.action_unavailable')],
            ]);
        }

        $user->subscription_cancelled_at = null;
        $user->subscription_cancel_reason = null;
        $user->save();
    }

    public static function redirectRoute(User $user): string
    {
        return self::hasAccess($user) ? 'dashboard' : 'subscription.index';
    }

    public static function cardLast4(User $user): ?string
    {
        return self::latestCardMeta($user)['card_last4'] ?? null;
    }

    public static function cardExpiry(User $user): ?string
    {
        return self::latestCardMeta($user)['card_expiry'] ?? null;
    }

    /** @return array{card_last4: ?string, card_expiry: ?string} */
    private static function latestCardMeta(User $user): array
    {
        $payments = DesignerSubscriptionPayment::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get(['meta']);

        foreach ($payments as $payment) {
            $meta = is_array($payment->meta) ? $payment->meta : [];
            if (($meta['payment_method'] ?? null) !== self::METHOD_CARD) {
                continue;
            }

            $last4 = $meta['card_last4'] ?? null;
            $expiry = $meta['card_expiry'] ?? null;

            return [
                'card_last4' => is_string($last4) && $last4 !== '' ? $last4 : null,
                'card_expiry' => is_string($expiry) && $expiry !== '' ? $expiry : null,
            ];
        }

        return ['card_last4' => null, 'card_expiry' => null];
    }

    public static function formatMoney(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' '.__('subscription.currency');
    }

    public static function formatDate(?Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->locale(app()->getLocale())->translatedFormat('d F Y');
    }
}
