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
        Schema::create('designer_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->integer('max_members')->default(5);
            $table->timestamps();

            $table->index('owner_id');
            $table->index('status');
        });

        Schema::create('designer_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('designer_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('role', ['owner', 'admin', 'designer'])->default('designer');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index('team_id');
            $table->index('user_id');
            $table->index('role');
            $table->index('status');
            $table->index(['team_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('designer_team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('designer_teams')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->enum('role', ['admin', 'designer'])->default('designer');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired', 'cancelled'])->default('pending');
            $table->string('token', 100)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index('team_id');
            $table->index('invited_by');
            $table->index('email');
            $table->index('status');
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designer_team_invitations');
        Schema::dropIfExists('designer_team_members');
        Schema::dropIfExists('designer_teams');
    }
};
