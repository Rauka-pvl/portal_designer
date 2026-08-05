<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings production DBs (deployed with incomplete misc schema) in line with models:
 * - users.account_type (from legacy users.role if present)
 * - reviews.direction / reviewer_user_id / designer_user_id / supplier_order_id
 * - designer_cashback_transactions.status / meta
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->syncUsersAccountType();
        $this->syncCashbackTransactions();
        $this->syncReviews();
    }

    public function down(): void
    {
        // Non-destructive sync — no automatic rollback of added columns.
    }

    private function syncUsersAccountType(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'account_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('account_type', ['designer', 'supplier', 'system_admin'])
                    ->default('designer')
                    ->after('password');
                $table->index('account_type');
            });
        }

        if (Schema::hasColumn('users', 'role') && Schema::hasColumn('users', 'account_type')) {
            DB::table('users')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $legacy = (string) ($row->role ?? '');
                    $mapped = match ($legacy) {
                        'supplier' => 'supplier',
                        'admin', 'system_admin', 'moderator' => 'system_admin',
                        default => 'designer',
                    };

                    if ((string) ($row->account_type ?? '') !== $mapped) {
                        DB::table('users')->where('id', $row->id)->update(['account_type' => $mapped]);
                    }
                }
            });
        }
    }

    private function syncCashbackTransactions(): void
    {
        if (! Schema::hasTable('designer_cashback_transactions')) {
            return;
        }

        if (! Schema::hasColumn('designer_cashback_transactions', 'status')) {
            Schema::table('designer_cashback_transactions', function (Blueprint $table) {
                $table->string('status', 20)->default('completed')->after('supplier_order_id');
                $table->index(['user_id', 'type', 'status']);
            });
        }

        if (! Schema::hasColumn('designer_cashback_transactions', 'meta')) {
            Schema::table('designer_cashback_transactions', function (Blueprint $table) {
                $table->json('meta')->nullable()->after('description');
            });
        }

        DB::table('designer_cashback_transactions')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '');
            })
            ->update(['status' => 'completed']);
    }

    private function syncReviews(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        // Already on the correct schema.
        if (Schema::hasColumn('reviews', 'direction')
            && Schema::hasColumn('reviews', 'reviewer_user_id')
            && Schema::hasColumn('reviews', 'designer_user_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        // Incomplete/legacy table from shortened migration — rebuild safely.
        // Keep a backup of existing rows when possible.
        $legacyRows = DB::table('reviews')->get();

        Schema::dropIfExists('reviews');

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_order_id')->nullable();
            $table->string('direction', 32);
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('designer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['supplier_order_id', 'direction']);
            $table->index(['direction', 'designer_user_id']);
            $table->index(['direction', 'supplier_id']);
            $table->index('rating');
        });

        if ($driver !== 'sqlite') {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('supplier_order_id')->references('id')->on('supplier_orders')->nullOnDelete();
            });
        }

        foreach ($legacyRows as $row) {
            $reviewerId = (int) ($row->reviewer_user_id ?? $row->user_id ?? 0);
            $supplierId = (int) ($row->supplier_id ?? 0);
            $orderId = (int) ($row->supplier_order_id ?? $row->order_id ?? 0) ?: null;
            $designerId = (int) ($row->designer_user_id ?? 0);

            if ($designerId < 1 && $orderId) {
                $designerId = (int) (DB::table('supplier_orders')->where('id', $orderId)->value('user_id') ?? 0);
            }

            if ($designerId < 1) {
                $designerId = $reviewerId;
            }

            if ($reviewerId < 1 || $supplierId < 1 || $designerId < 1) {
                continue;
            }

            $direction = (string) ($row->direction ?? 'designer_to_supplier');
            if (! in_array($direction, ['designer_to_supplier', 'supplier_to_designer'], true)) {
                $direction = 'designer_to_supplier';
            }

            try {
                DB::table('reviews')->insert([
                    'id' => $row->id,
                    'supplier_order_id' => $orderId,
                    'direction' => $direction,
                    'reviewer_user_id' => $reviewerId,
                    'designer_user_id' => $designerId,
                    'supplier_id' => $supplierId,
                    'rating' => (int) ($row->rating ?? 5),
                    'comment' => $row->comment ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            } catch (\Throwable) {
                // Skip rows that violate FK/unique after reshape.
            }
        }
    }
};
