<?php

namespace App\DTOs\LostItem;

use App\Http\Requests\LostItem\LostItemRequest;

class CreateLostItemDTO
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $location = null,
        public string $status = 'lost'
    ) {}

    public static function fromRequest(LostItemRequest $request): self
    {
        return new self(
            $request->title,
            $request->description,
            $request->location,
            $request->status ?? 'lost'
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'status' => $this->status,
        ];
    }
}
