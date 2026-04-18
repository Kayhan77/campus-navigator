<?php

declare(strict_types=1);

namespace App\DTOs\News;

use App\Http\Requests\News\UpdateNewsRequest;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

final class UpdateNewsDTO
{
    public function __construct(
        public readonly ?string        $title        = null,
        public readonly ?string        $content      = null,
        public readonly ?bool          $is_published = null,
        public readonly ?Carbon        $published_at = null,
        public readonly ?UploadedFile  $image        = null,
    ) {}

    public static function fromRequest(UpdateNewsRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title:        $validated['title'] ?? null,
            content:      $validated['content'] ?? null,
            is_published: isset($validated['is_published']) ? (bool) $validated['is_published'] : null,
            published_at: isset($validated['published_at']) ? Carbon::parse($validated['published_at']) : null,
            image:        $request->file('image'),
        );
    }

    public function toArray(): array
    {
        $data = [
            'title'        => $this->title,
            'content'      => $this->content,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toDateTimeString(),
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
