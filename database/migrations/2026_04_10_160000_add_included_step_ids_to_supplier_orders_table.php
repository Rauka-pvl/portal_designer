<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_orders', 'included_step_ids')) {
                $table->json('included_step_ids')->nullable()->after('project_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_orders', 'included_step_ids')) {
                $table->dropColumn('included_step_ids');
            }
        });
    }
};
