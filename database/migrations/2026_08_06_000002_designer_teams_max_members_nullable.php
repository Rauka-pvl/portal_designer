<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('designer_teams')) {
            return;
        }

        // null max_members = unlimited seats (Success plan)
        Schema::table('designer_teams', function (Blueprint $table) {
            $table->unsignedInteger('max_members')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('designer_teams')) {
            return;
        }

        Schema::table('designer_teams', function (Blueprint $table) {
            $table->unsignedInteger('max_members')->nullable(false)->default(5)->change();
        });
    }
};
