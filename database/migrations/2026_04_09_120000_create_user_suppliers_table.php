<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        if (Schema::hasTable('designer_supplier_links')) {
            DB::table('designer_supplier_links')
                ->select([
                    'designer_user_id',
                    'supplier_id',
                    'status',
                    'invited_at',
                    'accepted_at',
                    'rejected_at',
                    'created_at',
                    'updated_at',
                ])
                ->orderBy('id')
                ->chunk(500, function ($rows): void {
                    $payload = [];
                    foreach ($rows as $row) {
                        $payload[] = [
                            'designer_user_id' => $row->designer_user_id,
                            'supplier_id' => $row->supplier_id,
                            'status' => $row->status ?: 'pending',
                            'invited_at' => $row->invited_at,
                            'accepted_at' => $row->accepted_at,
                            'rejected_at' => $row->rejected_at,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];
                    }

                    if ($payload !== []) {
                        DB::table('user_suppliers')->upsert(
                            $payload,
                            ['designer_user_id', 'supplier_id'],
                            ['status', 'invited_at', 'accepted_at', 'rejected_at', 'updated_at']
                        );
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_suppliers');
    }
};
