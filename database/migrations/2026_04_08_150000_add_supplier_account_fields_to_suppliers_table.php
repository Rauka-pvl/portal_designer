<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('account_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('profile_status', 32)->default('draft')->after('account_user_id');
            $table->index(['profile_status', 'inn']);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_user_id');
            $table->dropIndex(['profile_status', 'inn']);
            $table->dropColumn('profile_status');
        });
    }
};
