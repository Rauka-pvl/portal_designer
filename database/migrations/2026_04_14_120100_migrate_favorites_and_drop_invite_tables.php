<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('designer_favorite_suppliers') && Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'is_favorite')) {
            DB::table('suppliers')
                ->select(['user_id', 'id as supplier_id'])
                ->where('is_favorite', true)
                ->orderBy('id')
                ->chunk(500, function ($rows): void {
                    $payload = [];
                    foreach ($rows as $row) {
                        $designerId = (int) ($row->user_id ?? 0);
                        $supplierId = (int) ($row->supplier_id ?? 0);
                        if ($designerId < 1 || $supplierId < 1) {
                            continue;
                        }
                        $payload[] = [
                            'designer_user_id' => $designerId,
                            'supplier_id' => $supplierId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if ($payload !== []) {
                        DB::table('designer_favorite_suppliers')->upsert(
                            $payload,
                            ['designer_user_id', 'supplier_id'],
                            ['updated_at']
                        );
                    }
                });
        }

        if (Schema::hasTable('user_suppliers')) {
            Schema::drop('user_suppliers');
        }

        if (Schema::hasColumn('suppliers', 'is_favorite')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('is_favorite');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('suppliers', 'is_favorite')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->boolean('is_favorite')->default(false);
            });
        }

        if (! Schema::hasTable('user_suppliers')) {
            Schema::create('user_suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('designer_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->string('status', 32)->default('pending');
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();

                $table->unique(['designer_user_id', 'supplier_id']);
                $table->index(['designer_user_id', 'status']);
            });
        }
    }
};
