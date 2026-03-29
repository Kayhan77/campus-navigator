<?php

declare(strict_types=1);

namespace App\DTOs\Event;

use App\Http\Requests\Event\UpdateEventRequest;
use Carbon\Carbon;

final class UpdateEventDTO
{
    public function __construct(
        public readonly ?int    $room_id               = null,
        public readonly ?string $title                 = null,
        public readonly ?string $description           = null,
        public readonly ?string $location              = null,
        public readonly ?string $location_override     = null,
        public readonly ?Carbon $start_time            = null,
        public readonly ?Carbon $end_time              = null,
        public readonly ?string $status                = null,
        public readonly ?bool   $is_public             = null,
        public readonly ?int    $max_attendees         = null,
        public readonly ?bool   $registration_required = null,
        public readonly ?Carbon $reminder_sent_at      = null,
    ) {}

    public static function fromRequest(UpdateEventRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            room_id:               isset($validated['room_id']) ? (int) $validated['room_id'] : null,
            title:                 $validated['title'] ?? null,
            description:           $validated['description'] ?? null,
            location:              $validated['location'] ?? null,
            location_override:     $validated['location_override'] ?? null,
            start_time:            isset($validated['start_time']) ? Carbon::parse($validated['start_time']) : null,
            end_time:              isset($validated['end_time'])   ? Carbon::parse($validated['end_time'])   : null,
            status:                $validated['status'] ?? null,
            is_public:             isset($validated['is_public']) ? (bool) $validated['is_public'] : null,
            max_attendees:         isset($validated['max_attendees']) ? (int) $validated['max_attendees'] : null,
            registration_required: isset($validated['registration_required']) ? (bool) $validated['registration_required'] : null,
            reminder_sent_at:      isset($validated['reminder_sent_at']) ? Carbon::parse($validated['reminder_sent_at']) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'room_id'               => $this->room_id,
            'title'                 => $this->title,
            'description'           => $this->description,
            'location'              => $this->location,
            'location_override'     => $this->location_override,
            'start_time'            => $this->start_time?->toDateTimeString(),
            'end_time'              => $this->end_time?->toDateTimeString(),
            'status'                => $this->status,
            'is_public'             => $this->is_public,
            'max_attendees'         => $this->max_attendees,
            'registration_required' => $this->registration_required,
            'reminder_sent_at'      => $this->reminder_sent_at?->toDateTimeString(),
        ], fn(mixed $value): bool => $value !== null);
    }
}
