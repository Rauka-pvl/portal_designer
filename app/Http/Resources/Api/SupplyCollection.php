<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplyCollection extends ResourceCollection
{
    public $collects = SupplyResource::class;
}
