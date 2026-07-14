@php
    $active = $active ?? 'profile';
    $role = auth()->user()->role ?? '';
    $isSupplierNav = $role === 'supplier';
    $profileRoute = $isSupplierNav ? 'supplier.profile.show' : 'profile.show';
    $reviewsRoute = $isSupplierNav ? 'supplier.profile.reviews' : 'profile.reviews';
    $cashbackRoute = 'profile.cashback';
    $canReview = in_array($role, ['designer', 'supplier'], true);
    $canCashback = $role === 'designer';
@endphp

<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route($profileRoute) }}"
        class="px-4 py-2 rounded-lg border text-sm transition-colors {{ $active === 'profile'
            ? 'border-[#f59e0b] text-[#f59e0b] bg-[#f8fafc] dark:bg-[#161615]'
            : 'border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b]' }}">
        {{ __('reviews.tab_profile') }}
    </a>

    @if ($canReview && Route::has($reviewsRoute))
        <a href="{{ route($reviewsRoute) }}"
            class="px-4 py-2 rounded-lg border text-sm transition-colors inline-flex items-center gap-1.5 {{ $active === 'reviews'
                ? 'border-[#f59e0b] text-[#f59e0b] bg-[#f8fafc] dark:bg-[#161615]'
                : 'border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b]' }}">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.28 3.94a1 1 0 00.95.69h4.15c.97 0 1.37 1.24.59 1.81l-3.36 2.44a1 1 0 00-.36 1.12l1.28 3.94c.3.92-.75 1.69-1.54 1.12l-3.35-2.44a1 1 0 00-1.18 0l-3.35 2.44c-.79.57-1.84-.2-1.54-1.12l1.28-3.94a1 1 0 00-.36-1.12L1.93 9.37c-.78-.57-.38-1.81.59-1.81h4.15a1 1 0 00.95-.69L9.05 2.93z"/>
            </svg>
            {{ __('reviews.tab_reviews') }}
        </a>
    @endif

    @if ($canCashback && Route::has($cashbackRoute))
        <a href="{{ route($cashbackRoute) }}"
            class="px-4 py-2 rounded-lg border text-sm transition-colors inline-flex items-center gap-1.5 {{ $active === 'cashback'
                ? 'border-[#f59e0b] text-[#f59e0b] bg-[#f8fafc] dark:bg-[#161615]'
                : 'border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b]' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('cashback.tab') }}
        </a>
    @endif
</div>
