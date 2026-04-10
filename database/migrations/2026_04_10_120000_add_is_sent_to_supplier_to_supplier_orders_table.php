<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->boolean('is_sent_to_supplier')->default(false)->after('status');
        });

        DB::table('supplier_orders')
            ->whereIn('status', [
                'order_sent',
                'order_confirmed',
                'advance_payment',
                'full_payment',
                'delivery_completed',
            ])
            ->update(['is_sent_to_supplier' => true]);
    }

    public function down(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->dropColumn('is_sent_to_supplier');
        });
    }
};
