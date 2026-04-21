<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        // Get the recipient record for the current user (loaded in controller)
        $recipient = $this->recipients?->first();

        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'message'       => $this->message,
            'type'          => $this->type,
            'data'          => $this->data,
            'is_read'       => $recipient?->is_read ?? false,
            'read_at'       => $recipient?->read_at?->toDateTimeString(),
            'delivered_at'  => $recipient?->delivered_at?->toDateTimeString(),
            'created_at'    => $this->created_at?->toDateTimeString(),
            'updated_at'    => $this->updated_at?->toDateTimeString(),
        ];
    }
}
