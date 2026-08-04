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
        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('designer_teams')->nullOnDelete();
            $table->foreign('object_id')->references('id')->on('passport_objects')->nullOnDelete();
        });

        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
        });

        Schema::table('designer_tasks', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('team_id')->references('id')->on('designer_teams')->nullOnDelete();
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->foreign('related_order_id')->references('id')->on('supplier_orders')->nullOnDelete();
            $table->foreign('related_invitation_id')->references('id')->on('designer_team_invitations')->nullOnDelete();
        });

        Schema::table('designer_cashback_transactions', function (Blueprint $table) {
            $table->foreign('supplier_order_id')->references('id')->on('supplier_orders')->nullOnDelete();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('supplier_orders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('designer_cashback_transactions', function (Blueprint $table) {
            $table->dropForeign(['supplier_order_id']);
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropForeign(['related_order_id']);
            $table->dropForeign(['related_invitation_id']);
        });

        Schema::table('designer_tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['team_id']);
        });

        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['client_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropForeign(['object_id']);
        });
    }
};
