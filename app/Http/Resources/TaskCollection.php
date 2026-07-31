<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaskCollection extends ResourceCollection
{
    public $collects = TaskResource::class;

    public function with(Request $request): array
    {
        return ['meta' => ['count' => $this->collection->count()]];
    }
}
