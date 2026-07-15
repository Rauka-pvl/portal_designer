@extends($layout)

@section('title', __('notifications.title'))
@section('header_title', __('notifications.title'))

@push('styles')
<style>
    .n-row { transition: background-color .15s ease; }
    .n-row.is-unread { background: rgba(245, 158, 11, 0.06); }
    .dark .n-row.is-unread { background: rgba(245, 158, 11, 0.08); }
    .n-dot { width: 8px; height: 8px; border-radius: 999px; background: #f59e0b; flex-shrink: 0; }
    .n-btn {
        display: inline-flex; align-items: center; justify-content: center;
        height: 36px; padding: 0 14px; border-radius: 9px;
        font-size: 13px; font-weight: 500; white-space: nowrap;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }
    .n-btn-accent { background: #f59e0b; color: #111827; border: 1px solid #f59e0b; }
    .n-btn-accent:hover { background: #d97706; border-color: #d97706; }
    .n-btn-secondary {
        background: transparent; color: #64748b;
        border: 1px solid rgba(124, 135, 153, 0.55);
    }
    .dark .n-btn-secondary { color: #A1A09A; border-color: #3E3E3A; }
    .n-btn-secondary:hover { border-color: #f59e0b; color: #f59e0b; }
    .n-menu {
        position: absolute; right: 0; top: 100%; margin-top: 4px; z-index: 30;
        min-width: 220px; border-radius: 12px;
        border: 1px solid rgba(124, 135, 153, 0.45);
        background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.18);
        padding: 4px 0;
    }
    .dark .n-menu { background: #1b1b18; border-color: #3E3E3A; }
    .n-menu button, .n-menu a {
        display: block; width: 100%; text-align: left;
        padding: 9px 14px; font-size: 13px; color: #0f172a;
    }
    .dark .n-menu button, .dark .n-menu a { color: #EDEDEC; }
    .n-menu button:hover, .n-menu a:hover { background: #F8FAFC; }
    .dark .n-menu button:hover, .dark .n-menu a:hover { background: #0a0a0a; }
    .n-menu .danger { color: #dc2626; }
    .dark .n-menu .danger { color: #f87171; }
</style>
@endpush

@section('content')
@php
    $csrf = csrf_token();
@endphp

<div id="notifications-app"
     data-csrf="{{ $csrf }}"
     data-route-prefix="{{ $routePrefix }}"
     data-unread-total="{{ (int) $unreadTotal }}"
     data-i18n="{{ json_encode([
         'mark_read' => __('notifications.mark_read'),
         'mark_unread' => __('notifications.mark_unread'),
         'delete' => __('notifications.delete'),
         'delete_confirm' => __('notifications.delete_confirm'),
         'marked_read' => __('notifications.marked_read'),
         'marked_unread' => __('notifications.marked_unread'),
         'marked_all_read' => __('notifications.marked_all_read'),
         'deleted' => __('notifications.deleted'),
         'referral_ok' => __('notifications.referral_supplier_confirmed'),
         'view_supplier' => __('notifications.view_supplier'),
         'empty' => __('notifications.empty'),
         'empty_unread' => __('notifications.empty_unread'),
     ], JSON_UNESCAPED_UNICODE) }}">

    <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ $subtitle }}</p>
            <div class="mt-3 flex items-center gap-1 border-b border-[#7c8799]/40 dark:border-[#3E3E3A]">
                <a href="{{ route($routePrefix . '.index', ['filter' => 'all']) }}"
                   class="px-3 py-2 text-sm border-b-2 -mb-px {{ $filter === 'all' ? 'border-[#f59e0b] text-[#f59e0b]' : 'border-transparent text-[#64748b] dark:text-[#A1A09A]' }}">
                    {{ __('notifications.filter_all') }}
                </a>
                <a href="{{ route($routePrefix . '.index', ['filter' => 'unread']) }}"
                   class="px-3 py-2 text-sm border-b-2 -mb-px inline-flex items-center gap-1.5 {{ $filter === 'unread' ? 'border-[#f59e0b] text-[#f59e0b]' : 'border-transparent text-[#64748b] dark:text-[#A1A09A]' }}">
                    {{ __('notifications.filter_unread') }}
                    <span id="notifications-unread-tab-count"
                          class="{{ $unreadTotal > 0 ? '' : 'hidden' }} inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-semibold bg-[#f59e0b]/15 text-[#f59e0b]">
                        {{ $unreadTotal }}
                    </span>
                </a>
            </div>
        </div>

        <button type="button" id="notifications-read-all"
                class="{{ $unreadTotal > 0 ? '' : 'hidden' }} n-btn n-btn-secondary self-start sm:self-auto"
                data-url="{{ route($routePrefix . '.read_all') }}">
            <span data-label>{{ __('notifications.mark_all_read') }}</span>
            <svg data-spinner class="hidden ml-2 w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path></svg>
        </button>
    </div>

    <div id="notifications-list" class="space-y-2.5">
        @forelse ($notifications as $n)
            @php $actions = $actionsById[$n->id] ?? ['type' => 'info', 'icon' => 'bell', 'primary' => null, 'secondary' => null, 'menu_secondary' => null, 'row_href' => null, 'accent_primary' => false]; @endphp
            @include('notifications.partials.row', [
                'n' => $n,
                'actions' => $actions,
                'routePrefix' => $routePrefix,
            ])
        @empty
            <div id="notifications-empty" class="rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-8 text-center text-sm text-[#64748b] dark:text-[#A1A09A]">
                {{ $filter === 'unread' ? __('notifications.empty_unread') : __('notifications.empty') }}
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $notifications->onEachSide(1)->links() }}
        </div>
    @endif
</div>

@include('partials.rating-modal', ['reviewStoreUrl' => $reviewStoreUrl])
@include('notifications.partials.scripts')
@endsection
