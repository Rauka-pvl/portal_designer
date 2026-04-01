<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('city')->nullable()->after('phone');
            $table->string('short_description')->nullable()->after('city');
            $table->text('work_regions')->nullable()->after('short_description');
            $table->text('about_designer')->nullable()->after('work_regions');
            $table->string('website_portfolio')->nullable()->after('about_designer');
            $table->string('telegram')->nullable()->after('website_portfolio');
            $table->string('whatsapp')->nullable()->after('telegram');
            $table->string('vk')->nullable()->after('whatsapp');
            $table->string('instagram')->nullable()->after('vk');
            $table->string('experience')->nullable()->after('instagram');
            $table->decimal('price_per_m2', 12, 2)->nullable()->after('experience');
            $table->text('education')->nullable()->after('price_per_m2');
            $table->text('awards')->nullable()->after('education');
            $table->text('specialization')->nullable()->after('awards');
            $table->text('styles')->nullable()->after('specialization');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'city',
                'short_description',
                'work_regions',
                'about_designer',
                'website_portfolio',
                'telegram',
                'whatsapp',
                'vk',
                'instagram',
                'experience',
                'price_per_m2',
                'education',
                'awards',
                'specialization',
                'styles',
            ]);
        });
    }
};
