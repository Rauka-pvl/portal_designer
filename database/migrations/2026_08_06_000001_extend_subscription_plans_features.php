<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('type', 20)->default('individual')->after('key');
            $table->unsignedInteger('max_users')->nullable()->after('included_seats'); // null = unlimited
            $table->unsignedInteger('max_projects')->nullable()->after('max_users'); // null = unlimited
            $table->boolean('priority_support')->default(false)->after('max_projects');
            $table->unsignedTinyInteger('annual_discount_percent')->default(0)->after('priority_support');
            $table->decimal('annual_price', 12, 2)->nullable()->after('annual_discount_percent');
            $table->json('feature_keys')->nullable()->after('annual_price');
            $table->boolean('recommended')->default(false)->after('feature_keys');
            $table->boolean('is_active')->default(true)->after('status');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');

            $table->index(['type', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_active']);
            $table->dropIndex(['sort_order']);
            $table->dropColumn([
                'type',
                'max_users',
                'max_projects',
                'priority_support',
                'annual_discount_percent',
                'annual_price',
                'feature_keys',
                'recommended',
                'is_active',
                'sort_order',
            ]);
        });
    }
};
