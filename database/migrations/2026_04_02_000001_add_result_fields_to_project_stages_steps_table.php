<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_stages_steps', function (Blueprint $table) {
            $table->string('result_status')->default('pending');
            $table->text('result_comment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_stages_steps', function (Blueprint $table) {
            $table->dropColumn(['result_status', 'result_comment']);
        });
    }
};

