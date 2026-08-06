<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DesignerSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    private function designerWithoutSub(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'account_type' => 'designer',
            'subscription_trial_used' => false,
            'subscription_trial_ends_at' => null,
            'subscription_ends_at' => null,
            'subscription_plan' => null,
        ], $overrides));
    }

    public function test_subscription_endpoints_require_auth(): void
    {
        $this->getJson('/api/subscription/plans')->assertUnauthorized();
        $this->getJson('/api/subscription')->assertUnauthorized();
        $this->postJson('/api/subscription/checkout', [
            'plan' => 'standard',
            'payment_method' => 'kaspi',
        ])->assertUnauthorized();
    }

    public function test_register_marks_subscription_required_for_designer(): void
    {
        $this->postJson('/api/register', [
            'name' => 'New Designer',
            'email' => 'new-sub@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'portal' => 'designer',
        ])
            ->assertCreated()
            ->assertJsonPath('subscription_required', true)
            ->assertJsonPath('user.subscription.has_access', false)
            ->assertJsonPath('user.subscription.can_use_trial', true);
    }

    public function test_plans_show_and_trial_checkout_grants_access(): void
    {
        $user = $this->designerWithoutSub();
        Sanctum::actingAs($user);

        $plans = $this->getJson('/api/subscription/plans')->assertOk();
        $keys = collect($plans->json('data.plans'))->pluck('key');
        $this->assertTrue($keys->contains('standard'));
        $this->assertTrue($keys->contains('pro'));
        $this->assertTrue($keys->contains('economy'));
        $this->assertTrue($keys->contains('progress'));
        $this->assertTrue($keys->contains('success'));

        $this->getJson('/api/subscription')
            ->assertOk()
            ->assertJsonPath('data.has_access', false)
            ->assertJsonPath('data.can_manage_billing', true);

        $checkout = $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertCreated();

        $this->assertTrue((bool) $checkout->json('data.subscription.has_access'));
        $this->assertTrue((bool) $checkout->json('data.subscription.is_on_trial'));
        $this->assertSame('trial', $checkout->json('data.payment.status'));
        $this->assertSame(0, (int) $checkout->json('data.payment.amount'));

        $user->refresh();
        $this->assertTrue(DesignerSubscription::hasAccess($user));
        $this->assertTrue((bool) $user->subscription_trial_used);

        $this->getJson('/api/subscription')
            ->assertOk()
            ->assertJsonPath('data.has_access', true)
            ->assertJsonPath('data.plan', DesignerSubscription::PLAN_STANDARD);

        $history = $this->getJson('/api/subscription/history')->assertOk();
        $this->assertNotEmpty($history->json('data.payments'));
    }

    public function test_cancel_and_resume_via_api(): void
    {
        $user = $this->designerWithoutSub();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_PRO,
            'payment_method' => DesignerSubscription::METHOD_CARD,
        ])->assertCreated();

        $this->postJson('/api/subscription/cancel', [
            'reason' => 'expensive',
        ])->assertOk();

        $user->refresh();
        $this->assertNotNull($user->subscription_cancelled_at);

        // While trial/access window still valid, has_access may remain true until end date.
        $this->postJson('/api/subscription/resume')->assertOk();
        $user->refresh();
        $this->assertNull($user->subscription_cancelled_at);
    }

    public function test_supplier_cannot_manage_billing(): void
    {
        $supplier = User::factory()->create(['account_type' => 'supplier']);
        Sanctum::actingAs($supplier);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertForbidden();
    }

    public function test_paid_checkout_after_trial_works_when_stub_enabled(): void
    {
        config(['subscription.allow_stub_payments' => true]);

        $user = $this->designerWithoutSub();
        Sanctum::actingAs($user);

        // Consume free trial first.
        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertCreated()->assertJsonPath('data.payment.status', 'trial');

        // Expire trial window so next checkout is paid.
        $user->refresh();
        $user->subscription_trial_ends_at = now()->subDay();
        $user->subscription_ends_at = null;
        $user->save();

        $this->assertFalse(DesignerSubscription::canUseTrial($user->fresh()));

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])
            ->assertCreated()
            ->assertJsonPath('data.subscription.has_access', true)
            ->assertJsonPath('data.payment.status', 'completed');

        $this->assertGreaterThan(0, (int) $user->fresh()->subscriptionPayments()->latest('id')->first()?->amount);
    }

    public function test_paid_checkout_blocked_via_api_when_stub_disabled(): void
    {
        config(['subscription.allow_stub_payments' => false]);
        config(['subscription.promo_code' => '']);

        $user = $this->designerWithoutSub();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertCreated()->assertJsonPath('data.payment.status', 'trial');

        $user->refresh();
        $user->subscription_trial_ends_at = now()->subDay();
        $user->subscription_ends_at = null;
        $user->save();

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertStatus(422);
    }

    public function test_business_api_returns_402_without_access_after_onboarding_window(): void
    {
        $user = $this->designerWithoutSub([
            'created_at' => now()->subDays(2),
            'subscription_trial_used' => true,
            'subscription_trial_ends_at' => now()->subDay(),
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/clients')
            ->assertStatus(402)
            ->assertJsonPath('code', 'subscription_required');
    }

    public function test_login_subscription_required_flag_matches_access(): void
    {
        $user = $this->designerWithoutSub([
            'email' => 'flag-check@example.com',
            'password' => bcrypt('Password1!'),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password1!',
            'portal' => 'designer',
        ])
            ->assertOk()
            ->assertJsonPath('subscription_required', true);

        Sanctum::actingAs($user);
        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertCreated();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password1!',
            'portal' => 'designer',
        ])
            ->assertOk()
            ->assertJsonPath('subscription_required', false);
    }

    public function test_plans_include_comparison_features(): void
    {
        $user = $this->designerWithoutSub();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/subscription/plans')->assertOk();
        $this->assertNotEmpty($response->json('data.comparison_feature_keys'));
        $this->assertIsBool($response->json('data.plans.0.comparison_features.feature_team'));
        $this->assertSame(false, $response->json('data.trial_requires_card'));
    }

    public function test_show_returns_enriched_subscription_fields(): void
    {
        $user = $this->designerWithoutSub();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertCreated();

        $this->getJson('/api/subscription')
            ->assertOk()
            ->assertJsonPath('data.can_use_trial', false)
            ->assertJsonPath('data.is_on_trial', true)
            ->assertJsonPath('data.trial_requires_card', false)
            ->assertJsonStructure([
                'data' => [
                    'trial_progress',
                    'trial_total_days',
                    'primary_action' => ['key', 'label'],
                    'is_onboarding',
                    'has_real_payments',
                    'is_corporate',
                    'auto_renew',
                    'payment_method',
                ],
            ]);
    }

    public function test_update_payment_method_via_api(): void
    {
        $user = $this->designerWithoutSub();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertCreated();

        $this->postJson('/api/subscription/payment-method', [
            'payment_method' => DesignerSubscription::METHOD_CARD,
        ])
            ->assertOk()
            ->assertJsonPath('data.payment_method', DesignerSubscription::METHOD_CARD);

        $this->assertSame(DesignerSubscription::METHOD_CARD, $user->fresh()->subscription_payment_method);

        $this->postJson('/api/subscription/payment', [
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])
            ->assertOk()
            ->assertJsonPath('data.payment_method', DesignerSubscription::METHOD_KASPI);
    }

    public function test_change_plan_via_api(): void
    {
        $user = $this->designerWithoutSub();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertCreated();

        $this->postJson('/api/subscription/change-plan', [
            'plan' => DesignerSubscription::PLAN_PRO,
        ])
            ->assertOk()
            ->assertJsonPath('data.plan', DesignerSubscription::PLAN_PRO);
    }

    public function test_history_marks_trial_payments(): void
    {
        $user = $this->designerWithoutSub();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_STANDARD,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertCreated();

        $this->getJson('/api/subscription/history')
            ->assertOk()
            ->assertJsonPath('data.payments.0.is_trial', true)
            ->assertJsonPath('data.payments.0.has_receipt', false)
            ->assertJsonPath('data.has_real_payments', false);
    }

    public function test_team_add_existing_member_via_api(): void
    {
        config(['subscription.allow_stub_payments' => true]);

        $owner = $this->designerWithoutSub(['email' => 'corp-owner@example.com']);
        $member = $this->designerWithoutSub(['email' => 'corp-member@example.com']);
        Sanctum::actingAs($owner);

        $this->postJson('/api/subscription/checkout', [
            'plan' => DesignerSubscription::PLAN_PROGRESS,
            'payment_method' => DesignerSubscription::METHOD_KASPI,
        ])->assertCreated();

        $this->postJson('/api/team/members/add', [
            'email' => $member->email,
            'role' => 'designer',
        ])
            ->assertCreated()
            ->assertJsonPath('data.email', $member->email);
    }
}
