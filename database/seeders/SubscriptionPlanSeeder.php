<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'key' => 'personal',
                'name' => 'Personal',
                'description' => 'Индивидуальный тариф для дизайнеров',
                'price' => 0.00,
                'currency' => 'KZT',
                'billing_period' => 'month',
                'included_seats' => 1,
                'status' => 'active',
            ],
            [
                'key' => 'standard',
                'name' => 'Standard',
                'description' => 'Базовый платный тариф',
                'price' => 5000.00,
                'currency' => 'KZT',
                'billing_period' => 'month',
                'included_seats' => 1,
                'status' => 'active',
            ],
            [
                'key' => 'pro',
                'name' => 'Pro',
                'description' => 'Расширенный тариф для дизайнеров',
                'price' => 9990.00,
                'currency' => 'KZT',
                'billing_period' => 'month',
                'included_seats' => 1,
                'status' => 'active',
            ],
            [
                'key' => 'corporate',
                'name' => 'Corporate',
                'description' => 'Корпоративный тариф для команд до 5 человек',
                'price' => 29990.00,
                'currency' => 'KZT',
                'billing_period' => 'month',
                'included_seats' => 5,
                'status' => 'active',
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['key' => $plan['key']],
                array_merge($plan, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
