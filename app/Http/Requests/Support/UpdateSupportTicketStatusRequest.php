<?php

namespace App\Http\Requests\Support;

use App\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof \App\Models\SupportTicket
            && ($this->user()?->can('updateStatus', $ticket) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', SupportTicketStatus::values())],
        ];
    }
}
