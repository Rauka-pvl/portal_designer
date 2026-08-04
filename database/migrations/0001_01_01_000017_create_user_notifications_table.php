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
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('info');
            $table->string('title');
            $table->text('comment')->nullable();
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable();
            $table->string('action_key')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            $table->unsignedBigInteger('related_supplier_id')->nullable();
            $table->unsignedBigInteger('related_order_id')->nullable();
            $table->unsignedBigInteger('related_post_id')->nullable();
            $table->unsignedBigInteger('related_invitation_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index(['user_id', 'is_read']);
            $table->index('related_supplier_id');
            $table->index('related_order_id');
            $table->index('related_post_id');
            $table->index('related_invitation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
