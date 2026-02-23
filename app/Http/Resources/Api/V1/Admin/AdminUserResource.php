<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'role'              => $this->role,
            'is_verified'       => $this->is_verified,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'created_at'        => $this->created_at?->toDateTimeString(),
        ];
    }
}
