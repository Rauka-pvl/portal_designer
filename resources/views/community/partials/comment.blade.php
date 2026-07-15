@php
    $isOwn = (int) ($comment->user_id) === (int) ($currentUser->id ?? 0);
@endphp
<div class="community-comment py-3" data-comment-id="{{ $comment->id }}">
    <div class="flex items-start gap-3">
        <a href="{{ route('community.profile', $comment->author->id) }}" class="shrink-0">
            @include('community.partials.avatar', ['name' => $comment->author->name ?? '', 'size' => 'w-8 h-8', 'textSize' => 'text-xs'])
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <a href="{{ route('community.profile', $comment->author->id) }}" class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] hover:text-[#f59e0b]">{{ $comment->author->name }}</a>
                    <span class="text-xs text-[#94a3b8] dark:text-[#71717a] ml-2">{{ optional($comment->created_at)->diffForHumans() }}</span>
                </div>
                @if ($isOwn)
                    <div class="flex items-center gap-1">
                        <button type="button" class="community-edit-comment text-xs text-[#64748b] hover:text-[#f59e0b] px-2 py-1">{{ __('community.menu_edit') }}</button>
                        <button type="button" class="community-delete-comment text-xs text-red-600 dark:text-red-400 px-2 py-1">{{ __('community.menu_delete') }}</button>
                    </div>
                @endif
            </div>
            <div class="community-comment-text mt-1 text-sm text-[#0f172a] dark:text-[#EDEDEC] whitespace-pre-wrap break-words">{{ $comment->text }}</div>
            @if (!($comment->parent_id))
                <button type="button" class="community-reply-btn mt-1 text-xs text-[#f59e0b] hover:underline">{{ __('community.reply') }}</button>
            @endif

            @if (($comment->replies ?? collect())->count())
                <div class="mt-2 pl-3 border-l border-[#7c8799]/40 dark:border-[#3E3E3A] space-y-2">
                    @foreach ($comment->replies as $reply)
                        @include('community.partials.comment', ['comment' => $reply, 'currentUser' => $currentUser, 'post' => $post])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
