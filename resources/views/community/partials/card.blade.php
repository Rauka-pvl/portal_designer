@php
    $author = $post->author;
    $role = $author->role ?? '';
    $roleLabel = __('community.roles.'.$role);
    $city = $post->city ?: ($author->city ?? null);
    $meta = collect([$roleLabel, $city, optional($post->created_at)->diffForHumans()])->filter()->implode(' · ');
    $text = (string) ($post->text ?? '');
    $media = $post->media ?? collect();
    $mediaCount = $media->count();
    $isOwner = (bool) ($post->is_owner ?? false);
    $isLiked = (bool) ($post->is_liked ?? false);
    $isSaved = (bool) ($post->is_saved ?? false);

    $linkified = e($text);
    $linkified = preg_replace(
        '~(https?://[^\s<]+)~u',
        '<a href="$1" class="text-[#f59e0b] hover:underline break-all" target="_blank" rel="noopener noreferrer">$1</a>',
        $linkified
    );
    $urlsJson = json_encode($media->pluck('url')->values(), JSON_UNESCAPED_UNICODE);
@endphp

<article class="community-card rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-4 py-4 sm:px-5 sm:py-[18px] mb-3"
    data-post-id="{{ $post->id }}"
    data-category="{{ $post->category }}"
    data-city="{{ $post->city }}"
    data-likes="{{ (int) $post->likes_count }}"
    data-comments="{{ (int) $post->comments_count }}"
    data-liked="{{ $isLiked ? '1' : '0' }}"
    data-saved="{{ $isSaved ? '1' : '0' }}"
    data-owner="{{ $isOwner ? '1' : '0' }}">
    <div class="flex items-start justify-between gap-2">
        <div class="flex items-start gap-2.5 min-w-0">
            <a href="{{ route('community.profile', $author->id) }}" class="shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#f59e0b] rounded-full">
                @include('community.partials.avatar', ['name' => $author->name ?? '', 'size' => 'w-10 h-10', 'textSize' => 'text-sm'])
            </a>
            <div class="min-w-0 pt-0.5">
                <a href="{{ route('community.profile', $author->id) }}" class="text-[15px] font-semibold leading-tight text-[#0f172a] dark:text-[#EDEDEC] hover:text-[#f59e0b] truncate block">
                    {{ $author->name }}
                </a>
                <div class="text-[12px] sm:text-[13px] leading-snug text-[#94a3b8] dark:text-[#A1A09A] mt-1 truncate">
                    <a href="{{ route('community.post', $post->id) }}" class="hover:text-[#f59e0b]">{{ $meta }}</a>
                </div>
                @if ($post->category)
                    <span class="inline-flex mt-1.5 text-[11px] px-2 py-0.5 rounded-full border border-[#7c8799]/40 dark:border-[#3E3E3A] text-[#94a3b8] dark:text-[#A1A09A]">
                        {{ __('community.categories.'.$post->category) }}
                    </span>
                @endif
            </div>
        </div>

        <div class="relative shrink-0 -mr-1">
            <button type="button"
                class="community-menu-btn w-10 h-10 inline-flex items-center justify-center rounded-xl text-[#64748b] dark:text-[#A1A09A] hover:bg-[#F1F5F9] dark:hover:bg-[#0a0a0a] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#f59e0b]"
                aria-label="{{ __('community.menu_actions') }}"
                aria-haspopup="true"
                aria-expanded="false">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM11.5 16a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z"/></svg>
            </button>
            <div class="community-menu hidden absolute right-0 mt-1 w-52 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#1b1b18] shadow-lg z-20 py-1">
                @if ($isOwner)
                    <button type="button" class="community-edit w-full text-left px-3 py-2 text-sm hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]">{{ __('community.menu_edit') }}</button>
                    <button type="button" class="community-copy w-full text-left px-3 py-2 text-sm hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]">{{ __('community.menu_copy') }}</button>
                    <button type="button" class="community-delete w-full text-left px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">{{ __('community.menu_delete') }}</button>
                @else
                    <button type="button" class="community-toggle-save-menu w-full text-left px-3 py-2 text-sm hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]">
                        <span class="label-save">{{ $isSaved ? __('community.menu_unsave') : __('community.menu_save') }}</span>
                    </button>
                    <button type="button" class="community-copy w-full text-left px-3 py-2 text-sm hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]">{{ __('community.menu_copy') }}</button>
                    <button type="button" class="community-report w-full text-left px-3 py-2 text-sm hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]">{{ __('community.menu_report') }}</button>
                    <button type="button" class="community-hide w-full text-left px-3 py-2 text-sm hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]">{{ __('community.menu_hide') }}</button>
                @endif
            </div>
        </div>
    </div>

    @if ($text !== '')
        <div class="mt-3.5 text-[14px] sm:text-[15px] leading-relaxed text-[#0f172a] dark:text-[#EDEDEC] whitespace-pre-wrap break-words community-text {{ mb_strlen($text) > 280 ? 'is-clamped' : '' }}" data-full="{{ e($text) }}">
            {!! nl2br($linkified) !!}
        </div>
        @if (mb_strlen($text) > 280)
            <button type="button" class="community-toggle-text mt-1 text-sm text-[#f59e0b] hover:underline">{{ __('community.show_more') }}</button>
        @endif
    @endif

    @if ($mediaCount === 1)
        <button type="button"
            class="community-open-lightbox community-media-single mt-3.5 block w-full text-left"
            data-index="0"
            data-media-id="{{ $media->first()->id }}"
            data-urls="{{ $urlsJson }}">
            <img src="{{ $media->first()->url }}" alt="" loading="lazy">
        </button>
    @elseif ($mediaCount > 1)
        <div class="mt-3.5 community-media grid gap-1 overflow-hidden rounded-[14px] {{ $mediaCount === 2 ? 'grid-cols-2' : 'grid-cols-2' }}">
            @foreach ($media->take(4) as $idx => $m)
                @php $isLastOverlay = $idx === 3 && $mediaCount > 4; @endphp
                <button type="button"
                    class="community-open-lightbox relative block w-full overflow-hidden bg-[#0a0a0a] aspect-square"
                    data-index="{{ $idx }}"
                    data-media-id="{{ $m->id }}"
                    data-urls="{{ $urlsJson }}">
                    <img src="{{ $m->url }}" alt="" loading="lazy" class="w-full h-full object-cover">
                    @if ($isLastOverlay)
                        <span class="absolute inset-0 bg-black/55 flex items-center justify-center text-white text-2xl font-medium">+{{ $mediaCount - 3 }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    @endif

    @if ((int) $post->likes_count > 0 || (int) $post->comments_count > 0 || ($isOwner && ((int) $post->views_count > 0 || (int) $post->saves_count > 0)))
        <div class="mt-3 text-[12px] sm:text-[13px] text-[#94a3b8] dark:text-[#A1A09A] community-stats flex flex-wrap gap-x-2 gap-y-1">
            @if ((int) $post->likes_count > 0)
                <span class="stat-likes">{{ trans_choice('community.likes_count', (int) $post->likes_count, ['count' => (int) $post->likes_count]) }}</span>
            @endif
            @if ((int) $post->comments_count > 0)
                <span class="stat-comments">{{ trans_choice('community.comments_count', (int) $post->comments_count, ['count' => (int) $post->comments_count]) }}</span>
            @endif
            @if ($isOwner && (int) $post->views_count > 0)
                <span>{{ trans_choice('community.views_count', (int) $post->views_count, ['count' => (int) $post->views_count]) }}</span>
            @endif
            @if ($isOwner && (int) $post->saves_count > 0)
                <span>{{ trans_choice('community.saves_stat', (int) $post->saves_count, ['count' => (int) $post->saves_count]) }}</span>
            @endif
        </div>
    @endif

    <div class="mt-2 pt-2 border-t border-[#7c8799]/25 dark:border-[#3E3E3A] grid grid-cols-4 gap-0.5">
        <button type="button" class="community-like inline-flex items-center justify-center gap-1.5 min-h-10 rounded-lg text-xs sm:text-[13px] text-[#64748b] dark:text-[#A1A09A] hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a] {{ $isLiked ? '!text-[#f59e0b]' : '' }}" aria-pressed="{{ $isLiked ? 'true' : 'false' }}">
            <svg class="w-5 h-5" fill="{{ $isLiked ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/></svg>
            <span class="hidden sm:inline">{{ __('community.like') }}</span>
        </button>
        <a href="{{ route('community.post', $post->id) }}#comments" class="inline-flex items-center justify-center gap-1.5 min-h-10 rounded-lg text-xs sm:text-[13px] text-[#64748b] dark:text-[#A1A09A] hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <span class="hidden sm:inline">{{ __('community.comment') }}</span>
        </a>
        <button type="button" class="community-save inline-flex items-center justify-center gap-1.5 min-h-10 rounded-lg text-xs sm:text-[13px] text-[#64748b] dark:text-[#A1A09A] hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a] {{ $isSaved ? '!text-[#f59e0b]' : '' }}" aria-pressed="{{ $isSaved ? 'true' : 'false' }}">
            <svg class="w-5 h-5" fill="{{ $isSaved ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            <span class="hidden sm:inline label-save-btn">{{ $isSaved ? __('community.saved') : __('community.save') }}</span>
        </button>
        <button type="button" class="community-share inline-flex items-center justify-center gap-1.5 min-h-10 rounded-lg text-xs sm:text-[13px] text-[#64748b] dark:text-[#A1A09A] hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            <span class="hidden sm:inline">{{ __('community.share') }}</span>
        </button>
    </div>
</article>
