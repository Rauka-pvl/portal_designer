<?php

namespace App\Http\Requests\Api;

use App\Enums\DesignerTaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['status' => ['required', 'string', Rule::in(DesignerTaskStatus::values())]]; }
}
