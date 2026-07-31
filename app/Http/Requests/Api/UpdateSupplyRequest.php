<?php

namespace App\Http\Requests\Api;

class UpdateSupplyRequest extends StoreSupplyRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['supplier_id'] = ['sometimes', 'integer', 'exists:suppliers,id'];

        return $rules;
    }
}
