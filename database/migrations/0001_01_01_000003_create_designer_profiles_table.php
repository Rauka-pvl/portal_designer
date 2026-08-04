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
        Schema::create('designer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('short_description', 500)->nullable();
            $table->string('work_regions', 2000)->nullable();
            $table->text('about_designer')->nullable();
            $table->string('website_portfolio', 255)->nullable();
            $table->string('telegram', 100)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('vk', 255)->nullable();
            $table->string('instagram', 100)->nullable();
            $table->string('experience', 100)->nullable();
            $table->decimal('price_per_m2', 10, 2)->nullable();
            $table->string('education', 255)->nullable();
            $table->string('awards', 500)->nullable();
            $table->string('specialization', 255)->nullable();
            $table->string('styles', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designer_profiles');
    }
};
