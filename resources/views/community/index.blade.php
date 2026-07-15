@extends($layout)

@section('title', __('community.title'))
@section('header_title', '')

@push('styles')
<style>
    .community-shell {
        max-width: 1140px;
        margin: 0 auto;
        width: 100%;
    }
    .community-feed {
        max-width: 740px;
        width: 100%;
        flex: 1 1 auto;
        min-width: 0;
    }
    .community-side {
        width: 300px;
        flex: 0 0 300px;
    }
    .community-tabs {
        overflow: visible;
        max-height: none;
    }
    .community-text.is-clamped {
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .community-media-single {
        width: 100%;
        max-height: 420px;
        border-radius: 14px;
        background: #0a0a0a;
        overflow: hidden;
    }
    .community-media-single img {
        width: 100%;
        max-height: 420px;
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }
    @media (min-width: 768px) {
        .community-media-single,
        .community-media-single img { max-height: 480px; }
    }
    @media (min-width: 1024px) {
        .community-media-single,
        .community-media-single img { max-height: 560px; }
        .community-side {
            position: sticky;
            top: 72px;
            align-self: flex-start;
        }
    }
    @media (max-width: 1023px) {
        .community-side { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $userName = trim((string) ($currentUser->name ?? ''));
    $activeCategory = request('category');
    $q = request('q');
    $communityI18n = [
        'created' => __('community.toasts.created'),
        'updated' => __('community.toasts.updated'),
        'deleted' => __('community.toasts.deleted'),
        'saved' => __('community.toasts.saved'),
        'unsaved' => __('community.toasts.unsaved'),
        'reported' => __('community.toasts.reported'),
        'hidden' => __('community.toasts.hidden'),
        'link_copied' => __('community.link_copied'),
        'publish_error' => __('community.errors.publish'),
        'like_error' => __('community.errors.like'),
        'save_error' => __('community.errors.save'),
        'empty_post' => __('community.errors.empty_post'),
        'show_more' => __('community.show_more'),
        'show_less' => __('community.show_less'),
        'like' => __('community.like'),
        'saved_label' => __('community.saved'),
        'save_label' => __('community.save'),
        'menu_save' => __('community.menu_save'),
        'menu_unsave' => __('community.menu_unsave'),
        'unsaved_title' => __('community.unsaved_title'),
    ];
    $sidebarCategories = ['new_arrival', 'project', 'work', 'idea', 'useful'];
@endphp

<div class="community-shell" id="community-app"
     data-csrf="{{ csrf_token() }}"
     data-store-url="{{ route('community.posts.store') }}"
     data-max-images="{{ $maxImages }}"
     data-max-kb="{{ $maxImageKb }}"
     data-max-text="{{ $maxText }}"
     data-i18n="{{ json_encode($communityI18n, JSON_UNESCAPED_UNICODE) }}">

    <div class="flex items-start sm:items-center justify-between gap-3 mb-4">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('community.title') }}</h1>
            <p class="text-[13px] sm:text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('community.subtitle') }}</p>
        </div>
        <button type="button" id="community-open-create"
            class="shrink-0 inline-flex items-center gap-2 h-10 sm:h-11 px-3 sm:px-4 rounded-xl bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706] focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#f59e0b]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="hidden sm:inline">{{ __('community.create') }}</span>
        </button>
    </div>

    {{-- Tabs: no overflow scroll --}}
    <nav class="community-tabs mb-4" aria-label="{{ __('community.title') }}">
        <div class="sm:hidden grid grid-cols-3 gap-1 p-1 rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-[#F8FAFC] dark:bg-[#0a0a0a]">
            @foreach (['all', 'my', 'saved'] as $key)
                @php
                    $count = $key === 'my' ? (int) $myCount : ($key === 'saved' ? (int) $savedCount : 0);
                    $isActive = $tab === $key;
                @endphp
                <a href="{{ route('community.index', array_filter(['tab' => $key, 'q' => $q, 'category' => $activeCategory])) }}"
                   class="min-w-0 inline-flex items-center justify-center gap-1 h-9 px-1.5 rounded-lg text-[12px] font-medium transition-colors {{ $isActive ? 'bg-[#f59e0b] text-white' : 'text-[#64748b] dark:text-[#A1A09A]' }}"
                   aria-current="{{ $isActive ? 'page' : 'false' }}">
                    <span class="truncate">{{ __('community.tabs_short.'.$key) }}</span>
                    @if ($count > 0)
                        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-semibold tabular-nums {{ $isActive ? 'bg-white/20 text-white' : 'bg-[#f59e0b]/15 text-[#f59e0b]' }}">{{ $count }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="hidden sm:flex items-end gap-0.5 border-b border-[#7c8799]/40 dark:border-[#3E3E3A]">
            @foreach (['all', 'my', 'saved'] as $key)
                @php
                    $count = $key === 'my' ? (int) $myCount : ($key === 'saved' ? (int) $savedCount : 0);
                    $isActive = $tab === $key;
                @endphp
                <a href="{{ route('community.index', array_filter(['tab' => $key, 'q' => $q, 'category' => $activeCategory])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2.5 text-sm border-b-2 -mb-px transition-colors {{ $isActive ? 'border-[#f59e0b] text-[#f59e0b]' : 'border-transparent text-[#94a3b8] dark:text-[#A1A09A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC]' }}"
                   aria-current="{{ $isActive ? 'page' : 'false' }}">
                    <span>{{ __('community.tabs.'.$key) }}</span>
                    @if ($count > 0)
                        <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-semibold tabular-nums {{ $isActive ? 'bg-[#f59e0b]/15 text-[#f59e0b]' : 'bg-[#F1F5F9] dark:bg-[#0a0a0a] text-[#64748b] dark:text-[#A1A09A]' }}">{{ $count }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>

    <div class="flex gap-5 lg:gap-6 items-start">
        <div class="community-feed">
            @if ($tab === 'all')
                <div class="mb-3.5 rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3.5 py-3">
                    <button type="button" id="community-composer-open"
                        class="w-full flex items-center gap-2.5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-[#f59e0b]/40 rounded-lg">
                        @include('community.partials.avatar', ['name' => $userName, 'size' => 'w-9 h-9', 'textSize' => 'text-xs'])
                        <span class="flex-1 min-w-0 text-[13px] sm:text-sm text-[#94a3b8] dark:text-[#71717a] truncate">{{ __('community.composer_placeholder') }}</span>
                    </button>
                    <div class="mt-2.5 flex items-center justify-between gap-2 pl-[46px]">
                        <button type="button" id="community-composer-photo"
                            class="inline-flex items-center gap-1.5 h-9 px-2.5 rounded-lg text-[12px] text-[#64748b] dark:text-[#A1A09A] hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a] transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ __('community.composer_photo') }}
                        </button>
                        <button type="button" id="community-composer-publish"
                            class="inline-flex items-center h-9 px-3 rounded-lg bg-[#f59e0b]/15 text-[#f59e0b] text-[12px] font-medium hover:bg-[#f59e0b]/25 transition-colors">
                            {{ __('community.composer_publish') }}
                        </button>
                    </div>
                </div>

                <form method="GET" action="{{ route('community.index') }}" class="mb-3.5 flex gap-2">
                    <input type="hidden" name="tab" value="all">
                    @if ($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory }}">
                    @endif
                    <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('community.search_placeholder') }}"
                           class="flex-1 h-10 px-3 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-sm text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-[#f59e0b]/40">
                </form>

                @if ($activeCategory)
                    <div class="mb-3 flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full border border-[#f59e0b]/40 text-[#f59e0b] bg-[#f59e0b]/10">
                            {{ __('community.categories.'.$activeCategory) }}
                        </span>
                        <a href="{{ route('community.index', array_filter(['tab' => 'all', 'q' => $q])) }}"
                           class="text-xs text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b] underline-offset-2 hover:underline">
                            {{ __('community.clear_category') }}
                        </a>
                    </div>
                @endif
            @endif

            <div id="community-feed-list">
                @forelse ($posts as $post)
                    @include('community.partials.card', ['post' => $post, 'currentUser' => $currentUser])
                @empty
                    <div class="rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-10 text-center">
                        @if ($tab === 'my')
                            <h2 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('community.empty_my_title') }}</h2>
                            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-2">{{ __('community.empty_my_text') }}</p>
                            <button type="button" class="community-open-create-btn mt-5 h-11 px-4 rounded-xl bg-[#f59e0b] text-white text-sm">{{ __('community.create') }}</button>
                        @elseif ($tab === 'saved')
                            <h2 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('community.empty_saved_title') }}</h2>
                            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-2">{{ __('community.empty_saved_text') }}</p>
                            <a href="{{ route('community.index', ['tab' => 'all']) }}" class="inline-flex mt-5 h-11 px-4 items-center rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] text-sm">{{ __('community.go_feed') }}</a>
                        @else
                            <h2 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('community.empty_all_title') }}</h2>
                            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-2">{{ __('community.empty_all_text') }}</p>
                            <button type="button" class="community-open-create-btn mt-5 h-11 px-4 rounded-xl bg-[#f59e0b] text-white text-sm">{{ __('community.create') }}</button>
                        @endif
                    </div>
                @endforelse
            </div>

            @if ($posts->hasPages())
                <div class="mt-6">{{ $posts->links() }}</div>
            @endif
        </div>

        <aside class="community-side space-y-4">
            @if (($recommended ?? collect())->isNotEmpty())
                <section class="rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
                    <h2 class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-3">{{ __('community.sidebar_recommended') }}</h2>
                    <div class="space-y-3">
                        @foreach ($recommended as $u)
                            <a href="{{ route('community.profile', $u->id) }}" class="flex items-center gap-3 hover:opacity-90">
                                @include('community.partials.avatar', ['name' => $u->name, 'size' => 'w-9 h-9', 'textSize' => 'text-xs'])
                                <div class="min-w-0">
                                    <div class="text-sm text-[#0f172a] dark:text-[#EDEDEC] truncate">{{ $u->name }}</div>
                                    <div class="text-xs text-[#94a3b8] dark:text-[#A1A09A] truncate">{{ __('community.roles.'.$u->role) }}@if($u->city) · {{ $u->city }}@endif</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h2 class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('community.sidebar_categories') }}</h2>
                    @if ($activeCategory)
                        <a href="{{ route('community.index', array_filter(['tab' => 'all', 'q' => $q])) }}" class="text-[11px] text-[#f59e0b] hover:underline">{{ __('community.clear_category') }}</a>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($sidebarCategories as $cat)
                        <a href="{{ route('community.index', ['tab' => 'all', 'category' => $cat, 'q' => $q]) }}"
                           class="text-xs px-2.5 py-1 rounded-full border transition-colors {{ $activeCategory === $cat ? 'border-[#f59e0b] text-[#f59e0b] bg-[#f59e0b]/10' : 'border-[#7c8799]/40 dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b]' }}">
                            {{ __('community.categories.'.$cat) }}
                        </a>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>

    @include('community.partials.modals', [
        'currentUser' => $currentUser,
        'categories' => $categories,
        'maxImages' => $maxImages,
        'maxText' => $maxText,
    ])
</div>

@include('community.partials.scripts')
<script>
(function () {
    const open = () => document.getElementById('community-open-create')?.click();
    document.getElementById('community-composer-photo')?.addEventListener('click', open);
    document.getElementById('community-composer-publish')?.addEventListener('click', open);
})();
</script>
@endsection
