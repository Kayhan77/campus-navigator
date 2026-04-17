<?php

declare(strict_types=1);

namespace App\DTOs\Announcement;

use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use Carbon\Carbon;

final class UpdateAnnouncementDTO
{
    public function __construct(
        public readonly ?string  $title        = null,
        public readonly ?string  $content      = null,
        public readonly ?bool    $is_active    = null,
        public readonly ?bool    $is_pinned    = null,
        public readonly ?Carbon  $published_at = null,
    ) {}

    public static function fromRequest(UpdateAnnouncementRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title:        $validated['title'] ?? null,
            content:      $validated['content'] ?? null,
            is_active:    isset($validated['is_active']) ? (bool) $validated['is_active'] : null,
            is_pinned:    isset($validated['is_pinned']) ? (bool) $validated['is_pinned'] : null,
            published_at: isset($validated['published_at']) ? Carbon::parse($validated['published_at']) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title'        => $this->title,
            'content'      => $this->content,
            'is_active'    => $this->is_active,
            'is_pinned'    => $this->is_pinned,
            'published_at' => $this->published_at?->toDateTimeString(),
        ], fn(mixed $value): bool => $value !== null);
    }
}
