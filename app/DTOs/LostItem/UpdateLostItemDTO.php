<?php

namespace App\DTOs\LostItem;

class UpdateLostItemDTO
{
    public ?string $title;
    public ?string $description;
    public ?string $location;
    public ?string $status;

    public function __construct(array $data)
    {
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->status = $data['status'] ?? null;
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'status' => $this->status,
        ], fn($value) => $value !== null);
    }
}
