<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designer_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('designer_teams')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('new'); // new|in_progress|completed|cancelled
            $table->timestamp('due_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['creator_id', 'status']);
            $table->index(['assignee_id', 'status']);
            $table->index(['project_id']);
            $table->index(['team_id', 'status']);
            $table->index(['due_at']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designer_tasks');
    }
};
