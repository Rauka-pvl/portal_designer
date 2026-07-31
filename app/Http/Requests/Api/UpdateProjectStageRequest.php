<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectStageRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['stage_id' => ['nullable', 'integer', 'exists:pipeline_stages,id'], 'status' => ['nullable', 'string', 'max:64']]; }
}
