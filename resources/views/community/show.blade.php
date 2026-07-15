@extends($layout)

@section('title', __('community.title'))
@section('header_title', __('community.title'))

@section('content')
@php
    $userName = trim((string) ($currentUser->name ?? ''));
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
        'comment_error' => __('community.errors.comment'),
        'empty_post' => __('community.errors.empty_post'),
        'show_more' => __('community.show_more'),
        'show_less' => __('community.show_less'),
        'saved_label' => __('community.saved'),
        'save_label' => __('community.save'),
        'menu_save' => __('community.menu_save'),
        'menu_unsave' => __('community.menu_unsave'),
        'unsaved_title' => __('community.unsaved_title'),
    ];
@endphp

<div class="community-shell max-w-[720px] mx-auto" id="community-app"
     data-csrf="{{ csrf_token() }}"
     data-store-url="{{ route('community.posts.store') }}"
     data-max-images="{{ $maxImages }}"
     data-max-kb="{{ $maxImageKb }}"
     data-max-text="{{ $maxText }}"
     data-i18n="{{ json_encode($communityI18n, JSON_UNESCAPED_UNICODE) }}">

    <div class="mb-4">
        @include('partials.back-link', [
            'fallback' => route('community.index', array_filter([
                'tab' => request('tab'),
                'q' => request('q'),
                'category' => request('category'),
            ])),
            'label' => __('community.back_community'),
            'icon' => true,
        ])
    </div>

    <div id="community-feed-list">
        @include('community.partials.card', ['post' => $post, 'currentUser' => $currentUser])
    </div>

    <section id="comments" class="mt-3.5">
        <form id="community-comment-form"
            class="rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-4 py-4 sm:px-5"
            data-post-id="{{ $post->id }}">
            <input type="hidden" name="parent_id" id="community-reply-parent" value="">

            <div id="community-reply-banner" class="hidden mb-2.5 flex items-center justify-between gap-2 text-xs text-[#64748b] dark:text-[#A1A09A]">
                <span id="community-reply-label">{{ __('community.reply') }}</span>
                <button type="button" id="community-cancel-reply" class="text-[#f59e0b] hover:underline">{{ __('community.cancel') }}</button>
            </div>

            <div class="flex items-start gap-2.5 sm:gap-3">
                <div class="shrink-0 mt-0.5">
                    @include('community.partials.avatar', ['name' => $userName, 'size' => 'w-9 h-9 sm:w-10 sm:h-10', 'textSize' => 'text-xs'])
                </div>

                <div class="flex-1 min-w-0">
                    <div class="community-comment-field relative rounded-[14px] border border-[#7c8799]/45 dark:border-[#3E3E3A] bg-[#F8FAFC] dark:bg-[#1c1c1a] focus-within:border-[#f59e0b]/55 transition-colors">
                        <textarea
                            name="text"
                            id="community-comment-text"
                            rows="1"
                            maxlength="{{ $maxComment }}"
                            placeholder="{{ __('community.comment_placeholder') }}"
                            class="block w-full resize-none bg-transparent border-0 outline-none text-sm leading-5 text-[#0f172a] dark:text-[#EDEDEC] placeholder:text-[#94a3b8] dark:placeholder:text-[#71717a] pl-3.5 pr-12 pt-3 pb-3 min-h-[46px] max-h-[140px] overflow-y-auto"
                        ></textarea>
                        <button
                            type="submit"
                            id="community-comment-submit"
                            disabled
                            aria-label="{{ __('community.send_comment_aria') }}"
                            class="absolute right-1.5 bottom-1.5 inline-flex items-center justify-center w-10 h-10 sm:w-9 sm:h-9 rounded-full bg-[#f59e0b] text-white transition-opacity disabled:opacity-35 disabled:cursor-not-allowed disabled:bg-[#64748b] dark:disabled:bg-[#3E3E3A] hover:enabled:bg-[#d97706] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#f59e0b]/50"
                        >
                            <svg data-send-icon class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                            <svg data-spinner-icon class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                            </svg>
                        </button>
                    </div>
                    <p id="community-comment-hint" class="hidden mt-1.5 text-[11px] text-[#94a3b8] dark:text-[#71717a]">{{ __('community.comment_shortcut_hint') }}</p>
                </div>
            </div>
        </form>

        <div id="community-comments-list" class="mt-3 rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-4 py-2 sm:px-5">
            @forelse ($comments as $comment)
                @include('community.partials.comment', ['comment' => $comment, 'currentUser' => $currentUser, 'post' => $post])
            @empty
                <p class="text-sm text-[#64748b] dark:text-[#A1A09A] py-3" id="community-comments-empty">{{ __('community.comments_empty') }}</p>
            @endforelse
        </div>
    </section>

    @if ($authorPosts->isNotEmpty())
        <section class="mt-6">
            <h2 class="text-base font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-3">{{ __('community.author_posts') }}</h2>
            @foreach ($authorPosts as $ap)
                @include('community.partials.card', ['post' => $ap, 'currentUser' => $currentUser])
            @endforeach
        </section>
    @endif

    @include('community.partials.modals', [
        'currentUser' => $currentUser,
        'categories' => $categories,
        'maxImages' => $maxImages,
        'maxText' => $maxText,
    ])
</div>

@include('community.partials.scripts')

@php
    $commentJs = [
        'error' => __('community.errors.comment'),
        'comments_count_tpl' => trans_choice('community.comments_count', 1, ['count' => ':count']),
    ];
