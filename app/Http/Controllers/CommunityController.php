<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use App\Models\CommunityPostComment;
use App\Models\CommunityPostHide;
use App\Models\CommunityPostLike;
use App\Models\CommunityPostMedia;
use App\Models\CommunityPostReport;
use App\Models\CommunityPostSave;
use App\Models\User;
use App\Support\CommunityNotifier;
use App\Support\PublicFileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['all', 'my', 'saved'], true)
            ? $request->query('tab')
            : 'all';

        $myCount = CommunityPost::query()
            ->where('user_id', $user->id)
            ->where('status', CommunityPost::STATUS_PUBLISHED)
            ->count();

        $savedCount = $this->savedPostsCount((int) $user->id);

        $posts = $this->feedQuery($request, $tab)
            ->paginate((int) config('community.posts_per_page', 10))
            ->withQueryString();

        $this->hydrateViewerState($posts->getCollection(), $user->id);

        return view('community.index', [
            'layout' => $this->layoutFor($user),
            'tab' => $tab,
            'posts' => $posts,
            'myCount' => $myCount,
            'savedCount' => $savedCount,
            'categories' => CommunityPost::CATEGORIES,
            'maxImages' => (int) config('community.max_images', 10),
            'maxImageKb' => (int) config('community.max_image_kb', 5120),
            'maxText' => (int) config('community.max_text_length', 2000),
            'currentUser' => $user,
            'recommended' => $this->recommendedUsers($user->id),
        ]);
    }

    public function show(Request $request, int $postId): View
    {
        $user = $request->user();
        $post = CommunityPost::query()
            ->with([
                'author:id,name,role,city',
                'author.supplierProfile:id,user_id,name,city,logo',
                'media',
            ])
            ->published()
            ->whereKey($postId)
            ->first();

        if (! $post) {
            return view('community.not-found', [
                'layout' => $this->layoutFor($user),
            ]);
        }

        $this->recordView($request, $post);

        $this->hydrateViewerState(collect([$post]), $user->id);

        $comments = CommunityPostComment::query()
            ->with([
                'author:id,name,role,city',
                'replies' => fn ($q) => $q->with('author:id,name,role,city')->orderBy('id'),
            ])
            ->where('community_post_id', $post->id)
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();

        $authorPosts = CommunityPost::query()
            ->with(['author:id,name,role,city', 'media'])
            ->published()
            ->where('user_id', $post->user_id)
            ->where('id', '!=', $post->id)
            ->latest()
            ->limit(5)
            ->get();

        $this->hydrateViewerState($authorPosts, $user->id);

        return view('community.show', [
            'layout' => $this->layoutFor($user),
            'post' => $post,
            'comments' => $comments,
            'authorPosts' => $authorPosts,
            'categories' => CommunityPost::CATEGORIES,
            'maxImages' => (int) config('community.max_images', 10),
            'maxImageKb' => (int) config('community.max_image_kb', 5120),
            'maxText' => (int) config('community.max_text_length', 2000),
            'maxComment' => (int) config('community.max_comment_length', 1000),
            'currentUser' => $user,
        ]);
    }

    public function profile(Request $request, int $userId): View
    {
        $viewer = $request->user();
        $author = User::query()
            ->with(['designerProfile', 'supplierProfile'])
            ->whereKey($userId)
            ->whereIn('role', ['designer', 'supplier'])
            ->firstOrFail();

        $isOwner = (int) $viewer->id === (int) $author->id;
        $layout = $this->layoutFor($viewer);

        // From community: reuse the regular profile UI, without referral link.
        if (($author->role ?? '') === 'supplier') {
            return view('supplier.profile.show', [
                'layout' => $layout,
                'user' => $author,
                'supplier' => $author->supplierProfile,
                'isOwner' => $isOwner,
                'isPublicView' => true,
                'referralSupplierUrl' => null,
            ]);
        }

        return view('designer.profile.show', [
            'layout' => $layout,
            'user' => $author,
            'profile' => $author->designerProfile,
            'isOwner' => $isOwner,
            'isPublicView' => true,
            'referralSupplierUrl' => null,
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['all', 'my', 'saved'], true)
            ? $request->query('tab')
            : 'all';

        $myCount = CommunityPost::query()
            ->where('user_id', $user->id)
            ->where('status', CommunityPost::STATUS_PUBLISHED)
            ->count();

        $savedCount = $this->savedPostsCount((int) $user->id);

        $posts = $this->feedQuery($request, $tab)
            ->paginate((int) config('community.posts_per_page', 10))
            ->withQueryString();

        $this->hydrateViewerState($posts->getCollection(), $user->id);

        return response()->json([
            'success' => true,
            'tab' => $tab,
            'my_count' => $myCount,
            'saved_count' => $savedCount,
            'categories' => CommunityPost::CATEGORIES,
            'limits' => $this->communityLimits(),
            'recommended' => $this->recommendedUsers($user->id)
                ->map(fn (User $u) => $this->serializeRecommendedUser($u))
                ->values(),
            'posts' => $posts->getCollection()
                ->map(fn (CommunityPost $post) => $this->serializePost($post, (int) $user->id))
                ->values(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function apiShow(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        $post = CommunityPost::query()
            ->with([
                'author:id,name,role,city',
                'author.supplierProfile:id,user_id,name,city,logo',
                'media',
            ])
            ->published()
            ->whereKey($postId)
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => __('community.not_found_title'),
            ], 404);
        }

        $this->recordView($request, $post);
        $this->hydrateViewerState(collect([$post]), $user->id);

        $comments = CommunityPostComment::query()
            ->with([
                'author:id,name,role,city',
                'replies' => fn ($q) => $q->with('author:id,name,role,city')->orderBy('id'),
            ])
            ->where('community_post_id', $post->id)
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();

        $authorPosts = CommunityPost::query()
            ->with(['author:id,name,role,city', 'media'])
            ->published()
            ->where('user_id', $post->user_id)
            ->where('id', '!=', $post->id)
            ->latest()
            ->limit(5)
            ->get();

        $this->hydrateViewerState($authorPosts, $user->id);

        return response()->json([
            'success' => true,
            'post' => $this->serializePost($post, (int) $user->id),
            'comments' => $comments
                ->map(fn (CommunityPostComment $comment) => $this->serializeComment($comment, (int) $user->id))
                ->values(),
            'author_posts' => $authorPosts
                ->map(fn (CommunityPost $p) => $this->serializePost($p, (int) $user->id))
                ->values(),
            'limits' => $this->communityLimits(),
        ]);
    }

    public function apiComments(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        $post = CommunityPost::query()
            ->published()
            ->whereKey($postId)
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => __('community.not_found_title'),
            ], 404);
        }

        $comments = CommunityPostComment::query()
            ->with([
                'author:id,name,role,city',
                'replies' => fn ($q) => $q->with('author:id,name,role,city')->orderBy('id'),
            ])
            ->where('community_post_id', $post->id)
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments
                ->map(fn (CommunityPostComment $comment) => $this->serializeComment($comment, (int) $user->id))
                ->values(),
            'comments_count' => (int) $post->comments_count,
        ]);
    }

    public function apiProfile(Request $request, int $userId): JsonResponse
    {
        $viewer = $request->user();
        $author = User::query()
            ->with(['designerProfile', 'supplierProfile'])
            ->whereKey($userId)
            ->whereIn('role', ['designer', 'supplier'])
            ->firstOrFail();

        $posts = CommunityPost::query()
            ->with([
                'author:id,name,role,city',
                'author.supplierProfile:id,user_id,name,city,logo',
                'media',
            ])
            ->published()
            ->where('user_id', $author->id)
            ->latest('id')
            ->paginate((int) config('community.posts_per_page', 10))
            ->withQueryString();

        $this->hydrateViewerState($posts->getCollection(), (int) $viewer->id);

        return response()->json([
            'success' => true,
            'user' => $this->serializeProfileUser($author, (int) $viewer->id),
            'posts_count' => CommunityPost::query()
                ->published()
                ->where('user_id', $author->id)
                ->count(),
            'posts' => $posts->getCollection()
                ->map(fn (CommunityPost $post) => $this->serializePost($post, (int) $viewer->id))
                ->values(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $this->attempt('community-post:'.$user->id, 10, 60)) {
            return $this->error(__('community.errors.rate_limited'), 429);
        }

        $data = $this->validatePost($request);
        if (! filled($data['text'] ?? null) && ! $request->hasFile('images')) {
            return $this->error(__('community.errors.empty_post'), 422);
        }

        $post = DB::transaction(function () use ($request, $user, $data) {
            $post = CommunityPost::query()->create([
                'user_id' => $user->id,
                'text' => $this->sanitizeText($data['text'] ?? null),
                'category' => $data['category'] ?? null,
                'city' => $data['city'] ?? ($user->city ?: null),
                'status' => CommunityPost::STATUS_PUBLISHED,
                'visibility' => CommunityPost::VISIBILITY_PUBLIC,
            ]);

            $this->syncMedia($request, $post);

            return $post;
        });

        $post->load(['author:id,name,role,city', 'author.supplierProfile:id,user_id,name,city,logo', 'media']);
        $this->hydrateViewerState(collect([$post]), $user->id);

        $payload = [
            'ok' => true,
            'success' => true,
            'message' => __('community.toasts.created'),
            'post' => $this->serializePost($post, $user->id),
        ];

        if (! $this->isApiRequest($request)) {
            $payload['html'] = view('community.partials.card', [
                'post' => $post,
                'currentUser' => $user,
                'backFrom' => route('community.index'),
            ])->render();
        }

        return response()->json($payload);
    }

    public function update(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        $post = CommunityPost::query()->whereKey($postId)->firstOrFail();

        if ((int) $post->user_id !== (int) $user->id && ($user->role ?? '') !== 'moderator') {
            return $this->error(__('community.errors.forbidden'), 403);
        }

        $data = $this->validatePost($request, true);
        $keepMediaIds = collect($request->input('keep_media_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $willHaveMedia = count($keepMediaIds) > 0 || $request->hasFile('images');
        if (! filled($data['text'] ?? null) && ! $willHaveMedia) {
            return $this->error(__('community.errors.empty_post'), 422);
        }

        DB::transaction(function () use ($request, $post, $data, $keepMediaIds) {
            $post->update([
                'text' => $this->sanitizeText($data['text'] ?? null),
                'category' => $data['category'] ?? null,
                'city' => $data['city'] ?? $post->city,
            ]);

            $toDelete = $post->media()->whereNotIn('id', $keepMediaIds)->get();
            foreach ($toDelete as $media) {
                Storage::disk('public')->delete($media->file_path);
                $media->delete();
            }

            $this->syncMedia($request, $post, count($keepMediaIds));
        });

        $post->refresh()->load(['author:id,name,role,city', 'author.supplierProfile:id,user_id,name,city,logo', 'media']);
        $this->hydrateViewerState(collect([$post]), $user->id);

        $payload = [
            'ok' => true,
            'message' => __('community.toasts.updated'),
            'post' => $this->serializePost($post, $user->id),
        ];

        if (! $this->isApiRequest($request)) {
            $payload['html'] = view('community.partials.card', [
                'post' => $post,
                'currentUser' => $user,
                'backFrom' => route('community.index'),
            ])->render();
        }

        return response()->json($payload);
    }

    public function destroy(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        $post = CommunityPost::query()->with('media')->whereKey($postId)->firstOrFail();

        if ((int) $post->user_id !== (int) $user->id && ($user->role ?? '') !== 'moderator') {
            return $this->error(__('community.errors.forbidden'), 403);
        }

        DB::transaction(function () use ($post) {
            if ($post->media) {
                foreach ($post->media as $media) {
                    if (is_string($media->file_path) && $media->file_path !== '') {
                        Storage::disk('public')->delete($media->file_path);
                    }
                }
            }
            // Soft-delete does not cascade FKs — clear viewer state so saved/liked lists stay correct.
            CommunityPostSave::query()->where('community_post_id', $post->id)->delete();
            CommunityPostLike::query()->where('community_post_id', $post->id)->delete();
            CommunityPostHide::query()->where('community_post_id', $post->id)->delete();
            $post->delete();
        });

        return response()->json([
            'ok' => true,
            'success' => true,
            'message' => __('community.toasts.deleted'),
        ]);
    }

    public function toggleLike(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        if (! $this->attempt('community-like:'.$user->id, 60, 60)) {
            return $this->error(__('community.errors.rate_limited'), 429);
        }

        $wantLiked = $request->has('liked') ? $request->boolean('liked') : null;
        if ($request->isMethod('DELETE')) {
            $wantLiked = false;
        }

        $existing = CommunityPostLike::query()
            ->where('community_post_id', $postId)
            ->where('user_id', $user->id)
            ->first();
        $currentlyLiked = $existing !== null;
        $shouldLike = $wantLiked === null ? ! $currentlyLiked : $wantLiked;

        if ($shouldLike === $currentlyLiked) {
            $post = CommunityPost::query()->withTrashed()->whereKey($postId)->first();

            return response()->json([
                'ok' => true,
                'success' => true,
                'is_liked' => $currentlyLiked,
                'likes_count' => (int) ($post?->likes_count ?? 0),
            ]);
        }

        if (! $shouldLike) {
            $existing?->delete();
            $post = CommunityPost::query()->withTrashed()->whereKey($postId)->first();
            if (
                $post
                && ! $post->trashed()
                && (string) $post->status === CommunityPost::STATUS_PUBLISHED
                && (int) $post->likes_count > 0
            ) {
                $post->decrement('likes_count');
                $post->refresh();
            }

            return response()->json([
                'ok' => true,
                'success' => true,
                'is_liked' => false,
                'likes_count' => (int) ($post?->likes_count ?? 0),
            ]);
        }

        $post = CommunityPost::query()->published()->whereKey($postId)->firstOrFail();
        CommunityPostLike::query()->create([
            'community_post_id' => $post->id,
            'user_id' => $user->id,
        ]);
        $post->increment('likes_count');
        $post->refresh();
        CommunityNotifier::liked($post, $user);

        return response()->json([
            'ok' => true,
            'success' => true,
            'is_liked' => true,
            'likes_count' => (int) $post->likes_count,
        ]);
    }

    public function toggleSave(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        $wantSaved = $request->has('saved') ? $request->boolean('saved') : null;
        // DELETE /save always means unsave (mobile clients often use DELETE).
        if ($request->isMethod('DELETE')) {
            $wantSaved = false;
        }

        $existing = CommunityPostSave::query()
            ->where('community_post_id', $postId)
            ->where('user_id', $user->id)
            ->first();

        $currentlySaved = $existing !== null;
        $shouldSave = $wantSaved === null ? ! $currentlySaved : $wantSaved;

        if ($shouldSave === $currentlySaved) {
            $post = CommunityPost::query()->withTrashed()->whereKey($postId)->first();

            return response()->json([
                'ok' => true,
                'success' => true,
                'is_saved' => $currentlySaved,
                'saves_count' => (int) ($post?->saves_count ?? 0),
                'message' => $currentlySaved ? __('community.toasts.saved') : __('community.toasts.unsaved'),
            ]);
        }

        if (! $shouldSave) {
            // Allow unsave even when post is soft-deleted / unpublished.
            $existing?->delete();
            $post = CommunityPost::query()->withTrashed()->whereKey($postId)->first();
            if (
                $post
                && ! $post->trashed()
                && (string) $post->status === CommunityPost::STATUS_PUBLISHED
                && (int) $post->saves_count > 0
            ) {
                $post->decrement('saves_count');
                $post->refresh();
            }

            return response()->json([
                'ok' => true,
                'success' => true,
                'is_saved' => false,
                'saves_count' => (int) ($post?->saves_count ?? 0),
                'message' => __('community.toasts.unsaved'),
            ]);
        }

        $post = CommunityPost::query()->published()->whereKey($postId)->firstOrFail();
        CommunityPostSave::query()->create([
            'community_post_id' => $post->id,
            'user_id' => $user->id,
        ]);
        $post->increment('saves_count');
        $post->refresh();

        return response()->json([
            'ok' => true,
            'success' => true,
            'is_saved' => true,
            'saves_count' => (int) $post->saves_count,
            'message' => __('community.toasts.saved'),
        ]);
    }

    public function storeComment(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        if (! $this->attempt('community-comment:'.$user->id, 30, 60)) {
            return $this->error(__('community.errors.rate_limited'), 429);
        }

        $post = CommunityPost::query()->published()->whereKey($postId)->firstOrFail();
        $max = (int) config('community.max_comment_length', 1000);

        if (! $request->filled('text') && $request->filled('body')) {
            $request->merge(['text' => $request->input('body')]);
        }
        if (! $request->filled('text') && $request->filled('message')) {
            $request->merge(['text' => $request->input('message')]);
        }

        $data = $request->validate([
            'text' => ['required', 'string', 'max:'.$max],
            'parent_id' => ['nullable', 'integer', 'exists:community_post_comments,id'],
        ]);

        $parentId = $data['parent_id'] ?? null;
        if ($parentId) {
            $parent = CommunityPostComment::query()
                ->whereKey($parentId)
                ->where('community_post_id', $post->id)
                ->firstOrFail();
            if ($parent->parent_id) {
                return $this->error(__('community.errors.reply_depth'), 422);
            }
        }

        $comment = CommunityPostComment::query()->create([
            'community_post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'text' => $this->sanitizeText($data['text']),
        ]);

        $post->increment('comments_count');
        $comment->load(['author:id,name,role,city', 'replies.author:id,name,role,city']);
        CommunityNotifier::commented($post, $user, $comment);

        $payload = [
            'ok' => true,
            'success' => true,
            'message' => __('community.toasts.comment_added'),
            'comments_count' => (int) $post->fresh()->comments_count,
            'comment' => $this->serializeComment($comment, (int) $user->id),
        ];

        if (! $this->isApiRequest($request)) {
            $payload['html'] = view('community.partials.comment', [
                'comment' => $comment,
                'currentUser' => $user,
                'post' => $post,
            ])->render();
        }

        return response()->json($payload);
    }

    public function updateComment(Request $request, int $commentId): JsonResponse
    {
        $user = $request->user();
        $comment = CommunityPostComment::query()->whereKey($commentId)->firstOrFail();

        if ((int) $comment->user_id !== (int) $user->id) {
            return $this->error(__('community.errors.forbidden'), 403);
        }

        $max = (int) config('community.max_comment_length', 1000);
        $data = $request->validate([
            'text' => ['required', 'string', 'max:'.$max],
        ]);

        $comment->update(['text' => $this->sanitizeText($data['text'])]);

        return response()->json([
            'ok' => true,
            'message' => __('community.toasts.comment_updated'),
            'text' => $comment->text,
            'comment' => $this->serializeComment($comment->fresh()->load('author:id,name,role,city'), (int) $user->id),
        ]);
    }

    public function destroyComment(Request $request, int $commentId): JsonResponse
    {
        $user = $request->user();
        $comment = CommunityPostComment::query()->whereKey($commentId)->firstOrFail();

        if ((int) $comment->user_id !== (int) $user->id && ($user->role ?? '') !== 'moderator') {
            return $this->error(__('community.errors.forbidden'), 403);
        }

        $post = CommunityPost::query()->find($comment->community_post_id);
        $repliesCount = $comment->replies()->count();
        $comment->replies()->delete();
        $comment->delete();

        if ($post) {
            $post->decrement('comments_count', 1 + $repliesCount);
        }

        return response()->json([
            'ok' => true,
            'message' => __('community.toasts.comment_deleted'),
            'comments_count' => (int) ($post?->fresh()->comments_count ?? 0),
        ]);
    }

    public function report(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        if (! $this->attempt('community-report:'.$user->id, 10, 60)) {
            return $this->error(__('community.errors.rate_limited'), 429);
        }

        $post = CommunityPost::query()->published()->whereKey($postId)->firstOrFail();
        if ((int) $post->user_id === (int) $user->id) {
            return $this->error(__('community.errors.report_own'), 422);
        }

        $data = $request->validate([
            'reason' => ['required', Rule::in(['spam', 'insult', 'inappropriate', 'fraud', 'copyright', 'other'])],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        CommunityPostReport::query()->updateOrCreate(
            [
                'community_post_id' => $post->id,
                'user_id' => $user->id,
            ],
            [
                'reason' => $data['reason'],
                'comment' => $this->sanitizeText($data['comment'] ?? null),
                'status' => 'pending',
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => __('community.toasts.reported'),
        ]);
    }

    public function hide(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        $post = CommunityPost::query()->published()->whereKey($postId)->firstOrFail();

        CommunityPostHide::query()->firstOrCreate([
            'community_post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        if ($request->hasSession()) {
            $hidden = $request->session()->get('community_hidden_posts', []);
            $hidden[] = $post->id;
            $request->session()->put('community_hidden_posts', array_values(array_unique($hidden)));
        }

        return response()->json([
            'ok' => true,
            'message' => __('community.toasts.hidden'),
        ]);
    }

    private function feedQuery(Request $request, string $tab)
    {
        $user = $request->user();
        $hidden = $this->hiddenPostIds($request, (int) $user->id);

        $query = CommunityPost::query()
            ->with([
                'author:id,name,role,city',
                'author.supplierProfile:id,user_id,name,city,logo',
                'media',
            ])
            ->latest('id');

        if ($tab === 'my') {
            $query->where('user_id', $user->id)
                ->where('status', CommunityPost::STATUS_PUBLISHED);
        } elseif ($tab === 'saved') {
            $query->published()
                ->whereHas('saves', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $query->published();
            if (! empty($hidden)) {
                $query->whereNotIn('id', $hidden);
            }
        }

        $category = $request->query('category');
        if ($category && in_array($category, CommunityPost::CATEGORIES, true)) {
            $query->where('category', $category);
        }

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('text', 'like', '%'.$q.'%')
                    ->orWhere('city', 'like', '%'.$q.'%')
                    ->orWhereHas('author', fn ($a) => $a->where('name', 'like', '%'.$q.'%'));
            });
        }

        return $query;
    }

    private function hydrateViewerState($posts, int $userId): void
    {
        $ids = $posts->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        $liked = CommunityPostLike::query()
            ->where('user_id', $userId)
            ->whereIn('community_post_id', $ids)
            ->pluck('community_post_id')
            ->all();

        $saved = CommunityPostSave::query()
            ->where('user_id', $userId)
            ->whereIn('community_post_id', $ids)
            ->pluck('community_post_id')
            ->all();

        $likedSet = array_fill_keys($liked, true);
        $savedSet = array_fill_keys($saved, true);

        foreach ($posts as $post) {
            $post->setAttribute('is_liked', isset($likedSet[$post->id]));
            $post->setAttribute('is_saved', isset($savedSet[$post->id]));
            $post->setAttribute('is_owner', (int) $post->user_id === $userId);
            $post->setAttribute('can_edit', (int) $post->user_id === $userId);
            $post->setAttribute('can_delete', (int) $post->user_id === $userId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePost(CommunityPost $post, int $userId): array
    {
        return [
            'id' => $post->id,
            'text' => $post->text,
            'category' => $post->category,
            'city' => $post->city,
            'likes_count' => (int) $post->likes_count,
            'comments_count' => (int) $post->comments_count,
            'saves_count' => (int) $post->user_id === $userId ? (int) $post->saves_count : null,
            'views_count' => (int) $post->user_id === $userId ? (int) $post->views_count : null,
            'is_liked' => (bool) $post->is_liked,
            'is_saved' => (bool) $post->is_saved,
            'is_owner' => (bool) $post->is_owner,
            'can_edit' => (bool) $post->can_edit,
            'can_delete' => (bool) $post->can_delete,
            'created_at' => optional($post->created_at)->toIso8601String(),
            'author' => [
                'id' => $post->author?->id,
                'name' => $post->author?->name,
                'role' => $post->author?->role,
                'city' => $post->author?->city,
            ],
            'media' => $post->media ? $post->media->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->url,
                'sort_order' => $m->sort_order,
            ])->values() : [],
        ];
    }

    private function validatePost(Request $request, bool $isUpdate = false): array
    {
        $maxImages = (int) config('community.max_images', 10);
        $maxKb = (int) config('community.max_image_kb', 5120);
        $maxText = (int) config('community.max_text_length', 2000);

        return $request->validate([
            'text' => ['nullable', 'string', 'max:'.$maxText],
            'category' => ['nullable', Rule::in(CommunityPost::CATEGORIES)],
            'city' => ['nullable', 'string', 'max:120'],
            'images' => ['nullable', 'array', 'max:'.$maxImages],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
            'keep_media_ids' => [$isUpdate ? 'nullable' : 'prohibited', 'array'],
            'keep_media_ids.*' => ['integer'],
        ]);
    }

    private function syncMedia(Request $request, CommunityPost $post, int $startOrder = 0): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $order = $startOrder;
        foreach ($request->file('images') as $file) {
            if (! $file) {
                continue;
            }
            $path = PublicFileStorage::store($file, 'community');
            $size = @getimagesize($file->getPathname());
            CommunityPostMedia::query()->create([
                'community_post_id' => $post->id,
                'file_path' => $path,
                'file_type' => 'image',
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
                'sort_order' => $order++,
            ]);
        }
    }

    private function sanitizeText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }
        $text = trim(strip_tags($text));

        return $text === '' ? null : $text;
    }

    private function recordView(Request $request, CommunityPost $post): void
    {
        $userId = (int) $request->user()->id;
        $cacheKey = 'community_viewed_'.$userId.'_'.$post->id;

        if ($request->hasSession()) {
            $sessionKey = 'community_viewed_'.$post->id;
            if ($request->session()->has($sessionKey)) {
                return;
            }
            $request->session()->put($sessionKey, now()->timestamp);
        } elseif (Cache::has($cacheKey)) {
            return;
        } else {
            Cache::put($cacheKey, now()->timestamp, now()->addDay());
        }

        $post->increment('views_count');
    }

    private function recommendedUsers(int $exceptUserId)
    {
        return User::query()
            ->select(['id', 'name', 'account_type'])
            ->with(['supplierProfile:id,user_id,name,city', 'designerProfile:id,user_id,city'])
            ->whereIn('account_type', ['designer', 'supplier'])
            ->where('id', '<>', (int) $exceptUserId)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereHas('communityPosts', fn ($q) => $q->published())
            ->withCount(['communityPosts as posts_count' => fn ($q) => $q->published()])
            ->orderByDesc('posts_count')
            ->limit(5)
            ->get()
            ->filter(fn (User $user) => (int) $user->id !== (int) $exceptUserId)
            ->values();
    }

    private function layoutFor(User $user): string
    {
        return ($user->role ?? '') === 'supplier' ? 'layouts.supplier' : 'layouts.dashboard';
    }

    private function attempt(string $key, int $max, int $decaySeconds): bool
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return false;
        }
        RateLimiter::hit($key, $decaySeconds);

        return true;
    }

    private function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['ok' => false, 'success' => false, 'message' => $message], $status);
    }

    /**
     * @return list<int>
     */
    private function hiddenPostIds(Request $request, int $userId): array
    {
        $hidden = CommunityPostHide::query()
            ->where('user_id', $userId)
            ->pluck('community_post_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($request->hasSession()) {
            $sessionHidden = $request->session()->get('community_hidden_posts', []);
            if (is_array($sessionHidden) && $sessionHidden !== []) {
                $hidden = array_values(array_unique(array_merge(
                    $hidden,
                    array_map('intval', $sessionHidden)
                )));
            }
        }

        return $hidden;
    }

    /**
     * @return array<string, int>
     */
    private function communityLimits(): array
    {
        return [
            'max_images' => (int) config('community.max_images', 10),
            'max_image_kb' => (int) config('community.max_image_kb', 5120),
            'max_text_length' => (int) config('community.max_text_length', 2000),
            'max_comment_length' => (int) config('community.max_comment_length', 1000),
            'posts_per_page' => (int) config('community.posts_per_page', 10),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment(CommunityPostComment $comment, int $viewerId): array
    {
        return [
            'id' => (int) $comment->id,
            'parent_id' => $comment->parent_id !== null ? (int) $comment->parent_id : null,
            'text' => (string) $comment->text,
            'created_at' => optional($comment->created_at)->toIso8601String(),
            'author' => [
                'id' => $comment->author?->id,
                'name' => $comment->author?->name,
                'role' => $comment->author?->role,
                'city' => $comment->author?->city,
            ],
            'can_edit' => (int) $comment->user_id === $viewerId,
            'can_delete' => (int) $comment->user_id === $viewerId,
            'replies' => $comment->relationLoaded('replies')
                ? $comment->replies
                    ->map(fn (CommunityPostComment $reply) => $this->serializeComment($reply, $viewerId))
                    ->values()
                    ->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRecommendedUser(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'role' => (string) $user->account_type,
            'city' => (string) ($user->supplierProfile?->city ?? $user->designerProfile?->city ?? ''),
            'posts_count' => (int) ($user->posts_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProfileUser(User $author, int $viewerId): array
    {
        $logo = null;
        if (($author->role ?? '') === 'supplier' && $author->supplierProfile?->logo) {
            $logo = asset('storage/'.ltrim((string) $author->supplierProfile->logo, '/'));
        }

        $profile = null;
        if (($author->role ?? '') === 'supplier' && $author->supplierProfile) {
            $sp = $author->supplierProfile;
            $profile = [
                'type' => 'supplier',
                'name' => (string) ($sp->name ?? ''),
                'city' => (string) ($sp->city ?? ''),
                'phone' => (string) ($sp->phone ?? ''),
                'email' => (string) ($sp->email ?? ''),
                'sphere' => (string) ($sp->sphere ?? ''),
                'logo_url' => $logo,
            ];
        } elseif ($author->designerProfile) {
            $dp = $author->designerProfile;
            $profile = [
                'type' => 'designer',
                'phone' => (string) ($dp->phone ?? ''),
                'city' => (string) ($dp->city ?? ''),
                'short_description' => (string) ($dp->short_description ?? ''),
                'about_designer' => (string) ($dp->about_designer ?? ''),
                'website_portfolio' => (string) ($dp->website_portfolio ?? ''),
                'experience' => (string) ($dp->experience ?? ''),
                'specialization' => (string) ($dp->specialization ?? ''),
            ];
        }

        return [
            'id' => (int) $author->id,
            'name' => (string) $author->name,
            'role' => (string) $author->role,
            'city' => (string) ($author->city ?? ''),
            'is_owner' => (int) $author->id === $viewerId,
            'profile' => $profile,
        ];
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*');
    }

    private function savedPostsCount(int $userId): int
    {
        return (int) CommunityPostSave::query()
            ->where('user_id', $userId)
            ->whereHas('post', fn ($q) => $q->published())
            ->count();
    }
}
