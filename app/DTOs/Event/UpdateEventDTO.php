<?php

declare(strict_types=1);

namespace App\DTOs\Event;

use App\Http\Requests\Event\UpdateEventRequest;
use Carbon\Carbon;

final class UpdateEventDTO
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $location,
        public readonly ?Carbon $start_time,
        public readonly ?Carbon $end_time,
    ) {}

    public static function fromRequest(UpdateEventRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title:       $validated['title'] ?? null,
            description: $validated['description'] ?? null,
            location:    $validated['location'] ?? null,
            start_time:  isset($validated['start_time']) ? Carbon::parse($validated['start_time']) : null,
            end_time:    isset($validated['end_time'])   ? Carbon::parse($validated['end_time'])   : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title'       => $this->title,
            'description' => $this->description,
            'location'    => $this->location,
            'start_time'  => $this->start_time?->toDateTimeString(),
            'end_time'    => $this->end_time?->toDateTimeString(),
        ], fn(mixed $value): bool => $value !== null);
    }
}
