<?php

namespace App\Models;

use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $table = 'support_tickets';

    protected $fillable = [
        'number',
        'created_by',
        'team_id',
        'subscription_id',
        'plan_id',
        'plan_code_snapshot',
        'is_priority',
        'subject',
        'category',
        'status',
        'last_message_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'is_priority' => 'boolean',
        'category' => SupportCategory::class,
        'status' => SupportTicketStatus::class,
        'last_message_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(DesignerTeam::class, 'team_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'ticket_id');
    }

    public function statusEnum(): SupportTicketStatus
    {
        return $this->status instanceof SupportTicketStatus
            ? $this->status
            : SupportTicketStatus::tryFrom((string) $this->status) ?? SupportTicketStatus::New;
    }

    public function isOpen(): bool
    {
        return $this->statusEnum()->isOpen();
    }

    public static function nextNumber(): string
    {
        $year = now()->format('Y');
        $max = static::query()
            ->where('number', 'like', "SUP-{$year}-%")
            ->max('number');

        $seq = 0;
        if (is_string($max) && preg_match('/-(\d+)$/', $max, $m)) {
            $seq = (int) $m[1];
        }

        return sprintf('SUP-%s-%05d', $year, $seq + 1);
    }
}
