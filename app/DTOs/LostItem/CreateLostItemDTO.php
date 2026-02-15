<?php

namespace App\DTOs\LostItem;

class CreateLostItemDTO
{
    public string $title;
    public ?string $description;
    public ?string $location;
    public string $status;

    public function __construct(array $data)
    {
        $this->title = $data['title'];
        $this->description = $data['description'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->status = $data['status'] ?? 'lost';
    }
}
