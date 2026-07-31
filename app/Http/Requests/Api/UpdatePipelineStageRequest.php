<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePipelineStageRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['name' => ['sometimes', 'string', 'max:120'], 'color' => ['sometimes', 'nullable', 'string', 'max:32']]; }
}
