<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'bonus_percent' => $this->input('bonus_percent', $this->input('percentage')),
            'date_planned' => $this->input('date_planned', $this->input('planned_delivery_date')),
            'included_step_ids' => $this->input('included_step_ids', $this->input('checklist_item_result_ids')),
            'send_to_supplier' => $this->boolean('send_to_supplier')
                || $this->input('submit_action') === 'send',
        ]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'summa' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'category' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:255'],
            'date_planned' => ['nullable', 'date'],
            'planned_delivery_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,order_created,order_confirmed,advance_payment,full_payment,delivery_completed'],
            'bonus_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'comment' => ['nullable', 'string'],
            'included_step_ids' => ['nullable', 'array'],
            'included_step_ids.*' => ['integer'],
            'checklist_item_result_ids' => ['nullable', 'array'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:10240'],
            'send_to_supplier' => ['nullable', 'boolean'],
            'submit_action' => ['nullable', 'in:draft,send'],
        ];
    }
}
