<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement(
                'CREATE INDEX passport_objects_moderation_duplicate_idx ON passport_objects (city, apartment, apartment_entrance, apartment_floor, type)'
            );

            return;
        }

        // MySQL InnoDB ограничивает длину составного индекса (3072 байта).
        // В utf8mb4 VARCHAR(255) слишком длинный, поэтому делаем prefix-индекс.
        DB::statement(
            'CREATE INDEX passport_objects_moderation_duplicate_idx ON passport_objects (city(100), apartment(120), apartment_entrance(80), apartment_floor(80), type(32))'
        );
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS passport_objects_moderation_duplicate_idx');
        } else {
            DB::statement('DROP INDEX passport_objects_moderation_duplicate_idx ON passport_objects');
        }
    }
};
