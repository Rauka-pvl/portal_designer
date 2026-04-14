<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_order_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_order_id')->constrained('supplier_orders')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('read_by_designer_at')->nullable();
            $table->timestamp('read_by_supplier_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_order_messages');
    }
};
