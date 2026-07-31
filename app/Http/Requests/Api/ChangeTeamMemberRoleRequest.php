<?php

namespace App\Http\Requests\Api;

use App\Enums\TeamRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeTeamMemberRoleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return ['role' => ['required', Rule::in([TeamRole::Admin->value, TeamRole::Designer->value])]];
    }
}
