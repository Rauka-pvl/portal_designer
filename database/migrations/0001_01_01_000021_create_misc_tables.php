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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('designer_cashback_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->unsignedInteger('amount');
            $table->unsignedBigInteger('supplier_order_id')->nullable();
            $table->string('status', 20)->default('completed');
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['supplier_order_id', 'type'], 'cashback_order_accrual_unique');
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type', 'status']);
            $table->index('supplier_order_id');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_order_id')->nullable();
            $table->string('direction', 32);
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('designer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['supplier_order_id', 'direction']);
            $table->index(['direction', 'designer_user_id']);
            $table->index(['direction', 'supplier_id']);
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('designer_cashback_transactions');
        Schema::dropIfExists('personal_access_tokens');
    }
};
