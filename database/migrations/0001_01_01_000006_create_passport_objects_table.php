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
        Schema::create('passport_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address');
            $table->string('city', 100)->nullable();
            $table->enum('type', ['apartment', 'house', 'commercial', 'office', 'other'])->default('apartment');
            $table->string('apartment', 50)->nullable();
            $table->string('apartment_floor', 20)->nullable();
            $table->string('apartment_entrance', 20)->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('repair_budget_planned', 12, 2)->nullable();
            $table->decimal('repair_budget_actual', 12, 2)->nullable();
            $table->decimal('repair_budget_per_m2_planned', 12, 2)->nullable();
            $table->decimal('repair_budget_per_m2_actual', 12, 2)->nullable();
            $table->string('status', 50)->nullable();
            $table->json('links')->nullable();
            $table->json('file_paths')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('moderation_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('moderation_reason')->nullable();
            $table->text('moderation_comment')->nullable();
            $table->unsignedBigInteger('moderation_duplicate_of_object_id')->nullable();
            $table->unsignedBigInteger('moderation_reviewer_id')->nullable();
            $table->timestamp('moderation_reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('client_id');
            $table->index('city');
            $table->index('type');
            $table->index('moderation_status');
            $table->index(['city', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passport_objects');
    }
};
