<?php

namespace App\DTOs\Event;

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
}
