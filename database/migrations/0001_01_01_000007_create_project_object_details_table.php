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
        Schema::create('project_object_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('passport_object_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->enum('type', ['apartment', 'house', 'commercial', 'office', 'other'])->nullable();
            $table->string('apartment', 50)->nullable();
            $table->string('apartment_floor', 20)->nullable();
            $table->string('apartment_entrance', 20)->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('repair_budget_planned', 12, 2)->nullable();
            $table->decimal('repair_budget_actual', 12, 2)->nullable();
            $table->decimal('repair_budget_per_m2_planned', 12, 2)->nullable();
            $table->decimal('repair_budget_per_m2_actual', 12, 2)->nullable();
            $table->string('status')->default('new');
            $table->json('links')->nullable();
            $table->json('file_paths')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('client_id');
            $table->index('city');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_object_details');
    }
};
