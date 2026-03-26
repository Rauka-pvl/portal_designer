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
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('client_id')->constrained('clients');
            $table->string('address');
            $table->string('type');
            $table->string('status');
            $table->decimal('area', 10, 2);
            $table->decimal('repair_budget_planned', 10, 2)->nullable();
            $table->decimal('repair_budget_actual', 10, 2)->nullable();
            $table->decimal('repair_budget_per_m2_planned', 10, 2)->nullable();
            $table->decimal('repair_budget_per_m2_actual', 10, 2)->nullable();
            $table->json('links')->nullable();
            $table->json('file_paths')->nullable();
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
        Schema::dropIfExists('passport_objects');
    }
};
