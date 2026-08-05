<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('text');
            $table->string('category', 50)->nullable();
            $table->string('visibility', 20)->default('public');
            $table->string('city', 100)->nullable();
            $table->enum('status', ['draft', 'published', 'hidden', 'deleted'])->default('published');
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('saves_count')->default(0);
            $table->json('media')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index(['status', 'created_at']);
            $table->index('likes_count');
        });

        Schema::create('community_post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type', 50);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('community_post_id');
            $table->index('sort_order');
        });

        Schema::create('community_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('community_post_comments')->cascadeOnDelete();
            $table->text('text');
            $table->integer('likes_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('community_post_id');
            $table->index('user_id');
            $table->index('parent_id');
        });

        Schema::create('community_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['community_post_id', 'user_id']);
            $table->index('community_post_id');
            $table->index('user_id');
        });

        Schema::create('community_post_hides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['community_post_id', 'user_id']);
            $table->index('community_post_id');
            $table->index('user_id');
        });

        Schema::create('community_post_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['community_post_id', 'user_id']);
            $table->index('community_post_id');
            $table->index('user_id');
        });

        Schema::create('community_post_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 100)->nullable();
            $table->text('comment')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('community_post_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_post_reports');
        Schema::dropIfExists('community_post_saves');
        Schema::dropIfExists('community_post_hides');
        Schema::dropIfExists('community_post_likes');
        Schema::dropIfExists('community_post_comments');
        Schema::dropIfExists('community_post_media');
        Schema::dropIfExists('community_posts');
    }
};
