<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Idempotent: upserts the six current plans and archives legacy ones
     * without touching existing subscriptions or any user data.
     */
    public function run(): void
    {
        $now = now();

        $plans = [
            // --- Individual ---
            [
                'key' => 'base',
                'type' => 'individual',
                'name' => 'Base',
                'description' => 'Стартовый тариф для самостоятельных дизайнеров',
                'price' => 9990.00,
                'max_users' => 1,
                'max_projects' => 2,
                'priority_support' => false,
                'annual_discount_percent' => 5,
                'annual_price' => 113886.00,
                'feature_keys' => json_encode(['feature_clients', 'feature_projects', 'feature_orders', 'feature_reports', 'feature_support']),
                'recommended' => false,
                'included_seats' => 1,
                'sort_order' => 10,
            ],
            [
                'key' => 'standard',
                'type' => 'individual',
                'name' => 'Standard',
                'description' => 'Оптимальный тариф для активной практики',
                'price' => 13500.00,
                'max_users' => 1,
                'max_projects' => 5,
                'priority_support' => false,
                'annual_discount_percent' => 10,
                'annual_price' => 145800.00,
                'feature_keys' => json_encode(['feature_clients', 'feature_projects', 'feature_orders', 'feature_reports', 'feature_support', 'feature_analytics']),
                'recommended' => true,
                'included_seats' => 1,
                'sort_order' => 20,
            ],
            [
                'key' => 'pro',
                'type' => 'individual',
                'name' => 'Pro',
                'description' => 'Безлимитные проекты и приоритетная поддержка',
                'price' => 17500.00,
                'max_users' => 1,
                'max_projects' => null,
                'priority_support' => true,
                'annual_discount_percent' => 15,
                'annual_price' => 178500.00,
                'feature_keys' => json_encode(['feature_unlimited', 'feature_analytics', 'feature_priority', 'feature_pro_tools', 'feature_cashback', 'feature_suppliers']),
                'recommended' => false,
                'included_seats' => 1,
                'sort_order' => 30,
            ],
            // --- Corporate ---
            [
                'key' => 'economy',
                'type' => 'corporate',
                'name' => 'Economy',
                'description' => 'Для небольших студий: владелец + 2 участника',
                'price' => 25000.00,
                'max_users' => 3,
                'max_projects' => 6,
                'priority_support' => false,
                'annual_discount_percent' => 5,
                'annual_price' => 285000.00,
                'feature_keys' => json_encode(['feature_clients', 'feature_projects', 'feature_orders', 'feature_reports', 'feature_support', 'feature_team', 'feature_roles', 'feature_assignees', 'feature_team_projects']),
                'recommended' => false,
                'included_seats' => 3,
                'sort_order' => 40,
            ],
            [
                'key' => 'progress',
                'type' => 'corporate',
                'name' => 'Progress',
                'description' => 'Для растущих команд: владелец + 6 участников',
                'price' => 35000.00,
                'max_users' => 7,
                'max_projects' => 15,
                'priority_support' => false,
                'annual_discount_percent' => 10,
                'annual_price' => 378000.00,
                'feature_keys' => json_encode(['feature_clients', 'feature_projects', 'feature_orders', 'feature_reports', 'feature_support', 'feature_analytics', 'feature_team', 'feature_roles', 'feature_assignees', 'feature_team_projects']),
                'recommended' => true,
                'included_seats' => 7,
                'sort_order' => 50,
            ],
            [
                'key' => 'success',
                'type' => 'corporate',
                'name' => 'Success',
                'description' => 'Безлимитная команда и проекты, приоритетная поддержка',
                'price' => 50000.00,
                'max_users' => null,
                'max_projects' => null,
                'priority_support' => true,
                'annual_discount_percent' => 15,
                'annual_price' => 510000.00,
                'feature_keys' => json_encode(['feature_unlimited', 'feature_analytics', 'feature_priority', 'feature_pro_tools', 'feature_cashback', 'feature_suppliers', 'feature_team', 'feature_roles', 'feature_assignees', 'feature_team_projects']),
                'recommended' => false,
                'included_seats' => 0,
                'sort_order' => 60,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['key' => $plan['key']],
                array_merge($plan, [
                    'currency' => 'KZT',
                    'billing_period' => 'month',
                    'status' => 'active',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        // Archive legacy plans: keep rows (FK safety) but remove from sale.
        // Existing subscriptions keep working until manually cleaned up.
        DB::table('subscription_plans')
            ->whereIn('key', ['corporate'])
            ->update([
                'type' => 'corporate',
                'status' => 'archived',
                'is_active' => false,
                'updated_at' => $now,
            ]);

        DB::table('subscription_plans')
            ->whereIn('key', ['personal'])
            ->update([
                'type' => 'individual',
                'status' => 'archived',
                'is_active' => false,
                'updated_at' => $now,
            ]);

        app(\App\Services\Billing\PlanCatalog::class)->forgetCache();
    }
}
