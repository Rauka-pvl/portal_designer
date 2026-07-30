<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_stages', function (Blueprint $table) {
            if (! Schema::hasColumn('project_stages', 'name')) {
                $table->string('name')->nullable()->after('stage_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_stages', function (Blueprint $table) {
            if (Schema::hasColumn('project_stages', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
