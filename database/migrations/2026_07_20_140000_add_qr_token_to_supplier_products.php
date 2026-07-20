<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_products', 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->unique()->after('image_path');
            }
            if (! Schema::hasColumn('supplier_products', 'qr_version')) {
                $table->unsignedInteger('qr_version')->default(1)->after('qr_token');
            }
            if (! Schema::hasColumn('supplier_products', 'qr_generated_at')) {
                $table->timestamp('qr_generated_at')->nullable()->after('qr_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_products', 'qr_generated_at')) {
                $table->dropColumn('qr_generated_at');
            }
            if (Schema::hasColumn('supplier_products', 'qr_version')) {
                $table->dropColumn('qr_version');
            }
            if (Schema::hasColumn('supplier_products', 'qr_token')) {
                $table->dropUnique(['qr_token']);
                $table->dropColumn('qr_token');
            }
        });
    }
};
