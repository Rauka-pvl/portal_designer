<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('moderation_status')->default('approved')->after('status');
            $table->string('moderation_reason')->nullable()->after('moderation_status');
            $table->text('moderation_comment')->nullable()->after('moderation_reason');
            $table->foreignId('moderation_reviewer_id')->nullable()->constrained('users')->nullOnDelete()->after('moderation_comment');
            $table->timestamp('moderation_reviewed_at')->nullable()->after('moderation_reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['moderation_reviewer_id']);
            $table->dropColumn([
                'moderation_status',
                'moderation_reason',
                'moderation_comment',
                'moderation_reviewer_id',
                'moderation_reviewed_at',
            ]);
        });
    }
};

