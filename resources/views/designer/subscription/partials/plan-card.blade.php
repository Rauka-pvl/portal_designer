@php
    /** @var array $plan */
    $key = $plan['key'];
    $mode = $mode ?? 'manage'; // onboard | manage
    $isCurrent = $isCurrent ?? false;
    $isRecommended = ! empty($plan['recommended']);
    $featureKeys = array_slice($plan['feature_keys'] ?? [], 0, 5);
    $monthlyLabel = \App\Support\DesignerSubscription::formatMoney((int) $plan['price']);
    $annualLabel = isset($plan['annual_price']) && $plan['annual_price'] !== null
        ? \App\Support\DesignerSubscription::formatMoney((int) $plan['annual_price']) : null;
    $annualMonthlyLabel = isset($plan['annual_monthly_equivalent']) && $plan['annual_monthly_equivalent'] !== null
        ? \App\Support\DesignerSubscription::formatMoney((int) $plan['annual_monthly_equivalent']) : null;
    $savingsLabel = ! empty($plan['annual_savings'])
        ? \App\Support\DesignerSubscription::formatMoney((int) $plan['annual_savings']) : null;

    if ($plan['unlimited_users']) {
        $usersLabel = __('subscription.limit_users_unlimited');
    } elseif ((int) ($plan['max_users'] ?? 1) === 1) {
        $usersLabel = __('subscription.limit_users_one');
    } else {
        $usersLabel = __('subscription.limit_users_up_to', ['count' => (int) $plan['max_users']]);
    }

    if ($plan['unlimited_projects']) {
        $projectsLabel = __('subscription.limit_projects_unlimited');
    } elseif ($plan['type'] === 'corporate') {
        $projectsLabel = __('subscription.limit_projects_team', ['count' => (int) $plan['max_projects']]);
    } else {
        $projectsLabel = __('subscription.limit_projects_up_to', ['count' => (int) $plan['max_projects']]);
    }
@endphp

<article
    class="sub-plan-card {{ $isCurrent ? 'is-current' : '' }} {{ $isRecommended ? 'sub-plan-card-recommended' : '' }}"
    @if ($mode === 'onboard')
        role="option"
        tabindex="0"
        aria-selected="false"
        data-plan-card
        data-plan="{{ $key }}"
        data-plan-label="{{ __('subscription.plan_'.$key) }}"
        data-plan-price="{{ $monthlyLabel }}"
        data-checkout-url="{{ route('subscription.checkout', ['plan' => $key]) }}"
    @endif
>
    <div class="flex items-start justify-between gap-3 mb-3">
        <div>
            <h3 class="text-xl font-semibold sub-title">{{ __('subscription.plan_'.$key) }}</h3>
            <p class="mt-1 text-sm sub-muted">{{ __('subscription.'.$plan['desc_key']) }}</p>
        </div>
        <div class="flex flex-col items-end gap-1.5 shrink-0">
            @if ($isRecommended)
                <span class="sub-badge-soft">{{ __('subscription.recommended') }}</span>
            @endif
            @if ($isCurrent)
                <span class="shrink-0 inline-flex items-center rounded-full border border-[#f59e0b]/40 px-2.5 py-0.5 text-[11px] font-medium text-[#f59e0b]">
                    {{ __('subscription.current_plan_badge') }}
                </span>
            @endif
            @if ($mode === 'onboard')
                <span class="hidden text-[11px] font-medium text-[#f59e0b]" data-selected-badge>{{ __('subscription.selected_badge') }}</span>
            @endif
        </div>
    </div>

    <div class="mb-4" data-price-monthly>
        <span class="text-2xl font-semibold sub-title">{{ $monthlyLabel }}</span>
        <span class="text-sm sub-muted"> {{ __('subscription.per_month') }}</span>
        @if (! empty($plan['annual_discount_percent']))
            <div class="mt-1 text-xs sub-muted">{{ __('subscription.annual_savings', ['amount' => $savingsLabel]) }} · −{{ (int) $plan['annual_discount_percent'] }}% {{ mb_strtolower(__('subscription.billing_annual')) }}</div>
        @endif
    </div>
    @if ($annualLabel)
        <div class="mb-4 hidden" data-price-yearly>
            <span class="text-2xl font-semibold sub-title">{{ $annualLabel }}</span>
            <span class="text-sm sub-muted"> / {{ mb_strtolower(__('subscription.billing_annual')) }}</span>
            <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                <span class="sub-badge-soft">−{{ (int) $plan['annual_discount_percent'] }}%</span>
                <span class="sub-muted">{{ __('subscription.annual_savings', ['amount' => $savingsLabel]) }}</span>
                <span class="sub-muted">· {{ __('subscription.annual_monthly_equiv', ['price' => $annualMonthlyLabel]) }}</span>
            </div>
        </div>
    @endif

    <ul class="space-y-2 mb-3">
        <li class="flex gap-2 text-sm sub-text">
            <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#f59e0b] shrink-0" aria-hidden="true"></span>
            {{ $usersLabel }}
        </li>
        <li class="flex gap-2 text-sm sub-text">
            <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#f59e0b] shrink-0" aria-hidden="true"></span>
            {{ $projectsLabel }}
        </li>
        <li class="flex gap-2 text-sm sub-text">
            <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#f59e0b] shrink-0" aria-hidden="true"></span>
            {{ __('subscription.limit_clients_unlimited') }}
        </li>
        <li class="flex gap-2 text-sm sub-text">
            <span class="mt-1.5 h-1.5 w-1.5 rounded-full {{ ! empty($plan['priority_support']) ? 'bg-red-400' : 'bg-[#f59e0b]' }} shrink-0" aria-hidden="true"></span>
            {{ ! empty($plan['priority_support']) ? __('subscription.support_priority') : __('subscription.support_standard') }}
        </li>
    </ul>

    <ul class="space-y-2 mb-4 flex-1 border-t border-white/5 pt-3">
        @foreach ($featureKeys as $feat)
            <li class="flex gap-2 text-sm sub-muted">
                <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#7c8799] shrink-0" aria-hidden="true"></span>
                {{ __('subscription.'.$feat) }}
            </li>
        @endforeach
    </ul>

    @if ($mode === 'onboard')
        <button type="button" class="sub-btn sub-btn-secondary w-full" data-select-plan="{{ $key }}">
            {{ __('subscription.select_plan', ['plan' => __('subscription.plan_'.$key)]) }}
        </button>
    @else
        @if ($isCurrent)
            <button type="button" class="sub-btn sub-btn-secondary w-full" disabled>{{ __('subscription.current_plan_badge') }}</button>
        @else
            <button type="button"
                class="sub-btn sub-btn-secondary w-full"
                data-open-change-plan="{{ $key }}"
                data-plan-type="{{ $plan['type'] }}"
                data-plan-label="{{ __('subscription.plan_'.$key) }}"
                data-plan-price="{{ $monthlyLabel }}">
                {{ __('subscription.select_plan', ['plan' => __('subscription.plan_'.$key)]) }}
            </button>
        @endif
    @endif
</article>
