<?php

declare(strict_types=1);

namespace App\DTOs\Announcement;

use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use Carbon\Carbon;

final class CreateAnnouncementDTO
{
    public function __construct(
        public readonly string   $title,
        public readonly string   $content,
        public readonly bool     $is_active    = true,
        public readonly bool     $is_pinned    = false,
        public readonly ?Carbon  $published_at = null,
    ) {}

    public static function fromRequest(StoreAnnouncementRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title:        $validated['title'],
            content:      $validated['content'],
            is_active:    (bool) ($validated['is_active'] ?? true),
            is_pinned:    (bool) ($validated['is_pinned'] ?? false),
            published_at: isset($validated['published_at']) ? Carbon::parse($validated['published_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'title'        => $this->title,
            'content'      => $this->content,
            'is_active'    => $this->is_active,
            'is_pinned'    => $this->is_pinned,
            'published_at' => $this->published_at?->toDateTimeString(),
        ];
    }
}
