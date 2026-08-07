<?php

namespace Tests\Feature;

use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use App\Enums\TeamRole;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Support\SupportTicketService;
use App\Services\Team\TeamService;
use App\Support\DesignerSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportApiTest extends TestCase
{
    use RefreshDatabase;

    private function designer(array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'account_type' => 'designer',
        ], $attrs));

        $planKey = array_key_exists('subscription_plan', $attrs)
            ? $attrs['subscription_plan']
            : DesignerSubscription::PLAN_PRO;

        if ($planKey) {
            $plan = SubscriptionPlan::findByKey($planKey);
            if ($plan) {
                Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => $attrs['subscription_ends_at'] ?? now()->addDays(20),
                ]);
            }
        }

        return $user;
    }

    private function ticketData(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'API ticket subject',
            'category' => SupportCategory::SiteError->value,
            'message' => 'API ticket body',
        ], $overrides);
    }

    public function test_meta_returns_categories_statuses_and_attachment_limits(): void
    {
        Sanctum::actingAs($this->designer());

        $this->getJson('/api/support/meta')
            ->assertOk()
            ->assertJsonPath('data.categories.0.value', SupportCategory::SiteError->value)
            ->assertJsonStructure([
                'data' => [
                    'categories' => [['value', 'label']],
                    'statuses' => [['value', 'label', 'is_open']],
                    'attachments' => ['max_files', 'max_file_kb', 'allowed_extensions'],
                ],
            ]);
    }

    public function test_designer_can_create_list_and_show_ticket(): void
    {
        $user = $this->designer();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/support', $this->ticketData())
            ->assertCreated()
            ->assertJsonPath('data.subject', 'API ticket subject')
            ->assertJsonPath('data.category', SupportCategory::SiteError->value)
            ->assertJsonPath('data.status', SupportTicketStatus::New->value)
            ->assertJsonPath('data.can_reply', true)
            ->assertJsonPath('data.messages.0.message', 'API ticket body');

        $ticketId = (int) $create->json('data.id');
        $this->assertNotEmpty($create->json('data.number'));

        $this->getJson('/api/support')
            ->assertOk()
            ->assertJsonPath('data.0.id', $ticketId)
            ->assertJsonStructure([
                'data' => [['id', 'number', 'subject', 'status', 'messages_count']],
                'links',
                'meta',
            ]);

        $this->getJson('/api/support/'.$ticketId)
            ->assertOk()
            ->assertJsonPath('data.id', $ticketId)
            ->assertJsonPath('data.messages.0.sender.id', $user->id)
            ->assertJsonCount(1, 'data.messages');
    }

    public function test_designer_can_reply_and_status_moves_from_waiting(): void
    {
        $user = $this->designer();
        $admin = User::factory()->create(['account_type' => 'system_admin']);
        $service = app(SupportTicketService::class);

        $ticket = $service->createTicket($user, $this->ticketData());
        $service->reply($ticket, $admin, 'Admin answer');
        $ticket->refresh();
        $this->assertSame(SupportTicketStatus::WaitingForUser, $ticket->statusEnum());

        Sanctum::actingAs($user);
        $this->postJson('/api/support/'.$ticket->id.'/reply', [
            'message' => 'User follow-up',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', SupportTicketStatus::InProgress->value)
            ->assertJsonPath('data.messages.2.message', 'User follow-up');
    }

    public function test_cannot_view_foreign_ticket(): void
    {
        $owner = $this->designer();
        $other = $this->designer(['email' => 'other@example.com']);
        $ticket = app(SupportTicketService::class)->createTicket($owner, $this->ticketData());

        Sanctum::actingAs($other);
        $this->getJson('/api/support/'.$ticket->id)->assertForbidden();
        $this->postJson('/api/support/'.$ticket->id.'/reply', [
            'message' => 'Nope',
        ])->assertForbidden();
    }

    public function test_team_owner_sees_member_tickets_in_list(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PROGRESS]);
        $member = $this->designer([
            'email' => 'member@example.com',
            'subscription_plan' => null,
        ]);

        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey('progress'));
        $inv = $teams->addExistingUser($team->fresh(), $owner, $member, TeamRole::Designer);
        $teams->acceptInvitation($member, $inv);

        $memberTicket = app(SupportTicketService::class)->createTicket($member, $this->ticketData([
            'subject' => 'Member issue',
        ]));

        Sanctum::actingAs($owner);
        $this->getJson('/api/support')
            ->assertOk()
            ->assertJsonFragment(['id' => $memberTicket->id, 'subject' => 'Member issue']);
    }

    public function test_create_with_attachment_and_download(): void
    {
        Storage::fake('local');
        $user = $this->designer();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('shot.png', 120, 'image/png');

        $create = $this->post('/api/support', $this->ticketData([
            'attachments' => [$file],
        ]), ['Accept' => 'application/json'])
            ->assertCreated();

        $attachmentId = (int) $create->json('data.messages.0.attachments.0.id');
        $this->assertGreaterThan(0, $attachmentId);
        $this->assertNotEmpty($create->json('data.messages.0.attachments.0.download_url'));

        $this->get('/api/support/attachments/'.$attachmentId.'/download')
            ->assertOk();
    }

    public function test_download_forbidden_for_outsider(): void
    {
        Storage::fake('local');
        $owner = $this->designer();
        $other = $this->designer(['email' => 'outsider@example.com']);

        Sanctum::actingAs($owner);
        $file = UploadedFile::fake()->create('doc.txt', 10, 'text/plain');
        $create = $this->post('/api/support', $this->ticketData([
            'attachments' => [$file],
        ]), ['Accept' => 'application/json'])->assertCreated();

        $attachmentId = (int) $create->json('data.messages.0.attachments.0.id');

        Sanctum::actingAs($other);
        $this->getJson('/api/support/attachments/'.$attachmentId.'/download')
            ->assertForbidden();
    }

    public function test_validation_rejects_bad_payload_and_file_type(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->designer());

        $this->postJson('/api/support', [
            'subject' => '',
            'category' => 'not-a-category',
            'message' => '',
        ])->assertStatus(422);

        $bad = UploadedFile::fake()->create('evil.php', 10, 'application/x-php');
        $this->post('/api/support', array_merge($this->ticketData(), [
            'attachments' => [$bad],
        ]), ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_cannot_reply_to_closed_ticket(): void
    {
        $user = $this->designer();
        $admin = User::factory()->create(['account_type' => 'system_admin']);
        $service = app(SupportTicketService::class);
        $ticket = $service->createTicket($user, $this->ticketData());
        $service->changeStatus($ticket, $admin, SupportTicketStatus::Closed);

        Sanctum::actingAs($user);
        $this->postJson('/api/support/'.$ticket->id.'/reply', [
            'message' => 'Too late',
        ])->assertForbidden();
    }

    public function test_unauthenticated_and_without_subscription_are_blocked(): void
    {
        $this->getJson('/api/support')->assertUnauthorized();

        $user = User::factory()->create([
            'account_type' => 'designer',
            'subscription_trial_ends_at' => now()->subDay(),
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/support')->assertStatus(402);
        $this->postJson('/api/support', $this->ticketData())->assertStatus(402);
    }

    public function test_list_filters_by_status_and_search(): void
    {
        $user = $this->designer();
        $service = app(SupportTicketService::class);
        $a = $service->createTicket($user, $this->ticketData(['subject' => 'Alpha bug']));
        $b = $service->createTicket($user, $this->ticketData(['subject' => 'Beta payment']));
        $service->changeStatus($b, User::factory()->create(['account_type' => 'system_admin']), SupportTicketStatus::Resolved);

        Sanctum::actingAs($user);

        $this->getJson('/api/support?status=new')
            ->assertOk()
            ->assertJsonFragment(['subject' => 'Alpha bug'])
            ->assertJsonMissing(['subject' => 'Beta payment']);

        $this->getJson('/api/support?search=Beta')
            ->assertOk()
            ->assertJsonFragment(['subject' => 'Beta payment'])
            ->assertJsonMissing(['subject' => 'Alpha bug']);
    }
}
