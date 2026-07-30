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
            if (! Schema::hasColumn('projects', 'client_id')) {
                $table->foreignId('client_id')->nullable()->after('user_id')->constrained('clients')->nullOnDelete();
            }
        });

        // Make object_id nullable without dropping FK aggressively across drivers.
        if (Schema::hasColumn('projects', 'object_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('object_id')->nullable()->change();
            });
        }

        // Backfill client_id from passport_objects via object_id
        if (Schema::hasTable('passport_objects')) {
            DB::table('projects')
                ->whereNotNull('object_id')
                ->whereNull('client_id')
                ->orderBy('id')
                ->chunkById(200, function ($projects) {
                    foreach ($projects as $project) {
                        $clientId = DB::table('passport_objects')
                            ->where('id', $project->object_id)
                            ->value('client_id');

                        if ($clientId) {
                            DB::table('projects')->where('id', $project->id)->update([
                                'client_id' => $clientId,
                                'updated_at' => now(),
                            ]);
                        }
                    }
                });
        }

        // Ensure project_object_details exists for every project that has passport data
        if (Schema::hasTable('project_object_details') && Schema::hasTable('passport_objects')) {
            DB::table('projects')
                ->whereNotNull('object_id')
                ->orderBy('id')
                ->chunkById(200, function ($projects) {
                    foreach ($projects as $project) {
                        $exists = DB::table('project_object_details')
                            ->where('project_id', $project->id)
                            ->exists();
                        if ($exists) {
                            // Keep client_id on details in sync
                            $object = DB::table('passport_objects')->where('id', $project->object_id)->first();
                            if ($object) {
                                DB::table('project_object_details')
                                    ->where('project_id', $project->id)
                                    ->update([
                                        'client_id' => $object->client_id,
                                        'updated_at' => now(),
                                    ]);
                            }
                            continue;
                        }

                        $object = DB::table('passport_objects')->where('id', $project->object_id)->first();
                        if (! $object) {
                            continue;
                        }

                        DB::table('project_object_details')->insert([
                            'project_id' => $project->id,
                            'passport_object_id' => $object->id,
                            'client_id' => $object->client_id,
                            'city' => $object->city ?? null,
                            'address' => $object->address,
                            'apartment' => $object->apartment ?? null,
                            'apartment_floor' => $object->apartment_floor ?? null,
                            'apartment_entrance' => $object->apartment_entrance ?? null,
                            'type' => $object->type,
                            'status' => $object->status,
                            'area' => $object->area,
                            'repair_budget_planned' => $object->repair_budget_planned,
                            'repair_budget_actual' => $object->repair_budget_actual,
                            'repair_budget_per_m2_planned' => $object->repair_budget_per_m2_planned,
                            'repair_budget_per_m2_actual' => $object->repair_budget_per_m2_actual,
                            'links' => $object->links,
                            'file_paths' => $object->file_paths,
                            'comment' => $object->comment,
                            'latitude' => $object->latitude ?? null,
                            'longitude' => $object->longitude ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'client_id')) {
                $table->dropConstrainedForeignId('client_id');
            }
        });

        // Do not force object_id back to NOT NULL — may fail if nulls exist.
    }
};
