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
        Schema::table('passport_objects', function (Blueprint $table) {
            $table->string('apartment_floor')->nullable()->after('apartment');
            $table->string('apartment_entrance')->nullable()->after('apartment_floor');
        });
    }

    public function down(): void
    {
        Schema::table('passport_objects', function (Blueprint $table) {
            $table->dropColumn(['apartment_floor', 'apartment_entrance']);
        });
    }
};
