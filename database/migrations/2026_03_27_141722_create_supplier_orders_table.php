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
        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('status');
            $table->integer('summa');
            $table->string('category');
            $table->string('mark')->nullable();
            $table->string('room')->nullable();
            $table->date('date_planned');
            $table->date('date_actual')->nullable();
            $table->date('prepayment_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->integer('prepayment_amount')->nullable();
            $table->integer('payment_amount')->nullable();
            $table->json('links')->nullable();
            $table->json('files')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_orders');
    }
};
