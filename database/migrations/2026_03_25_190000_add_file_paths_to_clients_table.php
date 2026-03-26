<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Храним список путей к файлам в виде JSON-массива строк.
            // Пример: ["clients/..../a.pdf","clients/..../b.docx"]
            $table->text('file_paths')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('file_paths');
        });
    }
};

