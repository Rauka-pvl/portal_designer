<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('short_description')->nullable();
            $table->text('work_regions')->nullable();
            $table->text('about_designer')->nullable();
            $table->string('website_portfolio')->nullable();
            $table->string('telegram')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('vk')->nullable();
            $table->string('instagram')->nullable();
            $table->string('experience')->nullable();
            $table->decimal('price_per_m2', 12, 2)->nullable();
            $table->text('education')->nullable();
            $table->text('awards')->nullable();
            $table->text('specialization')->nullable();
            $table->text('styles')->nullable();
            $table->timestamps();
        });

        $designers = DB::table('users')
            ->where('role', 'designer')
            ->select([
                'id',
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
            ])
            ->get();

        foreach ($designers as $designer) {
            DB::table('designer_profiles')->updateOrInsert(
                ['user_id' => $designer->id],
                [
                    'phone' => $designer->phone,
                    'city' => $designer->city,
                    'short_description' => $designer->short_description,
                    'work_regions' => $designer->work_regions,
                    'about_designer' => $designer->about_designer,
                    'website_portfolio' => $designer->website_portfolio,
                    'telegram' => $designer->telegram,
                    'whatsapp' => $designer->whatsapp,
                    'vk' => $designer->vk,
                    'instagram' => $designer->instagram,
                    'experience' => $designer->experience,
                    'price_per_m2' => $designer->price_per_m2,
                    'education' => $designer->education,
                    'awards' => $designer->awards,
                    'specialization' => $designer->specialization,
                    'styles' => $designer->styles,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('users')
            ->where('role', 'designer')
            ->update([
                'phone' => null,
                'city' => null,
                'short_description' => null,
                'work_regions' => null,
                'about_designer' => null,
                'website_portfolio' => null,
                'telegram' => null,
                'whatsapp' => null,
                'vk' => null,
                'instagram' => null,
                'experience' => null,
                'price_per_m2' => null,
                'education' => null,
                'awards' => null,
                'specialization' => null,
                'styles' => null,
            ]);
    }

    public function down(): void
    {
        $profiles = DB::table('designer_profiles')
            ->select([
                'user_id',
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
            ])
            ->get();

        foreach ($profiles as $profile) {
            DB::table('users')
                ->where('id', $profile->user_id)
                ->update([
                    'phone' => $profile->phone,
                    'city' => $profile->city,
                    'short_description' => $profile->short_description,
                    'work_regions' => $profile->work_regions,
                    'about_designer' => $profile->about_designer,
                    'website_portfolio' => $profile->website_portfolio,
                    'telegram' => $profile->telegram,
                    'whatsapp' => $profile->whatsapp,
                    'vk' => $profile->vk,
                    'instagram' => $profile->instagram,
                    'experience' => $profile->experience,
                    'price_per_m2' => $profile->price_per_m2,
                    'education' => $profile->education,
                    'awards' => $profile->awards,
                    'specialization' => $profile->specialization,
                    'styles' => $profile->styles,
                ]);
        }

        Schema::dropIfExists('designer_profiles');
    }
};
