<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return ['reason' => ['nullable', Rule::in(['expensive', 'not_using', 'missing_features', 'tech_issues', 'other'])]];
    }
}
