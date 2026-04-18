<?php

declare(strict_types=1);

namespace App\DTOs\LostItem;

use App\Http\Requests\LostItem\LostItemRequest;
use Illuminate\Http\UploadedFile;

final class CreateLostItemDTO
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $location = null,
        public string $status = 'lost',
        public ?UploadedFile $image = null
    ) {}

    public static function fromRequest(LostItemRequest $request): self
    {
        return new self(
            $request->title,
            $request->description,
            $request->location,
            $request->status ?? 'lost',
            $request->file('image')
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'image' => null,
            'status' => $this->status,
        ];
    }
}
