<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Announcement;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

final class AnnouncementNotificationObserver
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Send Firebase notification to all users when announcement is created.
     */
    public function created(Announcement $announcement): void
    {
        try {
            // Only send notification if announcement is active
            if (!$announcement->is_active) {
                return;
            }

            $this->notificationService->sendAndStoreNotification(
                title: "📢 Announcement: {$announcement->title}",
                message: substr($announcement->content ?? 'A new announcement has been posted', 0, 100),
                type: 'announcement',
                data: [
                    'announcement_id' => $announcement->id,
                    'title' => $announcement->title,
                    'is_pinned' => $announcement->is_pinned,
                    'published_at' => $announcement->published_at?->toIso8601String(),
                ],
            );

            Log::info('[Announcement Notification] Notification sent for announcement creation', [
                'announcement_id' => $announcement->id,
                'announcement_title' => $announcement->title,
            ]);
        } catch (\Exception $e) {
            Log::error('[Announcement Notification] Failed to send notification', [
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
