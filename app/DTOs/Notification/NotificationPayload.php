<?php

namespace App\DTOs\Notification;

/**
 * Immutable value object that encapsulates everything needed to build
 * and send a push notification.
 *
 * Decouples the job/service from raw string parameters, enabling
 * localization, rich content, and payload evolution without changing
 * every call site.
 */
final class NotificationPayload
{
    /**
     * @param string      $title     Notification title.
     * @param string      $body      Notification body text.
     * @param array       $data      Arbitrary key-value data for the Flutter app.
     * @param string|null $imageUrl  Optional image URL (shown in the notification).
     * @param string|null $actionUrl Deep-link or URL opened on tap.
     * @param string      $locale    BCP-47 locale code (e.g. "en", "ar"). The app
     *                               uses this to render content in the user's language.
     * @param string      $type      Logical notification type for client-side routing.
     */
    public function __construct(
        public readonly string  $title,
        public readonly string  $body,
        public readonly array   $data       = [],
        public readonly ?string $imageUrl   = null,
        public readonly ?string $actionUrl  = null,
        public readonly string  $locale     = 'en',
        public readonly string  $type       = 'general',
    ) {}

    // -------------------------------------------------------------------------
    // Factory helpers
    // -------------------------------------------------------------------------

    /**
     * Build a standard event-reminder payload.
     */
    public static function forEventReminder(
        string  $eventTitle,
        string  $startsIn,
        int     $eventId,
        string  $startTime,
        string  $locale     = 'en',
        ?string $imageUrl   = null,
    ): self {
        return new self(
            title:     '🔔 Event Reminder',
            body:      "\"{$eventTitle}\" starts in {$startsIn}!",
            data:      [
                'event_id'   => (string) $eventId,
                'start_time' => $startTime,
                'type'       => 'event_reminder',
            ],
            imageUrl:  $imageUrl,
            actionUrl: config('app.url') . "/events/{$eventId}",
            locale:    $locale,
            type:      'event_reminder',
        );
    }

    // -------------------------------------------------------------------------
    // Serialization — used when queuing the job (SerializesModels-compatible)
    // -------------------------------------------------------------------------

    /**
     * Merge internal metadata into the data payload for the Flutter client.
     * Never includes sensitive fields (token, user identifiers).
     */
    public function toDataArray(): array
    {
        $merged = array_merge($this->data, [
            'type'       => $this->type,
            'locale'     => $this->locale,
        ]);

        if ($this->imageUrl !== null) {
            $merged['image_url'] = $this->imageUrl;
        }

        if ($this->actionUrl !== null) {
            $merged['action_url'] = $this->actionUrl;
        }

        return $merged;
    }

    /**
     * Serialize to array for queuing / caching.
     */
    public function toArray(): array
    {
        return [
            'title'      => $this->title,
            'body'       => $this->body,
            'data'       => $this->data,
            'image_url'  => $this->imageUrl,
            'action_url' => $this->actionUrl,
            'locale'     => $this->locale,
            'type'       => $this->type,
        ];
    }

    /**
     * Reconstruct from a serialized array (used by the queued job).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title:     $data['title'],
            body:      $data['body'],
            data:      $data['data']       ?? [],
            imageUrl:  $data['image_url']  ?? null,
            actionUrl: $data['action_url'] ?? null,
            locale:    $data['locale']     ?? 'en',
            type:      $data['type']       ?? 'general',
        );
    }
}
