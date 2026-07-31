<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:1000'],
            'deadline' => ['nullable', 'date'],
            'responsible_id' => ['nullable', 'integer'],
            'link' => ['nullable', 'url', 'max:1000'],
            'order' => ['nullable', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'result_status' => ['nullable', 'in:pending,done'],
            'result_comment' => ['nullable', 'string'],
        ];
    }
}
