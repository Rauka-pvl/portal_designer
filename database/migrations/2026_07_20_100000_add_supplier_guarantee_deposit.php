<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'account_status')) {
                $table->string('account_status', 32)->default('deposit_required')->after('profile_status');
                $table->index('account_status');
            }
            if (! Schema::hasColumn('suppliers', 'guarantee_balance')) {
                // Integer tenge — never float.
                $table->unsignedBigInteger('guarantee_balance')->default(0)->after('account_status');
            }
            if (! Schema::hasColumn('suppliers', 'deposit_activated_at')) {
                $table->timestamp('deposit_activated_at')->nullable()->after('guarantee_balance');
            }
        });

        // Existing suppliers stay usable; new ones get deposit_required via model/registration.
        if (Schema::hasColumn('suppliers', 'account_status')) {
            DB::table('suppliers')
                ->whereNull('deposit_activated_at')
                ->where(function ($q) {
                    $q->where('moderation_status', 'approved')
                        ->orWhere('profile_status', 'active');
                })
                ->update([
                    'account_status' => 'active',
                    'deposit_activated_at' => now(),
                ]);

            DB::table('suppliers')
                ->where(function ($q) {
                    $q->whereNull('account_status')
                        ->orWhereNotIn('account_status', ['deposit_required', 'payment_pending', 'active']);
                })
                ->update(['account_status' => 'deposit_required']);
        }

        if (! Schema::hasTable('supplier_guarantee_payments')) {
            Schema::create('supplier_guarantee_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->uuid('uuid')->unique();
                $table->string('type', 64)->default('supplier_guarantee_deposit');
                $table->unsignedBigInteger('amount');
                $table->string('currency', 3)->default('KZT');
                $table->string('status', 32)->default('created');
                $table->string('provider', 32)->default('demo');
                $table->string('provider_payment_id')->nullable()->index();
                $table->string('idempotency_key', 64)->unique();
                $table->string('payment_url')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('provider_event_id')->nullable()->index();
                $table->json('provider_payload')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['supplier_id', 'status']);
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('supplier_guarantee_ledger')) {
            Schema::create('supplier_guarantee_ledger', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('supplier_guarantee_payments')->nullOnDelete();
                $table->string('type', 64);
                $table->bigInteger('amount');
                $table->string('currency', 3)->default('KZT');
                $table->unsignedBigInteger('balance_after');
                $table->string('source', 64)->nullable();
                $table->string('status', 32)->default('completed');
                $table->string('idempotency_key', 64)->unique();
                $table->text('description')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['supplier_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_guarantee_ledger');
        Schema::dropIfExists('supplier_guarantee_payments');

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'deposit_activated_at')) {
                $table->dropColumn('deposit_activated_at');
            }
            if (Schema::hasColumn('suppliers', 'guarantee_balance')) {
                $table->dropColumn('guarantee_balance');
            }
            if (Schema::hasColumn('suppliers', 'account_status')) {
                $table->dropIndex(['account_status']);
                $table->dropColumn('account_status');
            }
        });
    }
};
