@php
    $isUnread = ! (bool) $n->is_read;
    $rowHref = $actions['row_href'] ?? null;
    $primary = $actions['primary'] ?? null;
    $secondary = $actions['secondary'] ?? null;
    $menuSecondary = $actions['menu_secondary'] ?? null;
    $accent = (bool) ($actions['accent_primary'] ?? false);
    $icon = $actions['icon'] ?? 'bell';
    $type = $actions['type'] ?? 'info';
@endphp

<article
    class="n-row notification-row rounded-xl border border-[#7c8799]/45 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3.5 py-3.5 sm:px-4 {{ $isUnread ? 'is-unread' : '' }} {{ $rowHref ? 'cursor-pointer' : '' }}"
    data-id="{{ $n->id }}"
    data-read="{{ $isUnread ? '0' : '1' }}"
    data-type="{{ $type }}"
    data-row-href="{{ $rowHref }}"
    data-read-url="{{ route($routePrefix . '.read', $n->id) }}"
    data-unread-url="{{ route($routePrefix . '.unread', $n->id) }}"
    data-destroy-url="{{ route($routePrefix . '.destroy', $n->id) }}"
>
    <div class="flex items-start gap-2.5">
        <div class="w-2.5 pt-2 shrink-0 flex justify-center">
            <span class="n-dot {{ $isUnread ? '' : 'invisible' }}" data-unread-dot aria-hidden="true"></span>
        </div>

        <div class="mt-0.5 shrink-0 w-9 h-9 rounded-full bg-[#F8FAFC] dark:bg-[#0a0a0a] border border-[#7c8799]/35 dark:border-[#3E3E3A] flex items-center justify-center text-[#64748b] dark:text-[#A1A09A]">
            @include('notifications.partials.icon', ['icon' => $icon])
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="text-[14px] sm:text-[15px] text-[#0f172a] dark:text-[#EDEDEC] leading-snug {{ $isUnread ? 'font-semibold' : 'font-medium' }}" data-title>
                        {{ $n->title }}
                    </h3>
                    @if (!empty($n->comment))
                        <p class="mt-1 text-[13px] text-[#64748b] dark:text-[#A1A09A] whitespace-pre-line line-clamp-3">{{ $n->comment }}</p>
                    @endif
                    <p class="mt-1.5 text-[12px] text-[#94a3b8] dark:text-[#71717a]">{{ optional($n->created_at)->diffForHumans() }}</p>
                </div>

                <div class="relative shrink-0 n-actions-stop">
                    <button type="button"
                        class="n-menu-btn w-10 h-10 inline-flex items-center justify-center rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#F1F5F9] dark:hover:bg-[#0a0a0a]"
                        aria-label="{{ __('notifications.menu_actions') }}"
                        aria-haspopup="true"
                        aria-expanded="false">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM11.5 16a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z"/></svg>
                    </button>
                    <div class="n-menu hidden">
                        <button type="button" class="n-mark-toggle" data-mode="{{ $isUnread ? 'read' : 'unread' }}">
                            {{ $isUnread ? __('notifications.mark_read') : __('notifications.mark_unread') }}
                        </button>
                        @if ($menuSecondary)
                            <a href="{{ $menuSecondary['url'] }}" class="sm:hidden n-menu-secondary">{{ $menuSecondary['label'] }}</a>
                        @endif
                        <button type="button" class="n-delete danger">{{ __('notifications.delete') }}</button>
                    </div>
                </div>
            </div>

            @if ($primary || $secondary)
                <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2 n-actions-stop" data-primary-wrap>
                    @if ($primary)
                        @if (($primary['mode'] ?? '') === 'link')
                            <a href="{{ $primary['url'] }}"
                               class="n-btn {{ $accent ? 'n-btn-accent' : 'n-btn-secondary' }} w-full sm:w-auto n-primary-link"
                               data-mark-read="1">
                                {{ $primary['label'] }}
                            </a>
                        @elseif (($primary['mode'] ?? '') === 'post')
                            <button type="button"
                                class="n-btn n-btn-accent w-full sm:w-auto n-primary-post"
                                data-url="{{ $primary['url'] }}">
                                {{ $primary['label'] }}
                            </button>
                        @elseif (($primary['mode'] ?? '') === 'rate')
                            <button type="button"
                                class="n-btn n-btn-accent w-full sm:w-auto n-primary-rate"
                                data-order-id="{{ $primary['order_id'] }}"
                                data-title="{{ e($primary['title'] ?? '') }}">
                                {{ $primary['label'] }}
                            </button>
                        @endif
                    @endif

                    @if ($secondary)
                        <a href="{{ $secondary['url'] }}"
                           class="n-btn n-btn-secondary hidden sm:inline-flex n-secondary-link"
                           data-mark-read="1">
                            {{ $secondary['label'] }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</article>
