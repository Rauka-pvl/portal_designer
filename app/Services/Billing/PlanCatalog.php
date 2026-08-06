<?php

namespace App\Services\Billing;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for sellable plans.
 * Plans live in subscription_plans; this catalog caches and exposes them
 * grouped/typed so controllers, views and services never hardcode plan keys.
 */
class PlanCatalog
{
    private const CACHE_KEY = 'billing.plan_catalog.v1';

    /** @return Collection<int, SubscriptionPlan> */
    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            return SubscriptionPlan::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });
    }

    /** @return Collection<int, SubscriptionPlan> */
    public function individual(): Collection
    {
        return $this->all()->where('type', SubscriptionPlan::TYPE_INDIVIDUAL)->values();
    }

    /** @return Collection<int, SubscriptionPlan> */
    public function corporate(): Collection
    {
        return $this->all()->where('type', SubscriptionPlan::TYPE_CORPORATE)->values();
    }

    public function find(string $key): ?SubscriptionPlan
    {
        return $this->all()->firstWhere('key', $key);
    }

    public function has(string $key): bool
    {
        return $this->find($key) !== null;
    }

    public function defaultKey(): ?string
    {
        return $this->individual()->firstWhere('recommended', true)?->key
            ?? $this->individual()->first()?->key
            ?? $this->all()->first()?->key;
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * UI/API payload for a plan. Shape is backward compatible with the old
     * hardcoded DesignerSubscription::plans() entries, extended with limits.
     *
     * @return array<string, mixed>
     */
    public function toArray(SubscriptionPlan $plan): array
    {
        return [
            'key' => $plan->key,
            'name' => $plan->name,
            'type' => $plan->type,
            'price' => $plan->monthlyPriceInt(),
            'period_days' => (int) config('subscription.period_days', 30),
            'recommended' => (bool) $plan->recommended,
            'feature_keys' => $plan->feature_keys ?? [],
            'limit_key' => 'plan_'.$plan->key.'_limit',
            'desc_key' => 'plan_'.$plan->key.'_desc',
            'max_members' => $plan->max_users ?? 0, // 0 = unlimited (mobile contract)
            'max_users' => $plan->max_users,
            'max_projects' => $plan->max_projects,
            'unlimited_users' => $plan->unlimitedUsers(),
            'unlimited_projects' => $plan->unlimitedProjects(),
            'priority_support' => (bool) $plan->priority_support,
            'annual_price' => $plan->annualPriceInt(),
            'annual_discount_percent' => (int) $plan->annual_discount_percent,
            'annual_savings' => $plan->yearlySavings(),
            'annual_monthly_equivalent' => $plan->annualMonthlyEquivalent(),
        ];
    }

    /** @return array<string, array<string, mixed>> keyed by plan key */
    public function keyed(): array
    {
        $out = [];
        foreach ($this->all() as $plan) {
            $out[$plan->key] = $this->toArray($plan);
        }

        return $out;
    }
}
