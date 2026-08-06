<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DesignerSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PromoOnlySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subscription.trial_enabled' => false,
            'subscription.payments_enabled' => false,
            'subscription.allow_stub_payments' => false,
            'subscription.promo_code' => 'Launch-6M',
            'subscription.promo_starts_at' => now()->toDateString(),
            'subscription.promo_valid_days' => 7,
            'subscription.promo_period_days' => 180,
        ]);
    }

    private function designer(): User
    {
        return User::factory()->create([
            'account_type' => 'designer',
            'subscription_trial_used' => false,
            'subscription_trial_ends_at' => null,
            'subscription_ends_at' => null,
            'subscription_plan' => null,
        ]);
    }

    public function test_kaspi_checkout_blocked_without_promo(): void
    {
        $user = $this->designer();

        $this->expectException(ValidationException::class);

        DesignerSubscription::checkout(
            $user,
            DesignerSubscription::PLAN_STANDARD,
            DesignerSubscription::METHOD_KASPI
        );
    }

    public function test_trial_disabled_in_promo_only_mode(): void
    {
        $user = $this->designer();
        $this->assertFalse(DesignerSubscription::canUseTrial($user));
    }

    public function test_valid_promo_grants_six_months(): void
    {
        $user = $this->designer();

        $payment = DesignerSubscription::checkout(
            $user,
            DesignerSubscription::PLAN_PRO,
            DesignerSubscription::METHOD_PROMO,
            'Launch-6M'
        );

        $this->assertSame(0, (int) $payment->amount);
        $this->assertSame(180, (int) $payment->period_days);
        $this->assertSame('completed', $payment->status);
        $this->assertTrue((bool) ($payment->meta['is_promo'] ?? false));

        $user->refresh();
        $this->assertTrue(DesignerSubscription::hasAccess($user));
        $this->assertSame(DesignerSubscription::PLAN_SUCCESS, $user->subscription_plan);
        $this->assertNotNull($user->subscription_ends_at);
        $this->assertTrue($user->subscription_ends_at->greaterThan(now()->addDays(170)));
    }

    public function test_promo_overrides_selected_plan_with_promo_plan(): void
    {
        $user = $this->designer();

        // User picks Base, but the promo always grants Success.
        $payment = DesignerSubscription::checkout(
            $user,
            DesignerSubscription::PLAN_BASE,
            DesignerSubscription::METHOD_PROMO,
            'Launch-6M'
        );

        $this->assertSame(DesignerSubscription::PLAN_SUCCESS, (string) $payment->plan);
        $user->refresh();
        $this->assertSame(DesignerSubscription::PLAN_SUCCESS, $user->subscription_plan);
        // Success is corporate — the owner gets a team with unlimited seats.
        $team = app(\App\Services\Team\TeamService::class)->activeTeamFor($user);
        $this->assertNotNull($team);
        $this->assertNull($team->max_members);
    }

    public function test_promo_rejected_outside_seven_day_window(): void
    {
        config(['subscription.promo_starts_at' => now()->subDays(10)->toDateString()]);

        $user = $this->designer();
        $this->assertFalse(DesignerSubscription::promoWindowActive());

        $this->expectException(ValidationException::class);

        DesignerSubscription::checkout(
            $user,
            DesignerSubscription::PLAN_STANDARD,
            DesignerSubscription::METHOD_PROMO,
            'Launch-6M'
        );
    }

    public function test_web_purchase_requires_promo_and_redirects_to_dashboard(): void
    {
        $user = $this->designer();

        $this->actingAs($user)
            ->post(route('subscription.purchase'), [
                'plan' => DesignerSubscription::PLAN_STANDARD,
                'payment_method' => 'promo',
                'promo_code' => 'Launch-6M',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(DesignerSubscription::hasAccess($user->fresh()));
    }

    public function test_web_blocks_cabinet_until_promo_activation(): void
    {
        $user = $this->designer();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.index'));

        $this->actingAs($user)
            ->get(route('subscription.checkout', ['plan' => 'pro']))
            ->assertOk();
    }

    public function test_api_checkout_with_promo(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_PROGRESS,
            'payment_method' => 'promo',
            'promo_code' => 'Launch-6M',
        ])
            ->assertCreated()
            ->assertJsonPath('data.subscription.has_access', true)
            ->assertJsonPath('data.payment.amount_raw', 0);

        $this->assertSame(180, (int) $user->fresh()->subscriptionPayments()->latest('id')->first()?->period_days);
    }

    public function test_api_checkout_without_promo_fails(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => 'kaspi',
        ])->assertStatus(422);
    }
}
