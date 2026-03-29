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
        public readonly ?int    $room_id               = null,
        public readonly ?string $location_override      = null,
        public readonly string  $status                = 'draft',
        public readonly bool    $is_public             = true,
        public readonly ?int    $max_attendees         = null,
        public readonly bool    $registration_required = false,
        public readonly ?Carbon $reminder_sent_at      = null,
    ) {}

    public static function fromRequest(EventRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title:                 $validated['title'],
            description:           $validated['description'] ?? null,
            location:              $validated['location'] ?? null,
            start_time:            Carbon::parse($validated['start_time']),
            end_time:              Carbon::parse($validated['end_time']),
            room_id:               isset($validated['room_id']) ? (int) $validated['room_id'] : null,
            location_override:     $validated['location_override'] ?? null,
            status:                $validated['status'] ?? 'draft',
            is_public:             (bool) ($validated['is_public'] ?? true),
            max_attendees:         isset($validated['max_attendees']) ? (int) $validated['max_attendees'] : null,
            registration_required: (bool) ($validated['registration_required'] ?? false),
            reminder_sent_at:      isset($validated['reminder_sent_at']) ? Carbon::parse($validated['reminder_sent_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'room_id'               => $this->room_id,
            'title'                 => $this->title,
            'description'           => $this->description,
            'location'              => $this->location,
            'location_override'     => $this->location_override,
            'start_time'            => $this->start_time->toDateTimeString(),
            'end_time'              => $this->end_time->toDateTimeString(),
            'status'                => $this->status,
            'is_public'             => $this->is_public,
            'max_attendees'         => $this->max_attendees,
            'registration_required' => $this->registration_required,
            'reminder_sent_at'      => $this->reminder_sent_at?->toDateTimeString(),
        ];
    }
}
