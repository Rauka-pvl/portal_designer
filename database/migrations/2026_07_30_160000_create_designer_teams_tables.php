<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designer_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 20)->default('active'); // active|inactive|archived
            $table->unsignedTinyInteger('max_members')->default(5);
            $table->timestamps();

            $table->index(['owner_id', 'status']);
        });

        Schema::create('designer_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('designer_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20); // owner|admin|designer
            $table->string('status', 20)->default('active'); // active|blocked|inactive
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['team_id', 'status']);
        });

        Schema::create('designer_team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('designer_teams')->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 20)->default('designer');
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('pending'); // pending|accepted|cancelled|expired
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['email', 'status']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('team_id')
                ->nullable()
                ->after('user_id')
                ->constrained('designer_teams')
                ->nullOnDelete();
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });
        Schema::dropIfExists('designer_team_invitations');
        Schema::dropIfExists('designer_team_members');
        Schema::dropIfExists('designer_teams');
    }
};
