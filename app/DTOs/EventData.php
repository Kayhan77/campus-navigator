<?php

namespace App\DTOs;

class EventData
{
    public string $title;
    public ?string $description;
    public ?string $location;
    public string $start_time;
    public string $end_time;

    public function __construct(array $data)
    {
        $this->title = $data['title'];
        $this->description = $data['description'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->start_time = $data['start_time'];
        $this->end_time = $data['end_time'];
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }
}
