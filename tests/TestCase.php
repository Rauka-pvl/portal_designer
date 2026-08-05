<?php

namespace Tests;

use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed required data for tests
        $this->seed(SubscriptionPlanSeeder::class);

        // Most feature tests still exercise trial/stub checkout paths.
        // Promo-only product mode is covered by dedicated subscription tests.
        config([
            'subscription.trial_enabled' => true,
            'subscription.payments_enabled' => true,
            'subscription.allow_stub_payments' => true,
            'subscription.promo_code' => '',
            'subscription.promo_starts_at' => null,
        ]);
    }
}
