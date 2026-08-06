<?php

namespace App\Http\Controllers\Designer;

use App\Enums\SupportCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Requests\Support\SupportReplyRequest;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupportController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly SupportTicketService $tickets,
    ) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $query = $this->tickets->visibleTicketsFor($request->user());
        if ($status !== '') {
            $query->where('status', $status);
        }

        return view('designer.support.index', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'statusFilter' => $status,
        ]);
    }

    public function create(): View
    {
        return view('designer.support.create', [
            'categories' => SupportCategory::options(),
        ]);
    }

    public function store(StoreSupportTicketRequest $request)
    {
        $ticket = $this->tickets->createTicket(
            $request->user(),
            $request->safe()->only(['subject', 'category', 'message']),
            $request->file('attachments', []),
        );

        return redirect()
            ->route('support.show', $ticket)
            ->with('success', __('support.created', ['number' => $ticket->number]));
    }

    public function show(SupportTicket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['author:id,name', 'team:id,name', 'plan:id,key,name', 'messages.sender:id,name,account_type', 'messages.attachments']);

        return view('designer.support.show', [
            'ticket' => $ticket,
            'canReply' => request()->user()->can('reply', $ticket),
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
            ->route('support.show', $ticket)
            ->with('success', __('support.reply_sent'));
    }

    public function download(\App\Models\SupportTicketAttachment $attachment, Request $request)
    {
        $this->authorize('view', $attachment->ticket);

        abort_unless($attachment->existsOnDisk(), 404);

        $inline = $request->boolean('preview') && $attachment->isImage();

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'
                    .str_replace('"', '', $attachment->original_name).'"',
            ],
        );
    }
}
