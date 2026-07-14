<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->string('offer_status', 32)->nullable()->after('bonus_percent');
            $table->string('offer_message', 1000)->nullable()->after('offer_status');
            $table->json('offer_history')->nullable()->after('offer_message');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->dropColumn(['offer_status', 'offer_message', 'offer_history']);
        });
    }
};
