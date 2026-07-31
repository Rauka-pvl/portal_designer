<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('related_invitation_id')->nullable()->after('related_post_id');
            $table->index(['user_id', 'related_invitation_id']);
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'related_invitation_id']);
            $table->dropColumn('related_invitation_id');
        });
    }
};
