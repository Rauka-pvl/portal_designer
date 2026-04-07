<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_confirmed_by_designer')->default(true)->after('moderation_reviewed_at');
            $table->boolean('is_referral_submitted')->default(false)->after('is_confirmed_by_designer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'is_confirmed_by_designer',
                'is_referral_submitted',
            ]);
        });
    }
};
