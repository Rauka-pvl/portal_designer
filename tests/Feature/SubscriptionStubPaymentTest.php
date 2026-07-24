<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DesignerSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionStubPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_checkout_blocked_when_stub_payments_disabled(): void
    {
        config(['subscription.allow_stub_payments' => false]);
        config(['subscription.promo_code' => '']);

        $user = User::factory()->create([
            'role' => 'designer',
            'subscription_trial_used' => true,
            'subscription_trial_ends_at' => now()->subDay(),
            'subscription_ends_at' => null,
        ]);

        $this->expectException(ValidationException::class);

        DesignerSubscription::checkout(
            $user,
            DesignerSubscription::PLAN_STANDARD,
            DesignerSubscription::METHOD_KASPI
        );
    }

    public function test_trial_checkout_still_works_when_stub_payments_disabled(): void
    {
        config(['subscription.allow_stub_payments' => false]);
        config(['subscription.promo_code' => '']);

        $user = User::factory()->create([
            'role' => 'designer',
            'subscription_trial_used' => false,
            'subscription_trial_ends_at' => null,
            'subscription_ends_at' => null,
        ]);

        $payment = DesignerSubscription::checkout(
            $user,
            DesignerSubscription::PLAN_STANDARD,
            DesignerSubscription::METHOD_KASPI
        );

        $this->assertSame('trial', $payment->status);
        $this->assertSame(0, (int) $payment->amount);
        $user->refresh();
        $this->assertTrue((bool) $user->subscription_trial_used);
    }

    public function test_promo_code_comes_from_config_not_source_constant(): void
    {
        config(['subscription.allow_stub_payments' => false]);
        config(['subscription.promo_code' => 'Secret-Local-Promo']);

        $user = User::factory()->create([
            'role' => 'designer',
            'subscription_trial_used' => true,
            'subscription_trial_ends_at' => now()->subDay(),
            'subscription_ends_at' => null,
        ]);

        $payment = DesignerSubscription::checkout(
            $user,
            DesignerSubscription::PLAN_PRO,
            DesignerSubscription::METHOD_KASPI,
            'Secret-Local-Promo'
        );

        $this->assertSame(0, (int) $payment->amount);
        $this->assertSame(DesignerSubscription::METHOD_PROMO, $payment->meta['payment_method'] ?? null);
        $this->assertSame('', DesignerSubscription::PROMO_CODE);
    }
}
