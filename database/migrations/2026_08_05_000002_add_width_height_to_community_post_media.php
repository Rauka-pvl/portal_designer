<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('community_post_media')) {
            return;
        }

        Schema::table('community_post_media', function (Blueprint $table) {
            if (! Schema::hasColumn('community_post_media', 'width')) {
                $table->unsignedInteger('width')->nullable()->after('file_type');
            }
            if (! Schema::hasColumn('community_post_media', 'height')) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('community_post_media')) {
            return;
        }

        Schema::table('community_post_media', function (Blueprint $table) {
            if (Schema::hasColumn('community_post_media', 'width')) {
                $table->dropColumn('width');
            }
            if (Schema::hasColumn('community_post_media', 'height')) {
                $table->dropColumn('height');
            }
        });
    }
};
