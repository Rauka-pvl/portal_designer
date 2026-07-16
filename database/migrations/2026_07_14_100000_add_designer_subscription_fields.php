<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'subscription_trial_ends_at')) {
                $table->timestamp('subscription_trial_ends_at')->nullable()->after('password_changed_at');
            }
            if (! Schema::hasColumn('users', 'subscription_plan')) {
                $table->string('subscription_plan', 20)->nullable()->after('subscription_trial_ends_at');
            }
            if (! Schema::hasColumn('users', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('subscription_plan');
            }
        });

        if (! Schema::hasTable('designer_subscription_payments')) {
            Schema::create('designer_subscription_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('plan', 20);
                $table->unsignedInteger('amount');
                $table->unsignedSmallInteger('period_days')->default(30);
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->string('status', 20)->default('completed');
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        // Существующим дизайнерам — 30 дней триала с момента миграции
        User::query()
            ->where('role', 'designer')
            ->whereNull('subscription_trial_ends_at')
            ->update([
                'subscription_trial_ends_at' => now()->addDays(30),
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('designer_subscription_payments');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_trial_ends_at',
                'subscription_plan',
                'subscription_ends_at',
            ]);
        });
    }
};
