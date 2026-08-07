<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SupportTicketAttachment */
class SupportTicketAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message_id' => $this->message_id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => (int) $this->size,
            'size_label' => $this->sizeLabel(),
            'is_image' => $this->isImage(),
            'download_url' => url('/api/support/attachments/'.$this->id.'/download'),
            'preview_url' => $this->isImage()
                ? url('/api/support/attachments/'.$this->id.'/download?preview=1')
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
