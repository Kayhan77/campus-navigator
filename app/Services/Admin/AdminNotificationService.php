<?php

namespace App\Services\Admin;

use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Auth;

class AdminNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Send admin notification to users via Firebase.
     *
     * Creates a notification record, sets up recipients, and sends push notifications.
     *
     * @param string $title The notification title
     * @param string $body The notification body
     * @param array|null $userIds If null or empty, send to all users. Otherwise send only to specified user IDs.
     * @param array|null $data Optional additional data to store with the notification
     * @return array Result with 'sent', 'failed', and 'notification_id'
     */
    public function sendAdminNotification(
        string $title,
        string $body,
        ?array $userIds = null,
        ?array $data = null
    ): array {
        return $this->notificationService->sendAndStoreNotification(
            title: $title,
            message: $body,
            type: 'admin',
            data: $data,
            userIds: $userIds,
            senderId: Auth::id()
        );
    }
}

