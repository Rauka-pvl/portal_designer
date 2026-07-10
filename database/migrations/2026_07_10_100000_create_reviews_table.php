<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_order_id')->nullable()->constrained('supplier_orders')->nullOnDelete();
            // Направление отзыва: designer_to_supplier | supplier_to_designer
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
