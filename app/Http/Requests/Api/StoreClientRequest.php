<?php

namespace App\Http\Requests\Api;

use App\Services\Crm\ClientService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => $this->input('full_name', $this->input('name')),
            'client_type' => $this->input('client_type', $this->input('type', 'person')),
        ]);
    }

    public function rules(ClientService $clientService): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'client_type' => ['required', Rule::in(['person', 'company'])],
            'type' => ['nullable', Rule::in(['person', 'company'])],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'status' => ['required', 'string', 'max:64', Rule::in($clientService->allowedStatusKeys((int) $this->user()->id))],
            'comment' => ['nullable', 'string'],
            'link' => ['nullable', 'url', 'max:255'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file'],
        ];
    }
}
