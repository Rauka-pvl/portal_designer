<?php

namespace App\Services\Support;

use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Models\UserNotification;
use App\Policies\SupportTicketPolicy;
use App\Services\Billing\PlanLimitService;
use App\Services\Team\TeamService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupportTicketService
{
    private const DISK = 'local';

    public function __construct(
        private readonly TeamService $teams,
        private readonly PlanLimitService $limits,
    ) {}

    /**
     * Create ticket + first message + attachments atomically.
     * Admin notification fires only after a successful commit.
     *
     * @param  array{subject: string, category: string, message: string}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function createTicket(User $author, array $data, array $files = []): SupportTicket
    {
        $ticket = DB::transaction(function () use ($author, $data, $files) {
            $team = $this->teams->activeTeamFor($author);
            $hasCorporate = $team && $this->teams->teamHasCorporateAccess($team);
            $billingOwner = $this->limits->billingOwner($author);
            $subscription = $billingOwner->subscription;
            $plan = $this->limits->currentPlanFor($author);

            $ticket = SupportTicket::query()->create([
                'number' => $this->generateNumber(),
                'created_by' => $author->id,
                'team_id' => $hasCorporate ? $team->id : null,
                'subscription_id' => $subscription?->id,
                'plan_id' => $plan?->id,
                'plan_code_snapshot' => $plan?->key,
                'is_priority' => $this->limits->hasPrioritySupport($author),
                'subject' => $data['subject'],
                'category' => $data['category'],
                'status' => SupportTicketStatus::New->value,
                'last_message_at' => now(),
            ]);

            $message = $ticket->messages()->create([
                'sender_id' => $author->id,
                'sender_role' => 'user',
                'message' => $data['message'],
                'is_system' => false,
            ]);

            $this->storeAttachments($ticket, $message, $author, $files);

            return $ticket;
        });

        DB::afterCommit(fn () => $this->notifyAdminsAboutNewTicket($ticket));

        return $ticket;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function reply(SupportTicket $ticket, User $sender, string $body, array $files = []): SupportTicketMessage
    {
        $asAdmin = SupportTicketPolicy::isStaff($sender);

        $message = DB::transaction(function () use ($ticket, $sender, $body, $files, $asAdmin) {
            $message = $ticket->messages()->create([
                'sender_id' => $sender->id,
                'sender_role' => $asAdmin ? 'admin' : 'user',
                'message' => $body,
                'is_system' => false,
            ]);

            $this->storeAttachments($ticket->fresh(), $message, $sender, $files);

            $updates = ['last_message_at' => now()];
            if ($asAdmin) {
                if ($ticket->statusEnum()->isOpen()) {
                    $updates['status'] = SupportTicketStatus::WaitingForUser->value;
                }
            } elseif ($ticket->statusEnum() === SupportTicketStatus::WaitingForUser) {
                $updates['status'] = SupportTicketStatus::InProgress->value;
            }
            $ticket->update($updates);

            return $message;
        });

        if ($asAdmin && (int) $ticket->created_by !== (int) $sender->id) {
            DB::afterCommit(fn () => $this->notifyAuthorAboutReply($ticket->fresh(), $sender));
        }

        return $message;
    }

    /** Status changes go to the ticket history as system events — no notifications. */
    public function changeStatus(SupportTicket $ticket, User $admin, SupportTicketStatus $status): void
    {
        DB::transaction(function () use ($ticket, $admin, $status) {
            $previous = $ticket->statusEnum();

            $updates = ['status' => $status->value];
            if ($status === SupportTicketStatus::Resolved) {
                $updates['resolved_at'] = now();
                $updates['closed_at'] = null;
            } elseif ($status === SupportTicketStatus::Closed) {
                $updates['closed_at'] = now();
            } else {
                $updates['resolved_at'] = null;
                $updates['closed_at'] = null;
            }
            $ticket->update($updates);

            $ticket->messages()->create([
                'sender_id' => $admin->id,
                'sender_role' => 'system',
                'message' => __('support.history_status_changed', [
                    'from' => $previous->label(),
                    'to' => $status->label(),
                    'name' => $admin->name,
                ]),
                'is_system' => true,
            ]);
        });
    }

    /** @param  array<int, UploadedFile>  $files */
    private function storeAttachments(SupportTicket $ticket, SupportTicketMessage $message, User $uploader, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $extension = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($extension, SupportTicketAttachment::ALLOWED_EXTENSIONS, true)) {
                throw ValidationException::withMessages([
                    'attachments' => [__('support.attachment_type_denied')],
                ]);
            }

            $path = $file->storeAs(
                "support/{$ticket->id}",
                Str::random(40).'.'.$extension,
                self::DISK,
            );

            if ($path === false) {
                throw ValidationException::withMessages([
                    'attachments' => [__('support.attachment_upload_failed')],
                ]);
            }

            SupportTicketAttachment::query()->create([
                'ticket_id' => $ticket->id,
                'message_id' => $message->id,
                'uploaded_by' => $uploader->id,
                'disk' => self::DISK,
                'path' => $path,
                'original_name' => mb_substr((string) $file->getClientOriginalName(), 0, 255),
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'size' => (int) $file->getSize(),
            ]);
        }
    }

    private function generateNumber(): string
    {
        // Retry on the unique index in case of concurrent inserts.
        for ($i = 0; $i < 5; $i++) {
            $number = SupportTicket::nextNumber();
            if (! SupportTicket::query()->where('number', $number)->exists()) {
                return $number;
            }
        }

        return 'SUP-'.now()->format('Y').'-'.strtoupper(Str::random(6));
    }

    private function notifyAdminsAboutNewTicket(SupportTicket $ticket): void
    {
        $ticket->loadMissing(['author', 'plan']);
        $planLabel = $ticket->plan_code_snapshot ?? '—';

        $admins = User::query()
            ->whereIn('account_type', ['moderator', 'admin', 'system_admin'])
            ->get(['id', 'name']);

        foreach ($admins as $admin) {
            UserNotification::query()->create([
                'user_id' => $admin->id,
                'title' => __('support.notify_new_title', ['number' => $ticket->number]),
                'comment' => __('support.notify_new_body', [
                    'subject' => $ticket->subject,
                    'author' => $ticket->author?->name ?? '—',
                    'plan' => $planLabel,
                    'priority' => $ticket->is_priority ? __('support.priority_badge') : '—',
                ]),
                'action_key' => 'support_ticket_created',
                'data' => ['ticket_id' => $ticket->id, 'ticket_number' => $ticket->number],
                'is_read' => false,
            ]);
        }
    }

    private function notifyAuthorAboutReply(SupportTicket $ticket, User $admin): void
    {
        UserNotification::query()->create([
            'user_id' => $ticket->created_by,
            'title' => __('support.notify_reply_title', ['number' => $ticket->number]),
            'comment' => __('support.notify_reply_body', [
                'subject' => $ticket->subject,
                'name' => $admin->name,
            ]),
            'action_key' => 'support_ticket_reply',
            'data' => ['ticket_id' => $ticket->id, 'ticket_number' => $ticket->number],
            'is_read' => false,
        ]);
    }

    /** Tickets visible to a designer: own + (owner/admin) whole team. */
    public function visibleTicketsFor(User $user)
    {
        $query = SupportTicket::query()->with(['author:id,name,email'])->latest('last_message_at');

        $team = $this->teams->activeTeamFor($user);
        $isManager = $team
            && $this->teams->teamHasCorporateAccess($team)
            && in_array($team->roleFor($user), [\App\Enums\TeamRole::Owner, \App\Enums\TeamRole::Admin], true);

        if ($isManager) {
            $query->where(function ($q) use ($user, $team) {
                $q->where('created_by', $user->id)->orWhere('team_id', $team->id);
            });
        } else {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    /**
     * Admin list: priority open first, then other open, then resolved/closed;
     * oldest unhandled first inside each group.
     */
    public function adminQuery(array $filters = [])
    {
        $query = SupportTicket::query()
            ->with(['author:id,name,email', 'team:id,name', 'plan:id,key,name']);

        if (($filters['status'] ?? '') !== '') {
            $query->where('status', (string) $filters['status']);
        }
        if (($filters['category'] ?? '') !== '') {
            $query->where('category', (string) $filters['category']);
        }
        if (($filters['plan'] ?? '') !== '') {
            $query->where('plan_code_snapshot', (string) $filters['plan']);
        }
        if (! empty($filters['priority'])) {
            $query->where('is_priority', true);
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('author', function ($a) use ($search) {
                        $a->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->orderByRaw("CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END ASC")
            ->orderByDesc('is_priority')
            ->orderBy('created_at')
            ->orderBy('id');
    }
}
