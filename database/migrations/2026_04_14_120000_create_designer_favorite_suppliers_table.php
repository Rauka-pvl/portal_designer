<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designer_favorite_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['designer_user_id', 'supplier_id']);
            $table->index('designer_user_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designer_favorite_suppliers');
    }
};