@endphp
<script>
(function () {
    const form = document.getElementById('community-comment-form');
    if (!form) return;

    const commentJs = @json($commentJs);
    const csrf = document.getElementById('community-app').dataset.csrf;
    const list = document.getElementById('community-comments-list');
    const parentInput = document.getElementById('community-reply-parent');
    const replyBanner = document.getElementById('community-reply-banner');
    const cancelReply = document.getElementById('community-cancel-reply');
    const text = document.getElementById('community-comment-text');
    const submitBtn = document.getElementById('community-comment-submit');
    const hint = document.getElementById('community-comment-hint');
    const sendIcon = submitBtn?.querySelector('[data-send-icon]');
    const spinnerIcon = submitBtn?.querySelector('[data-spinner-icon]');
    const postId = form.dataset.postId;
    let submitting = false;

    function autosize() {
        text.style.height = '46px';
        const next = Math.min(Math.max(text.scrollHeight, 46), 140);
        text.style.height = next + 'px';
        text.style.overflowY = text.scrollHeight > 140 ? 'auto' : 'hidden';
    }

    function syncSubmitState() {
        const hasText = (text.value || '').trim().length > 0;
        submitBtn.disabled = !hasText || submitting;
    }

    function setSubmitting(state) {
        submitting = state;
        syncSubmitState();
        sendIcon?.classList.toggle('hidden', state);
        spinnerIcon?.classList.toggle('hidden', !state);
    }

    function clearReply() {
        parentInput.value = '';
        replyBanner?.classList.add('hidden');
    }

    text.addEventListener('input', () => {
        autosize();
        syncSubmitState();
    });

    text.addEventListener('focus', () => hint?.classList.remove('hidden'));
    text.addEventListener('blur', () => {
        if (!(text.value || '').trim()) hint?.classList.add('hidden');
    });

    text.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            if (!submitBtn.disabled) form.requestSubmit();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (submitting) return;

        const value = (text.value || '').trim();
        if (!value) return;

        setSubmitting(true);
        const fd = new FormData(form);
        fd.set('text', value);

        try {
            const res = await fetch(`{{ url('/community/posts') }}/${postId}/comments`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || commentJs.error);

            document.getElementById('community-comments-empty')?.remove();

            if (parentInput.value) {
                const parent = list.querySelector(`[data-comment-id="${parentInput.value}"]`);
                let replies = parent?.querySelector('.border-l');
                if (parent && !replies) {
                    replies = document.createElement('div');
                    replies.className = 'mt-2 pl-3 border-l border-[#7c8799]/40 dark:border-[#3E3E3A] space-y-2';
                    parent.querySelector('.flex-1')?.appendChild(replies);
                }
                replies?.insertAdjacentHTML('beforeend', data.html);
            } else {
                list.insertAdjacentHTML('beforeend', data.html);
            }

            text.value = '';
            clearReply();
            autosize();
            syncSubmitState();
            hint?.classList.add('hidden');

            if (typeof projectAlert === 'function') projectAlert('success', data.message, '', 2500);

            const card = document.querySelector(`[data-post-id="${postId}"]`);
            if (card) {
                card.dataset.comments = String(data.comments_count);
                const stats = card.querySelector('.stat-comments');
                if (stats && data.comments_count > 0) {
                    stats.textContent = String(commentJs.comments_count_tpl || '').replace(':count', String(data.comments_count));
                }
            }
        } catch (err) {
            if (typeof projectAlert === 'function') projectAlert('error', err.message || commentJs.error, '', 2800);
        } finally {
            setSubmitting(false);
        }
    });

    document.addEventListener('click', async (e) => {
        const replyBtn = e.target.closest('.community-reply-btn');
        if (replyBtn) {
            const comment = replyBtn.closest('.community-comment');
            parentInput.value = comment?.dataset.commentId || '';
            replyBanner?.classList.remove('hidden');
            text.focus();
            return;
        }

        if (e.target.closest('#community-cancel-reply')) {
            clearReply();
            return;
        }

        const del = e.target.closest('.community-delete-comment');
        if (del) {
            const comment = del.closest('.community-comment');
            const id = comment?.dataset.commentId;
            if (!id) return;
            try {
                const res = await fetch(`{{ url('/community/comments') }}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                if (!res.ok || !data.ok) throw new Error(data.message);
                comment.remove();
                if (typeof projectAlert === 'function') projectAlert('success', data.message, '', 2500);
            } catch (err) {
                if (typeof projectAlert === 'function') projectAlert('error', err.message, '', 2800);
            }
            return;
        }

        const edit = e.target.closest('.community-edit-comment');
        if (edit) {
            const comment = edit.closest('.community-comment');
            const textEl = comment.querySelector('.community-comment-text');
            const next = prompt(textEl.textContent.trim());
            if (next === null || !next.trim()) return;
            try {
                const res = await fetch(`{{ url('/community/comments') }}/${comment.dataset.commentId}`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ text: next.trim() }),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) throw new Error(data.message);
                textEl.textContent = data.text;
                if (typeof projectAlert === 'function') projectAlert('success', data.message, '', 2500);
            } catch (err) {
                if (typeof projectAlert === 'function') projectAlert('error', err.message, '', 2800);
            }
        }
    });

    autosize();
    syncSubmitState();
})();
</script>
@endsection
