<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passport_objects', function (Blueprint $table) {
            $table->string('moderation_status')->nullable()->after('longitude');
            $table->foreignId('moderation_duplicate_of_object_id')
                ->nullable()
                ->after('moderation_status')
                ->constrained('passport_objects')
                ->nullOnDelete();
            $table->text('moderation_comment')->nullable()->after('moderation_duplicate_of_object_id');
            $table->foreignId('moderation_reviewer_id')
                ->nullable()
                ->after('moderation_comment')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('moderation_reviewed_at')->nullable()->after('moderation_reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::table('passport_objects', function (Blueprint $table) {
            $table->dropForeign(['moderation_duplicate_of_object_id']);
            $table->dropForeign(['moderation_reviewer_id']);
            $table->dropColumn([
                'moderation_status',
                'moderation_duplicate_of_object_id',
                'moderation_comment',
                'moderation_reviewer_id',
                'moderation_reviewed_at',
            ]);
        });
    }
};
