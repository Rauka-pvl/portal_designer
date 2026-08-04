<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('supplier_guarantee_payments');

        Schema::create('supplier_guarantee_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50)->default('supplier_guarantee_deposit');
            $table->string('provider', 50)->nullable();
            $table->string('provider_payment_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('payment_url')->nullable();
            $table->integer('amount')->default(0);
            $table->string('currency', 3)->default('KZT');
            $table->string('status', 50)->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('provider_event_id')->nullable();
            $table->json('provider_payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('status');
            $table->index('provider_payment_id');
        });

        Schema::create('supplier_guarantee_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('supplier_guarantee_payments')->nullOnDelete();
            $table->string('type', 50);
            $table->integer('amount')->default(0);
            $table->string('currency', 3)->default('KZT');
            $table->integer('balance_after')->default(0);
            $table->string('source', 50)->nullable();
            $table->string('status', 50)->default('completed');
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('payment_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_guarantee_ledger');
        Schema::dropIfExists('supplier_guarantee_payments');
    }
};
