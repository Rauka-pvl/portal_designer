<?php

namespace App\Http\Requests\Support;

use App\Models\SupportTicketAttachment;
use Illuminate\Foundation\Http\FormRequest;

class SupportReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof \App\Models\SupportTicket
            && ($this->user()?->can('reply', $ticket) ?? false);
    }

    public function rules(): array
    {
        $mimes = implode(',', SupportTicketAttachment::ALLOWED_EXTENSIONS);

        return [
            'message' => ['required_without:attachments', 'nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:'.SupportTicketAttachment::MAX_FILES_PER_MESSAGE],
            'attachments.*' => ['file', 'max:'.SupportTicketAttachment::MAX_FILE_KB, 'mimes:'.$mimes],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['sender_id', 'sender_role', 'is_system', 'status', 'is_priority'] as $key) {
            $this->request->remove($key);
        }
    }
}
