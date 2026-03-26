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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Основная информация
            $table->string('logo')->nullable();
            $table->string('name');
            $table->boolean('recommend')->default(false);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('telegram')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('website')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('sphere')->nullable();
            $table->string('work_terms_type')->nullable();
            $table->string('work_terms_value')->nullable();
            $table->json('brands')->nullable();
            $table->json('cities_presence')->nullable();
            $table->text('comment')->nullable();

            // Реквизиты
            $table->string('org_form')->default('ooo');
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->string('ogrn')->nullable();
            $table->string('okpo')->nullable();
            $table->text('legal_address')->nullable();
            $table->text('actual_address')->nullable();
            $table->boolean('address_match')->default(false);
            $table->string('director')->nullable();
            $table->string('accountant')->nullable();

            // Банковские реквизиты
            $table->string('bik')->nullable();
            $table->string('bank')->nullable();
            $table->string('checking_account')->nullable();
            $table->string('corr_account')->nullable();
            $table->text('comment_bank')->nullable();

            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
