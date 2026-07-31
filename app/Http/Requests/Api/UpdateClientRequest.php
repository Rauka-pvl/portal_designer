<?php

namespace App\Http\Requests\Api;

class UpdateClientRequest extends StoreClientRequest
{
    public function rules(\App\Services\Crm\ClientService $clientService): array
    {
        return parent::rules($clientService) + [
            'existing_files' => ['nullable', 'array'],
            'existing_files.*' => ['string'],
        ];
    }
}
