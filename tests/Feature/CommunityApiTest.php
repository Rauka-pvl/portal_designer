<?php

namespace Tests\Feature;

use App\Models\CommunityPost;
use App\Models\CommunityPostComment;
use App\Models\CommunityPostSave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityApiTest extends TestCase
{
    use RefreshDatabase;

    private function designer(): User
    {
        $user = User::factory()->create([
            'account_type' => 'designer',
        ]);

        \App\Models\Subscription::create([
            'user_id' => $user->id,
            'plan_id' => \App\Models\SubscriptionPlan::findByKey('pro')?->id,
            'status' => 'trial',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
            'expires_at' => now()->addDays(14),
        ]);

        return $user;
    }

    private function publishedPost(User $author, array $extra = []): CommunityPost
    {
        return CommunityPost::query()->create(array_merge([
            'user_id' => $author->id,
            'text' => 'Hello community',
            'category' => 'idea',
            'status' => CommunityPost::STATUS_PUBLISHED,
            'visibility' => CommunityPost::VISIBILITY_PUBLIC,
            'likes_count' => 0,
            'comments_count' => 0,
            'saves_count' => 0,
            'views_count' => 0,
        ], $extra));
    }

    public function test_can_create_post_and_comment_via_api(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/community/posts', [
            'text' => 'New post from API',
            'category' => 'work',
        ])->assertSuccessful()
            ->assertJsonPath('success', true);

        $postId = (int) $create->json('post.id');
        $this->assertGreaterThan(0, $postId);

        $this->postJson('/api/community/posts/'.$postId.'/comments', [
            'text' => 'Nice post!',
        ])->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath('comment.text', 'Nice post!');

        $this->assertDatabaseHas('community_post_comments', [
            'community_post_id' => $postId,
            'user_id' => $user->id,
            'text' => 'Nice post!',
        ]);

        $this->getJson('/api/community/posts/'.$postId.'/comments')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'comments');
    }

    public function test_unsave_via_delete_removes_from_saved_tab(): void
    {
        $author = $this->designer();
        $viewer = $this->designer();
        $post = $this->publishedPost($author, ['text' => 'Save me']);

        CommunityPostSave::query()->create([
            'community_post_id' => $post->id,
            'user_id' => $viewer->id,
        ]);
        $post->update(['saves_count' => 1]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/community?tab=saved')
            ->assertOk()
            ->assertJsonPath('saved_count', 1)
            ->assertJsonCount(1, 'posts');

        $this->deleteJson('/api/community/posts/'.$post->id.'/save')
            ->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_saved', false);

        $this->assertDatabaseMissing('community_post_saves', [
            'community_post_id' => $post->id,
            'user_id' => $viewer->id,
        ]);

        $this->getJson('/api/community?tab=saved')
            ->assertOk()
            ->assertJsonPath('saved_count', 0)
            ->assertJsonCount(0, 'posts');
    }

    public function test_soft_deleted_post_is_cleaned_from_saves(): void
    {
        $author = $this->designer();
        $viewer = $this->designer();
        $post = $this->publishedPost($author);

        CommunityPostSave::query()->create([
            'community_post_id' => $post->id,
            'user_id' => $viewer->id,
        ]);
        $post->update(['saves_count' => 1]);

        Sanctum::actingAs($author);
        $this->deleteJson('/api/community/posts/'.$post->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('community_posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('community_post_saves', [
            'community_post_id' => $post->id,
            'user_id' => $viewer->id,
        ]);

        Sanctum::actingAs($viewer);
        $this->getJson('/api/community?tab=saved')
            ->assertOk()
            ->assertJsonPath('saved_count', 0)
            ->assertJsonCount(0, 'posts');
    }

    public function test_can_unsave_even_if_post_was_soft_deleted_without_cleanup(): void
    {
        $author = $this->designer();
        $viewer = $this->designer();
        $post = $this->publishedPost($author);

        CommunityPostSave::query()->create([
            'community_post_id' => $post->id,
            'user_id' => $viewer->id,
        ]);

        // Simulate orphaned save after soft-delete without cascade cleanup.
        $post->delete();

        Sanctum::actingAs($viewer);
        $this->deleteJson('/api/community/posts/'.$post->id.'/save')
            ->assertSuccessful()
            ->assertJsonPath('is_saved', false);

        $this->assertDatabaseMissing('community_post_saves', [
            'community_post_id' => $post->id,
            'user_id' => $viewer->id,
        ]);
    }

    public function test_comment_accepts_body_alias(): void
    {
        $user = $this->designer();
        $post = $this->publishedPost($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/community/posts/'.$post->id.'/comments', [
            'body' => 'Alias works',
        ])->assertSuccessful()
            ->assertJsonPath('comment.text', 'Alias works');

        $this->assertInstanceOf(CommunityPostComment::class, CommunityPostComment::query()->first());
    }
}
