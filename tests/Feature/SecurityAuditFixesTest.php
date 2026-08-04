<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\DesignerTeam;
use App\Models\DesignerTeamMember;
use App\Models\User;
use App\Services\Team\TeamService;
use App\Support\DesignerSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private function designer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'account_type' => 'designer',
            'subscription_plan' => DesignerSubscription::PLAN_PRO,
            'subscription_ends_at' => now()->addMonth(),
            'subscription_trial_ends_at' => null,
        ], $overrides));
    }

    public function test_supplier_cannot_access_designer_business_api(): void
    {
        $supplier = User::factory()->create([
            'account_type' => 'supplier',
        ]);

        Sanctum::actingAs($supplier);

        $this->getJson('/api/projects')->assertForbidden();
        $this->getJson('/api/clients')->assertForbidden();
        $this->getJson('/api/tasks')->assertForbidden();
    }

    public function test_change_role_rejects_member_from_another_team(): void
    {
        $ownerA = $this->designer(['email' => 'owner-a@example.com']);
        $ownerB = $this->designer(['email' => 'owner-b@example.com']);
        $memberB = $this->designer([
            'email' => 'member-b@example.com',
            'subscription_plan' => null,
            'subscription_ends_at' => null,
        ]);

        $teams = app(TeamService::class);
        $teamA = $teams->activateCorporateForOwner($ownerA);
        $teamB = $teams->activateCorporateForOwner($ownerB);

        $ownerA->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();
        $ownerB->forceFill([
            'subscription_plan' => DesignerSubscription::PLAN_CORPORATE,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();

        $invitation = $teams->addExistingUser($teamB, $ownerB, $memberB, TeamRole::Designer);
        $teams->acceptInvitation($memberB, $invitation);

        $foreignMember = DesignerTeamMember::query()
            ->where('team_id', $teamB->id)
            ->where('user_id', $memberB->id)
            ->firstOrFail();

        $this->expectException(ValidationException::class);
        $teams->changeRole($teamA, $ownerA, $foreignMember, TeamRole::Admin);
    }

    public function test_deposit_demo_defaults_to_false_when_config_missing(): void
    {
        config()->set('supplier_deposit.demo', null);

        $this->assertFalse(\App\Support\SupplierDeposit::isDemo());
    }
}
