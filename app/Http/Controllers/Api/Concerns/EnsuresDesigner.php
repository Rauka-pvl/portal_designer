<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;

trait EnsuresDesigner
{
    protected function ensureDesigner(Request $request): void
    {
        if (! in_array((string) ($request->user()?->role ?? ''), ['designer', 'moderator'], true)) {
            abort(403, 'Only designer portal');
        }
    }
}
