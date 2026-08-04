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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->string('name');
            $table->string('status', 50)->default('contract_negotiation');
            $table->string('workflow_status', 50)->nullable()->default('draft');
            $table->string('moderation_status', 50)->default('pending');
            $table->text('moderation_reason')->nullable();
            $table->text('moderation_comment')->nullable();
            $table->date('start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->decimal('planned_cost', 12, 2)->default(0);
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->text('comment')->nullable();
            $table->json('links')->nullable();
            $table->json('files')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'team_id']);
            $table->index('client_id');
            $table->index('status');
            $table->index('workflow_status');
            $table->index('moderation_status');
            $table->index('start_date');
            $table->index('planned_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
