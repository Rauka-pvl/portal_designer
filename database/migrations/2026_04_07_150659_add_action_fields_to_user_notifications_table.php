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
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('related_supplier_id')->nullable()->after('read_at');
            $table->string('action_key', 64)->nullable()->after('related_supplier_id');

            $table->index(['user_id', 'action_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'action_key']);
            $table->dropColumn([
                'related_supplier_id',
                'action_key',
            ]);
        });
    }
};
