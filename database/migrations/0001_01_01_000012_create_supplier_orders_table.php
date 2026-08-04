<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('name')->nullable();
            $table->string('status', 50)->default('draft');
            $table->integer('summa')->nullable()->default(0);
            $table->string('category')->nullable();
            $table->string('mark')->nullable();
            $table->string('room')->nullable();
            $table->date('date_planned')->nullable();
            $table->date('date_actual')->nullable();
            $table->date('prepayment_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->integer('prepayment_amount')->nullable()->default(0);
            $table->integer('payment_amount')->nullable()->default(0);
            $table->decimal('bonus_percent', 5, 2)->nullable()->default(0);
            $table->text('comment')->nullable();
            $table->json('links')->nullable();
            $table->json('files')->nullable();
            $table->json('product_items')->nullable();
            $table->json('included_step_ids')->nullable();
            $table->boolean('is_sent_to_supplier')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->string('offer_status', 50)->nullable();
            $table->text('offer_message')->nullable();
            $table->text('offer_comment')->nullable();
            $table->json('offer_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('project_id');
            $table->index('supplier_id');
            $table->index('client_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('date_planned');
            $table->index('payment_date');
            $table->index('offer_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_orders');
    }
};
