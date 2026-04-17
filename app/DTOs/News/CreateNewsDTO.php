<?php

declare(strict_types=1);

namespace App\DTOs\News;

use App\Http\Requests\News\StoreNewsRequest;
use Carbon\Carbon;

final class CreateNewsDTO
{
    public function __construct(
        public readonly string   $title,
        public readonly string   $content,
        public readonly bool     $is_published = true,
        public readonly ?Carbon  $published_at = null,
    ) {}

    public static function fromRequest(StoreNewsRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title:        $validated['title'],
            content:      $validated['content'],
            is_published: (bool) ($validated['is_published'] ?? true),
            published_at: isset($validated['published_at']) ? Carbon::parse($validated['published_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'title'        => $this->title,
            'content'      => $this->content,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toDateTimeString(),
        ];
    }
}
