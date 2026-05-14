<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Event;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

final class EventNotificationObserver
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Send Firebase notification to all users when an event is created.
     */
    public function created(Event $event): void
    {
        try {
            // Only send notification if event is published
            if ($event->status !== 'published') {
                return;
            }

            $this->notificationService->sendAndStoreNotification(
                title: "New Event: {$event->title}",
                message: substr($event->description ?? 'A new event has been created', 0, 100),
                type: 'event',
                data: [
                    'event_id' => $event->id,
                    'title' => $event->title,
                    'start_time' => $event->start_time->toIso8601String(),
                ],
            );

            Log::info('[Event Notification] Notification sent for event creation', [
                'event_id' => $event->id,
                'event_title' => $event->title,
            ]);
        } catch (\Exception $e) {
            Log::error('[Event Notification] Failed to send notification', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
