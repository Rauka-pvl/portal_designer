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
        Schema::create('supplier_order_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamp('read_by_designer_at')->nullable();
            $table->timestamp('read_by_supplier_at')->nullable();
            $table->timestamps();

            $table->index('supplier_order_id');
            $table->index('sender_user_id');
            $table->index(['supplier_order_id', 'read_by_designer_at'], 'som_order_designer_read_idx');
            $table->index(['supplier_order_id', 'read_by_supplier_at'], 'som_order_supplier_read_idx');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_order_messages');
    }
};
