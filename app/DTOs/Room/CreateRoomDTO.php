<?php

namespace App\DTOs\Room;

use App\Http\Requests\Room\RoomRequest;

class CreateRoomDTO
{
    public function __construct(
        public int $building_id,
        public string $room_number,
        public ?int $floor = null
    ) {}

    public static function fromRequest(RoomRequest $request): self
    {
        return new self(
            $request->building_id,
            $request->room_number,
            $request->floor
        );
    }

    public function toArray(): array
    {
        return [
            'building_id' => $this->building_id,
            'room_number' => $this->room_number,
            'floor' => $this->floor,
        ];
    }
}
