<?php

declare(strict_types=1);

namespace App\DTOs\Event;

use App\Http\Requests\Event\EventRequest;
use Carbon\Carbon;

final class CreateEventDTO
{
    public function __construct(
        public readonly string  $title,
        public readonly ?string $description,
        public readonly ?string $location,
        public readonly Carbon  $start_time,
        public readonly Carbon  $end_time,
    ) {}

    public static function fromRequest(EventRequest $request): self
    {
        return new self(
            title:       $request->validated('title'),
            description: $request->validated('description'),
            location:    $request->validated('location'),
            start_time:  Carbon::parse($request->validated('start_time')),
            end_time:    Carbon::parse($request->validated('end_time')),
        );
    }

    public function toArray(): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'location'    => $this->location,
            'start_time'  => $this->start_time->toDateTimeString(),
            'end_time'    => $this->end_time->toDateTimeString(),
        ];
    }
}
