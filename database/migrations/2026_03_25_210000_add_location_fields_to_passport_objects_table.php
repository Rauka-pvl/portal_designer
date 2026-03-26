<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passport_objects', function (Blueprint $table) {
            $table->string('city')->nullable()->after('client_id');
            $table->string('apartment')->nullable()->after('address');
            $table->decimal('latitude', 10, 7)->nullable()->after('comment');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('passport_objects', function (Blueprint $table) {
            $table->dropColumn(['city', 'apartment', 'latitude', 'longitude']);
        });
    }
};

