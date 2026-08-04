<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('city', 100)->nullable();
            $table->text('comment')->nullable();
            $table->text('notes')->nullable();
            $table->string('client_type', 50)->default('person');
            $table->string('status', 50)->default('new');
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('file_path')->nullable();
            $table->json('file_paths')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('phone');
            $table->index('email');
            $table->index('status');
            $table->index('client_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
