<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512);
            $table->string('platform', 32)->nullable();
            $table->string('provider', 32)->default('expo');
            $table->string('app', 64)->default('mobile');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('token');
            $table->index(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
