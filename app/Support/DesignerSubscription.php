<?php

namespace App\Support;

use App\Models\DesignerSubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DesignerSubscription
{
    public const PLAN_STANDARD = 'standard';

    public const PLAN_PRO = 'pro';

    public const PERIOD_DAYS = 30;

    public const TRIAL_DAYS = 7;

    public const PROMO_CODE = 'DesignPortal-2026!';

    public const METHOD_KASPI = 'kaspi';

    public const METHOD_CARD = 'card';

    public const METHOD_PROMO = 'promo';

    /** @return array<string, array{key: string, price: int, period_days: int}> */
    public static function plans(): array
    {
        return [
            self::PLAN_STANDARD => [
                'key' => self::PLAN_STANDARD,
                'price' => 5000,
                'period_days' => self::PERIOD_DAYS,
            ],
            self::PLAN_PRO => [
                'key' => self::PLAN_PRO,
                'price' => 9990,
                'period_days' => self::PERIOD_DAYS,
            ],
        ];
    }

    public static function plan(string $key): ?array
    {
        return self::plans()[$key] ?? null;
    }

    public static function isValidPromo(?string $code): bool
    {
        return is_string($code) && hash_equals(self::PROMO_CODE, trim($code));
    }

    public static function canUseTrial(User $user): bool
    {
        return $user->role === 'designer' && ! (bool) $user->subscription_trial_used;
    }

    public static function hasAccess(User $user): bool
    {
        if ($user->role !== 'designer') {
            return true;
        }

        if ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            return true;
        }

        if ($user->subscription_trial_ends_at && $user->subscription_trial_ends_at->isFuture()) {
            return true;
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
        $starts = $ends->copy()->subDays(self::TRIAL_DAYS);
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

        if ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            return 'active';
        }

        if (self::isOnTrial($user)) {
            return 'trial';
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

        return $user->subscription_ends_at ?? $user->subscription_trial_ends_at;
    }

    public static function nextChargeAt(User $user): ?Carbon
    {
        if ($user->subscription_cancelled_at) {
            return null;
        }

        return self::accessEndsAt($user);
    }

    public static function nextChargeAmount(User $user): ?int
    {
        if ($user->subscription_cancelled_at) {
            return null;
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
        $plan = $user->subscription_plan ?: self::PLAN_STANDARD;

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
            throw ValidationException::withMessages([
                'promo_code' => [__('subscription.promo_invalid')],
            ]);
        }

        if (! in_array($paymentMethod, [self::METHOD_KASPI, self::METHOD_CARD, self::METHOD_PROMO], true)) {
            throw ValidationException::withMessages([
                'payment_method' => [__('subscription.invalid_payment_method')],
            ]);
        }

        if ($usePromo) {
            $paymentMethod = self::METHOD_PROMO;
        }

        $price = (int) $plan['price'];
        $amount = ($usePromo || self::canUseTrial($user)) ? 0 : $price;
        $useTrial = self::canUseTrial($user);

        $startsAt = now();
        $periodDays = $useTrial ? self::TRIAL_DAYS : (int) $plan['period_days'];

        if (! $useTrial && $user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
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
                'promo_code' => $usePromo ? self::PROMO_CODE : null,
                'discount_percent' => $usePromo ? 100 : 0,
                'is_trial' => $useTrial,
                'list_price' => $price,
                'card_last4' => $cardLast4,
                'card_expiry' => $cardExpiry,
            ],
        ]);

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

        $user->subscription_plan = $planKey;
        $user->subscription_cancelled_at = null;
        $user->subscription_cancel_reason = null;
        $user->save();
    }

    public static function updatePaymentMethod(User $user, string $method): void
    {
        if (! in_array($method, [self::METHOD_KASPI, self::METHOD_CARD], true)) {
            throw ValidationException::withMessages([
                'payment_method' => [__('subscription.invalid_payment_method')],
            ]);
        }

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
