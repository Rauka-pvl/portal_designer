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
    }
}
