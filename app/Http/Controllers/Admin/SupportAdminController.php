<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\SupportReplyRequest;
use App\Http\Requests\Support\UpdateSupportTicketStatusRequest;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportAdminController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly SupportTicketService $tickets,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'category', 'plan', 'priority', 'search']);

        $planOptions = SupportTicket::query()
            ->whereNotNull('plan_code_snapshot')
            ->distinct()
            ->orderBy('plan_code_snapshot')
            ->pluck('plan_code_snapshot');

        return view('admin.support.index', [
            'tickets' => $this->tickets->adminQuery($filters)->paginate(20)->withQueryString(),
            'filters' => $filters,
            'statusOptions' => SupportTicketStatus::cases(),
            'categoryOptions' => SupportCategory::options(),
            'planOptions' => $planOptions,
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'author:id,name,email',
            'team:id,name,owner_id',
            'team.owner:id,name,email',
            'plan:id,key,name',
            'subscription',
            'messages.sender:id,name,account_type',
            'messages.attachments',
        ]);

        return view('admin.support.show', [
            'ticket' => $ticket,
            'statusOptions' => SupportTicketStatus::cases(),
        ]);
    }

    public function reply(SupportReplyRequest $request, SupportTicket $ticket)
    {
        $this->tickets->reply(
            $ticket,
            $request->user(),
            (string) $request->validated('message', ''),
            $request->file('attachments', []),
        );

        return redirect()
            ->route('admin.support.show', $ticket)
            ->with('success', __('support.reply_sent'));
    }

    public function updateStatus(UpdateSupportTicketStatusRequest $request, SupportTicket $ticket)
    {
        $this->tickets->changeStatus(
            $ticket,
            $request->user(),
            SupportTicketStatus::from((string) $request->validated('status')),
        );

        return redirect()
            ->route('admin.support.show', $ticket)
            ->with('success', __('support.status_updated'));
    }
}
