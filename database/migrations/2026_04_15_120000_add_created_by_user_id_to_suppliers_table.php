<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('account_user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('suppliers as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->whereNull('s.created_by_user_id')
            ->where('u.role', '=', 'designer')
            ->update([
                's.created_by_user_id' => DB::raw('s.user_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};
