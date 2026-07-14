@extends($layout)

@section('title', __('notifications.title'))
@section('header_title', __('notifications.title'))

@section('content')
    <div class="mb-6 flex items-start md:items-center justify-between gap-3 flex-col md:flex-row">
        <div>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ $subtitle }}</p>
        </div>
        <form method="POST" action="{{ route($routePrefix . '.read_all') }}">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                {{ __('notifications.mark_all_read') }}
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-200 text-sm border border-emerald-200 dark:border-emerald-500/30">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($notifications as $n)
            <div class="rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $n->title }}</div>
                        @if (!empty($n->comment))
                            <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1 whitespace-pre-line">{{ $n->comment }}</div>
                        @endif
                        <div class="text-xs text-[#94a3b8] dark:text-[#71717a] mt-2">{{ optional($n->created_at)->format('Y-m-d H:i') }}</div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                        @if ($n->is_read)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                {{ __('notifications.read') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200">
                                {{ __('notifications.unread') }}
                            </span>
                            <form method="POST" action="{{ route($routePrefix . '.read', $n->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-xs text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                                    {{ __('notifications.mark_read') }}
                                </button>
                            </form>
                        @endif

                        @if ($isDesigner && $n->action_key === 'confirm_referral_supplier' && !empty($n->related_supplier_id))
                            <form method="POST" action="{{ route($routePrefix . '.confirm_referral_supplier', $n->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-900/40 text-xs text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                    {{ __('notifications.referral_supplier_add') }}
                                </button>
                            </form>
                        @endif

                        @if ($isDesigner && !empty($n->related_supplier_id) && $n->action_key !== 'supplier_order')
                            <a href="{{ route('suppliers.show', ['supplierId' => $n->related_supplier_id, 'readonly' => 1]) }}" class="px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-xs text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                                {{ __('notifications.view_supplier') }}
                            </a>
                        @endif

                        @if (in_array($n->action_key, ['rate_supplier', 'rate_designer'], true) && !empty($n->related_order_id))
                            <button type="button"
                                onclick="openRatingModal('{{ $n->related_order_id }}', @js($n->title))"
                                class="px-3 py-1.5 rounded-lg border border-[#f59e0b] text-xs text-[#f59e0b] hover:bg-[#f59e0b]/10 transition-colors inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.28 3.94a1 1 0 00.95.69h4.15c.97 0 1.37 1.24.59 1.81l-3.36 2.44a1 1 0 00-.36 1.12l1.28 3.94c.3.92-.75 1.69-1.54 1.12l-3.35-2.44a1 1 0 00-1.18 0l-3.35 2.44c-.79.57-1.84-.2-1.54-1.12l1.28-3.94a1 1 0 00-.36-1.12L1.93 9.37c-.78-.57-.38-1.81.59-1.81h4.15a1 1 0 00.95-.69L9.05 2.93z"/></svg>
                                {{ __('notifications.rate_action') }}
                            </button>
                        @endif

                        @if (!$isDesigner && $n->action_key === 'supplier_order' && Route::has('supplier.orders'))
                            <a href="{{ route('supplier.orders') }}" class="px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-xs text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                                {{ __('supplier-portal.nav_orders') }}
                            </a>
                        @endif

                        @if ($n->action_key === 'order_offer')
                            @if ($isDesigner && Route::has('supplier-orders.index'))
                                <a href="{{ route('supplier-orders.index') }}" class="px-3 py-1.5 rounded-lg border border-[#f59e0b] text-xs text-[#f59e0b] hover:bg-[#f59e0b]/10 transition-colors">
                                    {{ __('notifications.offer_view_order') }}
                                </a>
                            @elseif (!$isDesigner && Route::has('supplier.orders'))
                                <a href="{{ route('supplier.orders') }}?tab=offers" class="px-3 py-1.5 rounded-lg border border-[#f59e0b] text-xs text-[#f59e0b] hover:bg-[#f59e0b]/10 transition-colors">
                                    {{ __('notifications.offer_view_order') }}
                                </a>
                            @endif
                        @endif

                        <form method="POST" action="{{ route($routePrefix . '.destroy', $n->id) }}" onsubmit="return confirm('{{ __('notifications.delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-200 dark:border-red-900/40 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                {{ __('notifications.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-8 text-center text-[#64748b] dark:text-[#A1A09A]">
                {{ __('notifications.empty') }}
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif

    @include('partials.rating-modal', ['reviewStoreUrl' => $reviewStoreUrl])
@endsection
