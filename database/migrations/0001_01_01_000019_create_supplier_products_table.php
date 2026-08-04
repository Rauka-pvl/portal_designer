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
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('category')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('unit', 20)->nullable();
            $table->string('image_path')->nullable();
            $table->json('images')->nullable();
            $table->string('qr_token', 100)->nullable()->unique();
            $table->unsignedInteger('qr_version')->default(1);
            $table->timestamp('qr_generated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('sku');
            $table->index('is_active');
            $table->index('category');
        });

        Schema::create('designer_favorite_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['designer_user_id', 'supplier_id']);
            $table->index('designer_user_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designer_favorite_suppliers');
        Schema::dropIfExists('supplier_products');
    }
};
