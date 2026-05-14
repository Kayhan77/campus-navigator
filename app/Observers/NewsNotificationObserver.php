<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\News;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

final class NewsNotificationObserver
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Send Firebase notification to all users when news is created.
     */
    public function created(News $news): void
    {
        try {
            // Only send notification if news is published
            if (!$news->is_published) {
                return;
            }

            $this->notificationService->sendAndStoreNotification(
                title: "📰 News: {$news->title}",
                message: substr($news->content ?? 'New campus news has been published', 0, 100),
                type: 'news',
                data: [
                    'news_id' => $news->id,
                    'title' => $news->title,
                    'published_at' => $news->published_at->toIso8601String(),
                ],
            );

            Log::info('[News Notification] Notification sent for news creation', [
                'news_id' => $news->id,
                'news_title' => $news->title,
            ]);
        } catch (\Exception $e) {
            Log::error('[News Notification] Failed to send notification', [
                'news_id' => $news->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
