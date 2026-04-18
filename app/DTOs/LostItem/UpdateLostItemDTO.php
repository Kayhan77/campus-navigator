<?php

declare(strict_types=1);

namespace App\DTOs\LostItem;

use App\Http\Requests\LostItem\UpdateLostItemRequest;
use Illuminate\Http\UploadedFile;

final class UpdateLostItemDTO
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $location,
        public readonly ?string $status,
        public readonly ?UploadedFile $image,
    ) {}

    public static function fromRequest(UpdateLostItemRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title:       $validated['title'] ?? null,
            description: $validated['description'] ?? null,
            location:    $validated['location'] ?? null,
            status:      $validated['status'] ?? null,
            image:       $request->file('image'),
        );
    }

    public function toArray(): array
    {
        $data = [
            'title'       => $this->title,
            'description' => $this->description,
            'location'    => $this->location,
            'status'      => $this->status,
        ];

        if ($this->image !== null) {
            $data['image'] = null;
        }

        return array_filter(
            $data,
            fn(mixed $value): bool => $value !== null || $this->image !== null
        );
    }
}
