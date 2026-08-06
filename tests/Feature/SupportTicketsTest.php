<?php

namespace Tests\Feature;

use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use App\Enums\TeamRole;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Support\SupportTicketService;
use App\Services\Team\TeamService;
use App\Support\DesignerSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportTicketsTest extends TestCase
{
    use RefreshDatabase;

    private function designer(array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'account_type' => 'designer',
        ], $attrs));

        $planKey = $attrs['subscription_plan'] ?? DesignerSubscription::PLAN_PRO;
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

    private function admin(): User
    {
        return User::factory()->create(['account_type' => 'system_admin']);
    }

    private function service(): SupportTicketService
    {
        return app(SupportTicketService::class);
    }

    private function ticketData(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Не работает кнопка',
            'category' => SupportCategory::SiteError->value,
            'message' => 'Описание проблемы',
        ], $overrides);
    }

    private function corporateTeam(User $owner, array $members = [], string $planKey = 'progress'): \App\Models\DesignerTeam
    {
        $teams = app(TeamService::class);
        $team = $teams->activateCorporateForOwner($owner, null, SubscriptionPlan::findByKey($planKey));

        foreach ($members as [$member, $role]) {
            $inv = $teams->addExistingUser($team->fresh(), $owner, $member, $role);
            $teams->acceptInvitation($member, $inv);
        }

        return $team->fresh();
    }

    // --- Creation & visibility ---

    public function test_any_corporate_member_can_create_ticket(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PROGRESS]);
        $member = $this->designer(['email' => 'm@example.com', 'subscription_plan' => null, 'subscription_ends_at' => null]);
        $team = $this->corporateTeam($owner, [[$member, TeamRole::Designer]]);

        $ticket = $this->service()->createTicket($member, $this->ticketData());

        $this->assertSame($member->id, $ticket->created_by);
        $this->assertSame($team->id, $ticket->team_id);
        $this->assertSame('progress', $ticket->plan_code_snapshot);
        $this->assertFalse($ticket->is_priority); // progress has no priority support
    }

    public function test_regular_member_sees_only_own_tickets(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PROGRESS]);
        $m1 = $this->designer(['email' => 'm1@example.com', 'subscription_plan' => null, 'subscription_ends_at' => null]);
        $m2 = $this->designer(['email' => 'm2@example.com', 'subscription_plan' => null, 'subscription_ends_at' => null]);
        $this->corporateTeam($owner, [[$m1, TeamRole::Designer], [$m2, TeamRole::Designer]]);

        $own = $this->service()->createTicket($m1, $this->ticketData());
        $foreign = $this->service()->createTicket($m2, $this->ticketData(['subject' => 'Чужой тикет']));

        $visible = $this->service()->visibleTicketsFor($m1)->pluck('id')->all();
        $this->assertContains($own->id, $visible);
        $this->assertNotContains($foreign->id, $visible);

        // Owner sees the whole team
        $ownerVisible = $this->service()->visibleTicketsFor($owner)->pluck('id')->all();
        $this->assertContains($own->id, $ownerVisible);
        $this->assertContains($foreign->id, $ownerVisible);
    }

    public function test_team_admin_sees_team_tickets(): void
    {
        $owner = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PROGRESS]);
        $teamAdmin = $this->designer(['email' => 'ta@example.com', 'subscription_plan' => null, 'subscription_ends_at' => null]);
        $member = $this->designer(['email' => 'mm@example.com', 'subscription_plan' => null, 'subscription_ends_at' => null]);
        $this->corporateTeam($owner, [[$teamAdmin, TeamRole::Admin], [$member, TeamRole::Designer]]);

        $ticket = $this->service()->createTicket($member, $this->ticketData());

        $this->assertTrue($teamAdmin->can('view', $ticket));
        $this->assertContains($ticket->id, $this->service()->visibleTicketsFor($teamAdmin)->pluck('id')->all());
    }

    public function test_user_cannot_open_foreign_team_ticket(): void
    {
        $ownerA = $this->designer(['email' => 'oa@example.com', 'subscription_plan' => DesignerSubscription::PLAN_PROGRESS]);
        $this->corporateTeam($ownerA);
        $ticket = $this->service()->createTicket($ownerA, $this->ticketData());

        $outsider = $this->designer(['email' => 'out@example.com']);

        $this->assertFalse($outsider->can('view', $ticket));
        $this->actingAs($outsider)
            ->get(route('support.show', $ticket))
            ->assertForbidden();
    }

    public function test_designer_can_open_own_ticket_page(): void
    {
        $user = $this->designer();
        $ticket = $this->service()->createTicket($user, $this->ticketData());

        $this->actingAs($user)
            ->get(route('support.show', $ticket))
            ->assertOk()
            ->assertSee('Не работает кнопка')
            ->assertSee('Описание проблемы');
    }

    public function test_admin_can_open_ticket_page(): void
    {
        $admin = $this->admin();
        $user = $this->designer();
        $ticket = $this->service()->createTicket($user, $this->ticketData());

        $this->actingAs($admin)
            ->get(route('admin.support.show', $ticket))
            ->assertOk()
            ->assertSee('Не работает кнопка');
    }

    // --- Priority ---

    public function test_pro_and_success_create_priority_tickets_others_do_not(): void
    {
        $pro = $this->designer(['email' => 'pro@example.com', 'subscription_plan' => DesignerSubscription::PLAN_PRO]);
        $base = $this->designer(['email' => 'base@example.com', 'subscription_plan' => DesignerSubscription::PLAN_BASE]);
        $successOwner = $this->designer(['email' => 'so@example.com', 'subscription_plan' => DesignerSubscription::PLAN_SUCCESS]);
        $this->corporateTeam($successOwner, [], 'success');

        $this->assertTrue($this->service()->createTicket($pro, $this->ticketData())->is_priority);
        $this->assertFalse($this->service()->createTicket($base, $this->ticketData())->is_priority);
        $this->assertTrue($this->service()->createTicket($successOwner, $this->ticketData())->is_priority);

        // Snapshot: priority must not change when subscription changes later
        $ticket = $this->service()->createTicket($pro, $this->ticketData(['subject' => 'Snapshot']));
        Subscription::query()->where('user_id', $pro->id)->update([
            'plan_id' => SubscriptionPlan::findByKey('base')->id,
        ]);
        $this->assertTrue($ticket->fresh()->is_priority);
        $this->assertSame('pro', $ticket->fresh()->plan_code_snapshot);
    }

    public function test_user_cannot_forge_priority_via_request(): void
    {
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_BASE]);

        $this->actingAs($user)->post(route('support.store'), $this->ticketData([
            'is_priority' => '1',
            'created_by' => $user->id + 1000,
        ]))->assertRedirect();

        $ticket = SupportTicket::query()->latest('id')->first();
        $this->assertFalse((bool) $ticket->is_priority);
        $this->assertSame($user->id, $ticket->created_by);
    }

    public function test_admin_list_sorts_priority_open_first(): void
    {
        $base1 = $this->designer(['email' => 'b1@example.com', 'subscription_plan' => DesignerSubscription::PLAN_BASE]);
        $pro = $this->designer(['email' => 'p1@example.com', 'subscription_plan' => DesignerSubscription::PLAN_PRO]);

        $oldNormal = $this->service()->createTicket($base1, $this->ticketData(['subject' => 'old normal']));
        SupportTicket::query()->whereKey($oldNormal->id)->update(['created_at' => now()->subDays(5)]);

        $priority = $this->service()->createTicket($pro, $this->ticketData(['subject' => 'priority']));
        SupportTicket::query()->whereKey($priority->id)->update(['created_at' => now()->subDay()]);

        $resolved = $this->service()->createTicket($base1, $this->ticketData(['subject' => 'resolved one']));
        $this->service()->changeStatus($resolved, $this->admin(), SupportTicketStatus::Resolved);
        SupportTicket::query()->whereKey($resolved->id)->update(['created_at' => now()->subDays(10)]);

        $ordered = $this->service()->adminQuery()->get();
        $this->assertSame($priority->id, $ordered[0]->id);       // open priority first
        $this->assertSame($oldNormal->id, $ordered[1]->id);      // then oldest open
        $this->assertSame($resolved->id, $ordered[2]->id);       // resolved last

        $priorityOnly = $this->service()->adminQuery(['priority' => 1])->get();
        $this->assertCount(1, $priorityOnly);
        $this->assertSame($priority->id, $priorityOnly[0]->id);
    }

    // --- Notifications ---

    public function test_admin_is_notified_about_new_ticket(): void
    {
        $admin = $this->admin();
        $user = $this->designer(['subscription_plan' => DesignerSubscription::PLAN_PRO]);

        $ticket = $this->service()->createTicket($user, $this->ticketData());

        $notification = UserNotification::query()
            ->where('user_id', $admin->id)
            ->where('action_key', 'support_ticket_created')
            ->latest('id')
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame($ticket->id, $notification->data['ticket_id'] ?? null);
    }

    public function test_author_is_notified_about_admin_reply_but_not_about_own_messages(): void
    {
        $admin = $this->admin();
        $user = $this->designer();
        $ticket = $this->service()->createTicket($user, $this->ticketData());

        $this->service()->reply($ticket, $admin, 'Ответ администратора');

        $this->assertTrue(
            UserNotification::query()
                ->where('user_id', $user->id)
                ->where('action_key', 'support_ticket_reply')
                ->exists()
        );

        // User's own reply must not notify the user
        $before = UserNotification::query()->where('user_id', $user->id)->count();
        $this->service()->reply($ticket, $user, 'Моё сообщение');
        $this->assertSame($before, UserNotification::query()->where('user_id', $user->id)->count());
    }

    public function test_status_change_creates_history_but_no_notification(): void
    {
        $admin = $this->admin();
        $user = $this->designer();
        $ticket = $this->service()->createTicket($user, $this->ticketData());

        $before = UserNotification::query()->count();
        $this->service()->changeStatus($ticket, $admin, SupportTicketStatus::InProgress);

        $this->assertSame($before, UserNotification::query()->count());
        $this->assertSame(SupportTicketStatus::InProgress, $ticket->fresh()->statusEnum());
        $this->assertTrue(
            $ticket->fresh()->messages()->where('is_system', true)->exists()
        );
    }

    // --- Attachments ---

    public function test_forbidden_file_type_is_rejected(): void
    {
        $user = $this->designer();

        $response = $this->actingAs($user)->post(route('support.store'), $this->ticketData([
            'attachments' => [UploadedFile::fake()->create('shell.php', 50, 'application/x-php')],
        ]));

        $response->assertSessionHasErrors('attachments.0');
        $this->assertSame(0, SupportTicket::query()->count());
    }

    public function test_file_over_20mb_is_rejected(): void
    {
        $user = $this->designer();

        $response = $this->actingAs($user)->post(route('support.store'), $this->ticketData([
            'attachments' => [UploadedFile::fake()->create('huge.pdf', 21000, 'application/pdf')],
        ]));

        $response->assertSessionHasErrors('attachments.0');
    }

    public function test_more_than_ten_files_is_rejected(): void
    {
        $user = $this->designer();

        $files = [];
        for ($i = 0; $i < 11; $i++) {
            $files[] = UploadedFile::fake()->create("doc{$i}.txt", 10, 'text/plain');
        }

        $response = $this->actingAs($user)->post(route('support.store'), $this->ticketData([
            'attachments' => $files,
        ]));

        $response->assertSessionHasErrors('attachments');
    }

    public function test_attachment_download_requires_ticket_access(): void
    {
        Storage::fake('local');

        $user = $this->designer(['email' => 'author@example.com']);
        $ticket = $this->service()->createTicket(
            $user,
            $this->ticketData(),
            [UploadedFile::fake()->image('screen.png')]
        );

        $attachment = SupportTicketAttachment::query()->firstOrFail();
        $this->assertSame('local', $attachment->disk);
        $this->assertNotSame('screen.png', basename($attachment->path)); // random name on disk

        $outsider = $this->designer(['email' => 'foreign@example.com']);
        $this->actingAs($outsider)
            ->get(route('support.attachments.download', $attachment))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('support.attachments.download', $attachment))
            ->assertOk();
    }

    // --- Admin actions ---

    public function test_admin_reply_moves_ticket_to_waiting_for_user(): void
    {
        $admin = $this->admin();
        $user = $this->designer();
        $ticket = $this->service()->createTicket($user, $this->ticketData());

        $this->actingAs($admin)
            ->post(route('admin.support.reply', $ticket), ['message' => 'Смотрим проблему'])
            ->assertRedirect();

        $this->assertSame(SupportTicketStatus::WaitingForUser, $ticket->fresh()->statusEnum());
    }

    public function test_designer_cannot_change_status(): void
    {
        $user = $this->designer();
        $ticket = $this->service()->createTicket($user, $this->ticketData());

        $this->assertFalse($user->can('updateStatus', $ticket));

        // Route is moderator-only: a designer is redirected away, status untouched
        $this->actingAs($user)
            ->patch(route('admin.support.status', $ticket), ['status' => 'closed'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(SupportTicketStatus::New, $ticket->fresh()->statusEnum());
    }

    public function test_ticket_number_format_and_first_message(): void
    {
        $user = $this->designer();
        $ticket = $this->service()->createTicket($user, $this->ticketData());

        $this->assertMatchesRegularExpression('/^SUP-\d{4}-\d{5}$/', $ticket->number);
        $this->assertSame(1, $ticket->messages()->count());
        $this->assertSame('user', $ticket->messages()->first()->sender_role);
        $this->assertNotNull($ticket->last_message_at);
    }
}
