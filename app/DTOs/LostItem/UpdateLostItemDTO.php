<?php

declare(strict_types=1);

namespace App\DTOs\LostItem;

use App\Http\Requests\LostItem\UpdateLostItemRequest;

final class UpdateLostItemDTO
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $location,
        public readonly ?string $status,
    ) {}

    public static function fromRequest(UpdateLostItemRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title:       $validated['title'] ?? null,
            description: $validated['description'] ?? null,
            location:    $validated['location'] ?? null,
            status:      $validated['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title'       => $this->title,
            'description' => $this->description,
            'location'    => $this->location,
            'status'      => $this->status,
        ], fn(mixed $value): bool => $value !== null);
    }
}
