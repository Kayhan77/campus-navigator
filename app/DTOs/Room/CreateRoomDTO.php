<?php

declare(strict_types=1);

namespace App\DTOs\Room;

use App\Http\Requests\Room\RoomRequest;

final class CreateRoomDTO
{
    public function __construct(
        public int     $building_id,
        public string  $room_number,
        public ?int    $floor    = null,
        public int     $capacity = 0,
        public string  $type     = 'classroom',
    ) {}

    public static function fromRequest(RoomRequest $request): self
    {
        return new self(
            building_id: (int) $request->building_id,
            room_number: $request->room_number,
            floor:       $request->floor ? (int) $request->floor : null,
            capacity:    (int) $request->capacity,
            type:        $request->type,
        );
    }

    public function toArray(): array
    {
        return [
            'building_id' => $this->building_id,
            'room_number' => $this->room_number,
            'floor'       => $this->floor,
            'capacity'    => $this->capacity,
            'type'        => $this->type,
        ];
    }
}
