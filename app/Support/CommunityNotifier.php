<?php

namespace App\Support;

use App\Models\CommunityPost;
use App\Models\CommunityPostComment;
use App\Models\CommunityPostLike;
use App\Models\User;
use App\Models\UserNotification;

class CommunityNotifier
{
    public static function liked(CommunityPost $post, User $actor): void
    {
        if ((int) $post->user_id === (int) $actor->id) {
            return;
        }

        $window = (int) config('community.like_notify_window_minutes', 60);
        $existing = UserNotification::query()
            ->where('user_id', $post->user_id)
            ->where('related_post_id', $post->id)
            ->where('action_key', 'community_like')
            ->where('created_at', '>=', now()->subMinutes($window))
            ->orderByDesc('id')
            ->first();

        $recentActors = CommunityPostLike::query()
            ->where('community_post_id', $post->id)
            ->where('created_at', '>=', now()->subMinutes($window))
            ->where('user_id', '!=', $post->user_id)
            ->orderByDesc('id')
            ->limit(10)
            ->with('user:id,name')
            ->get();

        $first = $recentActors->first()?->user?->name ?: $actor->name;
        $others = max(0, $recentActors->count() - 1);

        $comment = $others > 0
            ? __('notifications.community_like_comment_grouped', ['name' => $first, 'count' => $others])
            : __('notifications.community_like_comment', ['name' => $first]);

        if ($existing) {
            $existing->comment = $comment;
            $existing->is_read = false;
            $existing->read_at = null;
            $existing->save();

            return;
        }

        UserNotification::query()->create([
            'user_id' => $post->user_id,
            'title' => __('notifications.community_like_title'),
            'comment' => $comment,
            'is_read' => false,
            'related_post_id' => $post->id,
            'action_key' => 'community_like',
        ]);
    }

    public static function commented(CommunityPost $post, User $actor, CommunityPostComment $comment): void
    {
        $recipientId = (int) $post->user_id;
        if ($comment->parent_id) {
            $parent = CommunityPostComment::query()->find($comment->parent_id);
            if ($parent) {
                $recipientId = (int) $parent->user_id;
            }
        }

        if ($recipientId === (int) $actor->id) {
            return;
        }

        $isReply = (bool) $comment->parent_id;

        UserNotification::query()->create([
            'user_id' => $recipientId,
            'title' => $isReply
                ? __('notifications.community_reply_title')
                : __('notifications.community_comment_title'),
            'comment' => $isReply
                ? __('notifications.community_reply_comment', ['name' => $actor->name])
                : __('notifications.community_comment_comment', ['name' => $actor->name]),
            'is_read' => false,
            'related_post_id' => $post->id,
            'action_key' => $isReply ? 'community_reply' : 'community_comment',
        ]);
    }
}
