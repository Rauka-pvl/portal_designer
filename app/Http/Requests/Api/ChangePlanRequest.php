<?php

namespace App\Http\Requests\Api;

use App\Support\DesignerSubscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePlanRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::in(array_keys(DesignerSubscription::plans()))],
            'confirm_team_downgrade' => ['nullable', 'boolean'],
        ];
    }
}
