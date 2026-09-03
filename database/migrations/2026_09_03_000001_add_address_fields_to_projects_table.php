<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('comment');
            $table->string('address', 500)->nullable()->after('city');
            $table->string('apartment', 50)->nullable()->after('address');
            $table->string('apartment_floor', 50)->nullable()->after('apartment');
            $table->string('apartment_entrance', 50)->nullable()->after('apartment_floor');
            $table->string('object_type', 50)->nullable()->after('apartment_entrance');
            $table->decimal('area', 10, 2)->nullable()->after('object_type');
            $table->decimal('latitude', 10, 7)->nullable()->after('area');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index('city');
        });

        // Prefer CRM object details, then legacy passport object.
        if (Schema::hasTable('project_object_details')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement('
                    UPDATE projects p
                    INNER JOIN project_object_details d ON d.project_id = p.id
                    SET
                        p.city = COALESCE(NULLIF(p.city, \'\'), d.city),
                        p.address = COALESCE(NULLIF(p.address, \'\'), d.address),
                        p.apartment = COALESCE(NULLIF(p.apartment, \'\'), d.apartment),
                        p.apartment_floor = COALESCE(NULLIF(p.apartment_floor, \'\'), d.apartment_floor),
                        p.apartment_entrance = COALESCE(NULLIF(p.apartment_entrance, \'\'), d.apartment_entrance),
                        p.object_type = COALESCE(NULLIF(p.object_type, \'\'), d.type),
                        p.area = COALESCE(p.area, d.area),
                        p.latitude = COALESCE(p.latitude, d.latitude),
                        p.longitude = COALESCE(p.longitude, d.longitude)
                ');
            } else {
                // SQLite / others: row-by-row copy
                foreach (DB::table('project_object_details')->get() as $d) {
                    $project = DB::table('projects')->where('id', $d->project_id)->first();
                    if (! $project) {
                        continue;
                    }
                    DB::table('projects')->where('id', $d->project_id)->update([
                        'city' => $project->city ?: $d->city,
                        'address' => $project->address ?: $d->address,
                        'apartment' => $project->apartment ?: $d->apartment,
                        'apartment_floor' => $project->apartment_floor ?: $d->apartment_floor,
                        'apartment_entrance' => $project->apartment_entrance ?: $d->apartment_entrance,
                        'object_type' => $project->object_type ?: $d->type,
                        'area' => $project->area ?? $d->area,
                        'latitude' => $project->latitude ?? $d->latitude,
                        'longitude' => $project->longitude ?? $d->longitude,
                    ]);
                }
            }
        }

        if (Schema::hasTable('passport_objects')) {
            $projects = DB::table('projects')
                ->whereNotNull('object_id')
                ->where(function ($q) {
                    $q->whereNull('address')->orWhere('address', '');
                })
                ->get(['id', 'object_id']);

            foreach ($projects as $project) {
                $object = DB::table('passport_objects')->where('id', $project->object_id)->first();
                if (! $object) {
                    continue;
                }
                $current = DB::table('projects')->where('id', $project->id)->first();
                DB::table('projects')->where('id', $project->id)->update([
                    'city' => $current->city ?: $object->city,
                    'address' => $current->address ?: $object->address,
                    'apartment' => $current->apartment ?: $object->apartment,
                    'apartment_floor' => $current->apartment_floor ?: $object->apartment_floor,
                    'apartment_entrance' => $current->apartment_entrance ?: $object->apartment_entrance,
                    'object_type' => $current->object_type ?: $object->type,
                    'area' => $current->area ?? $object->area,
                    'latitude' => $current->latitude ?? $object->latitude,
                    'longitude' => $current->longitude ?? $object->longitude,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropColumn([
                'city',
                'address',
                'apartment',
                'apartment_floor',
                'apartment_entrance',
                'object_type',
                'area',
                'latitude',
                'longitude',
            ]);
        });
    }
};
