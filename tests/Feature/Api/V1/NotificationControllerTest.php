<?php

namespace Tests\Feature\Api\V1;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_403_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_retrieves_user_notifications_with_pagination(): void
    {
        $notifications = collect(range(1, 5))->map(fn () => $this->createNotification());

        foreach ($notifications as $notification) {
            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $this->user->id,
                'is_read' => false,
            ]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications?per_page=2');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => ['id', 'title', 'message', 'type', 'is_read'],
                ],
                'meta' => ['total', 'per_page', 'current_page'],
            ],
        ]);
    }

    #[Test]
    public function it_filters_unread_notifications(): void
    {
        $readNotification = $this->createNotification();
        $unreadNotification = $this->createNotification();

        NotificationRecipient::create([
            'notification_id' => $readNotification->id,
            'user_id' => $this->user->id,
            'is_read' => true,
            'read_at' => now(),
        ]);

        NotificationRecipient::create([
            'notification_id' => $unreadNotification->id,
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications?is_read=0');

        $response->assertOk();
        $data = $response->json('data.data');

        $this->assertCount(1, $data);
        $this->assertFalse($data[0]['is_read']);
    }

    #[Test]
    public function it_retrieves_specific_notification(): void
    {
        $notification = $this->createNotification();
        NotificationRecipient::create([
            'notification_id' => $notification->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $notification->id);
        $response->assertJsonPath('data.title', $notification->title);
    }

    #[Test]
    public function it_returns_404_for_unauthorized_notification_access(): void
    {
        $otherUser = User::factory()->create();
        $notification = $this->createNotification();

        NotificationRecipient::create([
            'notification_id' => $notification->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertNotFound();
    }

    #[Test]
    public function it_marks_notification_as_read(): void
    {
        $notification = $this->createNotification();
        $recipient = NotificationRecipient::create([
            'notification_id' => $notification->id,
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk();

        $recipient->refresh();
        $this->assertTrue($recipient->is_read);
        $this->assertNotNull($recipient->read_at);
    }

    #[Test]
    public function it_marks_all_notifications_as_read(): void
    {
        $notifications = collect(range(1, 3))->map(fn () => $this->createNotification());

        foreach ($notifications as $notification) {
            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $this->user->id,
                'is_read' => false,
            ]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/notifications/mark-all-as-read');

        $response->assertOk();

        $unreadCount = NotificationRecipient::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count();

        $this->assertSame(0, $unreadCount);
    }

    #[Test]
    public function it_returns_unread_count(): void
    {
        $notifications = collect(range(1, 3))->map(fn () => $this->createNotification());

        foreach ($notifications as $key => $notification) {
            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $this->user->id,
                'is_read' => $key === 0, // First one is read
            ]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk();
        $response->assertJsonPath('data.unread_count', 2);
    }

    #[Test]
    public function it_does_not_show_notifications_from_other_users(): void
    {
        $otherUser = User::factory()->create();
        $notification1 = $this->createNotification();
        $notification2 = $this->createNotification();

        NotificationRecipient::create([
            'notification_id' => $notification1->id,
            'user_id' => $this->user->id,
        ]);

        NotificationRecipient::create([
            'notification_id' => $notification2->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications');

        $response->assertOk();
        $data = $response->json('data.data');

        $this->assertCount(1, $data);
        $this->assertSame($notification1->id, $data[0]['id']);
    }

    private function createNotification(array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'title' => 'Test Notification',
            'message' => 'Test Message',
            'type' => 'system',
            'target_role' => 'all',
            'data' => null,
            'sender_id' => null,
        ], $overrides));
    }
}
