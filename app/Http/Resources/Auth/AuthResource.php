<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'status' => true,
            'token' => $this->token,
            'user' => new UserResource($this->user),
        ];
    }
}
