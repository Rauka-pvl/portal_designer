<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passport_objects', function (Blueprint $table) {
            $table->dropIndex('passport_objects_moderation_duplicate_idx');
        });

        DB::statement(
            'CREATE INDEX passport_objects_moderation_duplicate_idx ON passport_objects (city(100), apartment(120), apartment_entrance(80), apartment_floor(80), type(32))'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX passport_objects_moderation_duplicate_idx ON passport_objects');
    }
};

