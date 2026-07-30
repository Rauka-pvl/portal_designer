<?php

use App\Enums\PipelineType;
use App\Enums\ProjectStatus;
use App\Enums\SupplyStatus;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32); // project | supply
            $table->string('name');
            $table->boolean('is_default')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'type', 'is_default']);
        });

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('pipelines')->cascadeOnDelete();
            $table->string('system_key', 64);
            $table->string('name');
            $table->string('color', 32)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['pipeline_id', 'system_key']);
            $table->index(['pipeline_id', 'position']);
        });

        Schema::create('activity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type', 64);
            $table->unsignedBigInteger('subject_id');
            $table->string('event_type', 64);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('project_object_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('passport_object_id')->nullable()->constrained('passport_objects')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('apartment')->nullable();
            $table->string('apartment_floor')->nullable();
            $table->string('apartment_entrance')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->decimal('area', 12, 2)->nullable();
            $table->decimal('repair_budget_planned', 14, 2)->nullable();
            $table->decimal('repair_budget_actual', 14, 2)->nullable();
            $table->decimal('repair_budget_per_m2_planned', 14, 2)->nullable();
            $table->decimal('repair_budget_per_m2_actual', 14, 2)->nullable();
            $table->json('links')->nullable();
            $table->json('file_paths')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        // Backfill pipelines for existing designers
        $designers = User::query()->where('role', 'designer')->orderBy('id')->get(['id']);

        foreach ($designers as $designer) {
            $this->seedProjectPipeline((int) $designer->id);
            $this->seedSupplyPipeline((int) $designer->id);
        }

        // Backfill project_object_details from passport_objects via projects.object_id
        if (Schema::hasTable('projects') && Schema::hasTable('passport_objects')) {
            DB::table('projects')
                ->whereNotNull('object_id')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->chunkById(200, function ($projects) {
                    foreach ($projects as $project) {
                        $object = DB::table('passport_objects')->where('id', $project->object_id)->first();
                        if (! $object) {
                            continue;
                        }

                        $exists = DB::table('project_object_details')
                            ->where('project_id', $project->id)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        DB::table('project_object_details')->insert([
                            'project_id' => $project->id,
                            'passport_object_id' => $object->id,
                            'client_id' => $object->client_id,
                            'city' => $object->city,
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
                            'latitude' => $object->latitude,
                            'longitude' => $object->longitude,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }

        // Seed activity: project created
        if (Schema::hasTable('projects')) {
            DB::table('projects')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->chunkById(200, function ($projects) {
                    $rows = [];
                    foreach ($projects as $project) {
                        $rows[] = [
                            'user_id' => $project->user_id,
                            'subject_type' => 'project',
                            'subject_id' => $project->id,
                            'event_type' => 'project.created',
                            'actor_id' => $project->user_id,
                            'body' => null,
                            'meta' => json_encode(['name' => $project->name], JSON_UNESCAPED_UNICODE),
                            'created_at' => $project->created_at ?? now(),
                            'updated_at' => $project->created_at ?? now(),
                        ];
                    }
                    if ($rows !== []) {
                        DB::table('activity_events')->insert($rows);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_object_details');
        Schema::dropIfExists('activity_events');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');
    }

    private function seedProjectPipeline(int $userId): void
    {
        $pipelineId = DB::table('pipelines')->insertGetId([
            'user_id' => $userId,
            'type' => PipelineType::Project->value,
            'name' => 'Общая воронка',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $position = 0;
        foreach (ProjectStatus::funnelOrder() as $status) {
            DB::table('pipeline_stages')->insert([
                'pipeline_id' => $pipelineId,
                'system_key' => $status->value,
                'name' => match ($status) {
                    ProjectStatus::ContractNegotiation => 'Заключение договора',
                    ProjectStatus::ContractSigned => 'Договор подписан',
                    ProjectStatus::PrepaymentReceived => 'Предоплата поступила',
                    ProjectStatus::TzSigned => 'ТЗ Подписано',
                    ProjectStatus::DocumentsSigned => 'Документы подписаны',
                    ProjectStatus::InWork => 'Проект взят в работу',
                },
                'color' => $status->defaultColor(),
                'position' => $position++,
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedSupplyPipeline(int $userId): void
    {
        $pipelineId = DB::table('pipelines')->insertGetId([
            'user_id' => $userId,
            'type' => PipelineType::Supply->value,
            'name' => 'Воронка поставок',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $position = 0;
        foreach (SupplyStatus::funnelOrder() as $status) {
            DB::table('pipeline_stages')->insert([
                'pipeline_id' => $pipelineId,
                'system_key' => $status->value,
                'name' => match ($status) {
                    SupplyStatus::OrderCreated => 'Заказ создан',
                    SupplyStatus::OrderConfirmed => 'Заказ подтвержден',
                    SupplyStatus::AdvancePayment => 'Оплата аванса',
                    SupplyStatus::FullPayment => 'Оплата 100%',
                    SupplyStatus::DeliveryCompleted => 'Поставка выполнена',
                    default => $status->value,
                },
                'color' => $status->defaultColor(),
                'position' => $position++,
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
