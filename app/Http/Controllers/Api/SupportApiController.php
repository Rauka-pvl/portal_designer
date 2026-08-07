<?php

namespace App\Http\Controllers\Api;

use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Api\Concerns\EnsuresDesigner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Requests\Support\SupportReplyRequest;
use App\Http\Resources\Api\SupportTicketCollection;
use App\Http\Resources\Api\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Services\Support\SupportTicketService;
use App\Support\Api\ApiQuery;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportApiController extends Controller
{
    use AuthorizesRequests;
    use EnsuresDesigner;

    public function __construct(
        private readonly SupportTicketService $tickets,
    ) {}

    /** GET /api/support/meta */
    public function meta(Request $request): JsonResponse
    {
        $this->ensureDesigner($request);

        return response()->json([
            'data' => [
                'categories' => SupportCategory::options(),
                'statuses' => array_map(
                    fn (SupportTicketStatus $status) => [
                        'value' => $status->value,
                        'label' => $status->label(),
                        'is_open' => $status->isOpen(),
                    ],
                    SupportTicketStatus::cases(),
                ),
                'attachments' => [
                    'max_files' => SupportTicketAttachment::MAX_FILES_PER_MESSAGE,
                    'max_file_kb' => SupportTicketAttachment::MAX_FILE_KB,
                    'allowed_extensions' => SupportTicketAttachment::ALLOWED_EXTENSIONS,
                ],
            ],
        ]);
    }

    /** GET /api/support */
    public function index(Request $request): SupportTicketCollection
    {
        $this->ensureDesigner($request);
        $this->authorize('viewAny', SupportTicket::class);

        $query = $this->tickets->visibleTicketsFor($request->user())
            ->withCount('messages');

        $status = (string) $request->query('status', '');
        if ($status !== '' && in_array($status, SupportTicketStatus::values(), true)) {
            $query->where('status', $status);
        }

        $category = (string) $request->query('category', '');
        if ($category !== '' && in_array($category, SupportCategory::values(), true)) {
            $query->where('category', $category);
        }

        if ($request->boolean('priority')) {
            $query->where('is_priority', true);
        }

        ApiQuery::applySearch($query, $request->query('search'), [
            'number', 'subject',
        ]);

        return new SupportTicketCollection(ApiQuery::applyPagination($query, $request));
    }

    /** POST /api/support */
    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $this->ensureDesigner($request);

        $ticket = $this->tickets->createTicket(
            $request->user(),
            $request->safe()->only(['subject', 'category', 'message']),
            $this->uploadedFiles($request, 'attachments'),
        );

        $ticket->load([
            'author:id,name,email',
            'team:id,name',
            'plan:id,key,name',
            'messages.sender:id,name',
            'messages.attachments',
        ])->loadCount('messages');

        return (new SupportTicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/support/{ticket} */
    public function show(Request $request, SupportTicket $ticket): SupportTicketResource
    {
        $this->ensureDesigner($request);
        $this->authorize('view', $ticket);

        $ticket->load([
            'author:id,name,email',
            'team:id,name',
            'plan:id,key,name',
            'messages.sender:id,name',
            'messages.attachments',
        ])->loadCount('messages');

        return new SupportTicketResource($ticket);
    }

    /** POST /api/support/{ticket}/reply */
    public function reply(SupportReplyRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->ensureDesigner($request);

        $this->tickets->reply(
            $ticket,
            $request->user(),
            (string) $request->validated('message', ''),
            $this->uploadedFiles($request, 'attachments'),
        );

        $ticket->refresh()->load([
            'author:id,name,email',
            'team:id,name',
            'plan:id,key,name',
            'messages.sender:id,name',
            'messages.attachments',
        ])->loadCount('messages');

        return (new SupportTicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/support/attachments/{attachment}/download */
    public function download(Request $request, SupportTicketAttachment $attachment): StreamedResponse
    {
        $this->ensureDesigner($request);

        $attachment->loadMissing('ticket');
        $this->authorize('view', $attachment->ticket);

        abort_unless($attachment->existsOnDisk(), 404);

        $inline = $request->boolean('preview') && $attachment->isImage();
        $disposition = ($inline ? 'inline' : 'attachment').'; filename="'
            .str_replace('"', '', (string) $attachment->original_name).'"';

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => $disposition,
            ],
        );
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedFiles(Request $request, string $key): array
    {
        $files = $request->file($key, []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(
            $files,
            fn ($file) => $file instanceof UploadedFile && $file->isValid(),
        ));
    }
}
