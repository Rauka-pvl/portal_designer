<?php

namespace App\Http\Requests\Support;

use App\Enums\SupportCategory;
use App\Models\SupportTicketAttachment;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\SupportTicket::class) ?? false;
    }

    public function rules(): array
    {
        $mimes = implode(',', SupportTicketAttachment::ALLOWED_EXTENSIONS);

        return [
            'subject' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'in:'.implode(',', SupportCategory::values())],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:'.SupportTicketAttachment::MAX_FILES_PER_MESSAGE],
            'attachments.*' => ['file', 'max:'.SupportTicketAttachment::MAX_FILE_KB, 'mimes:'.$mimes],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Never trust client-supplied ownership/priority fields.
        foreach (['created_by', 'team_id', 'is_priority', 'plan_id', 'subscription_id', 'status', 'number'] as $key) {
            $this->request->remove($key);
        }
    }
}
