<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('subscription_trial_used')->default(false)->after('subscription_ends_at');
            $table->string('subscription_payment_method', 20)->nullable()->after('subscription_trial_used');
            $table->timestamp('subscription_cancelled_at')->nullable()->after('subscription_payment_method');
        });

        // Уже был триал (старая логика) — повторно не даём
        \App\Models\User::query()
            ->where('role', 'designer')
            ->whereNotNull('subscription_trial_ends_at')
            ->update(['subscription_trial_used' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_trial_used',
                'subscription_payment_method',
                'subscription_cancelled_at',
            ]);
        });
    }
};
