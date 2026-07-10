<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'name']);
            $table->index(['supplier_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
