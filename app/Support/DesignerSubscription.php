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
                'price' => 1000,
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

        if ($user->subscription_cancelled_at && (! $user->subscription_ends_at || $user->subscription_ends_at->isPast())
            && (! $user->subscription_trial_ends_at || $user->subscription_trial_ends_at->isPast())) {
            return false;
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

    public static function status(User $user): string
    {
        if ($user->subscription_cancelled_at && ! self::hasAccess($user)) {
            return 'cancelled';
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

    /**
     * Оформление / продление подписки.
     * Первый раз — 7 дней триала на выбранный план.
     * Промокод DesignPortal-2026! = 100% скидка.
     */
    public static function checkout(
        User $user,
        string $planKey,
        string $paymentMethod,
        ?string $promoCode = null
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
        $amount = $usePromo ? 0 : $price;
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
            'status' => 'completed',
            'meta' => [
                'payment_method' => $paymentMethod,
                'promo_code' => $usePromo ? self::PROMO_CODE : null,
                'discount_percent' => $usePromo ? 100 : 0,
                'is_trial' => $useTrial,
                'list_price' => $price,
            ],
        ]);

        $user->subscription_plan = $planKey;
        $user->subscription_payment_method = $paymentMethod;
        $user->subscription_cancelled_at = null;

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

    public static function cancel(User $user): void
    {
        $user->subscription_cancelled_at = now();
        // Доступ сохраняется до конца оплаченного / триального периода
        $user->save();
    }

    /** Редирект после логина/регистрации дизайнера. */
    public static function redirectRoute(User $user): string
    {
        return self::hasAccess($user) ? 'dashboard' : 'subscription.index';
    }
}
