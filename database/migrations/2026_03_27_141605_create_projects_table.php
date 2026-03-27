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
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('object_id')->constrained('passport_objects');
            $table->string('name');
            $table->string('status');
            $table->date('start_date');
            $table->date('planned_end_date');
            $table->date('actual_end_date')->nullable();
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->decimal('planned_cost', 10, 2)->default(0);
            $table->json('links')->nullable();
            $table->json('files')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
