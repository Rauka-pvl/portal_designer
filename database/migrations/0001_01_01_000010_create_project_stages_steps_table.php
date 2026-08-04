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
        Schema::create('project_stages_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_stage_id')->constrained('project_stages')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('order')->default(0);
            $table->date('due_date')->nullable();
            $table->date('deadline')->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('link')->nullable();
            $table->string('result_status', 50)->nullable();
            $table->text('result_comment')->nullable();
            $table->text('result')->nullable();
            $table->json('result_files')->nullable();
            $table->timestamps();

            $table->index('project_stage_id');
            $table->index('status');
            $table->index('order');
            $table->index(['project_stage_id', 'order']);
            $table->index('responsible_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_stages_steps');
    }
};
