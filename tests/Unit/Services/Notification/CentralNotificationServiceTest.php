<?php

namespace Tests\Unit\Services\Notification;

use App\Models\Announcement;
use App\Models\DeviceToken;
use App\Models\Event;
use App\Models\News;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;
    private $firebaseMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firebaseMock = Mockery::mock(FirebaseService::class);
        $this->service = new NotificationService($this->firebaseMock);
    }

    #[Test]
    public function it_creates_notification_record_with_all_data(): void
    {
        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        User::factory()->create();

        $result = $this->service->sendAndStoreNotification(
            title: 'Test Notification',
            message: 'Test message',
            type: 'event',
            data: ['event_id' => 42],
            senderId: 1
        );

        $this->assertDatabaseHas('notifications', [
            'title' => 'Test Notification',
            'message' => 'Test message',
            'type' => 'event',
            'data' => json_encode(['event_id' => 42]),
            'sender_id' => 1,
        ]);

        $this->assertArrayHasKey('notification_id', $result);
    }

    #[Test]
    public function it_creates_notification_recipients_for_all_users(): void
    {
        $users = User::factory()->count(5)->create();
        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $result = $this->service->sendAndStoreNotification(
            title: 'Broadcast Notification',
            message: 'For all users',
            type: 'system'
        );

        $notificationId = $result['notification_id'];
        $recipients = NotificationRecipient::where('notification_id', $notificationId)->get();

        $this->assertGreaterThanOrEqual(5, $recipients->count());
        foreach ($recipients as $recipient) {
            $this->assertFalse($recipient->is_read);
            $this->assertNull($recipient->read_at);
        }
    }

    #[Test]
    public function it_creates_notification_recipients_for_specific_users(): void
    {
        $targetUsers = User::factory()->count(3)->create();
        $otherUser = User::factory()->create();
        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $result = $this->service->sendAndStoreNotification(
            title: 'Targeted Notification',
            message: 'Only for specific users',
            type: 'event',
            userIds: $targetUsers->pluck('id')->toArray()
        );

        $notificationId = $result['notification_id'];
        $recipients = NotificationRecipient::where('notification_id', $notificationId)
            ->pluck('user_id')
            ->toArray();

        $this->assertCount(3, $recipients);
        foreach ($recipients as $userId) {
            $this->assertContains($userId, $targetUsers->pluck('id')->toArray());
        }
        $this->assertNotContains($otherUser->id, $recipients);
    }

    #[Test]
    public function it_respects_notification_preferences(): void
    {
        $enabledUser = User::factory()->create([
            'notification_preferences' => json_encode(['enabled' => true])
        ]);
        $disabledUser = User::factory()->create([
            'notification_preferences' => json_encode(['enabled' => false])
        ]);

        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $result = $this->service->sendAndStoreNotification(
            title: 'Preferences Test',
            message: 'Testing preferences',
            type: 'system'
        );

        $notificationId = $result['notification_id'];
        $recipients = NotificationRecipient::where('notification_id', $notificationId)
            ->pluck('user_id')
            ->toArray();

        $this->assertContains($enabledUser->id, $recipients);
        $this->assertNotContains($disabledUser->id, $recipients);
    }

    #[Test]
    public function it_sends_firebase_and_updates_delivered_at(): void
    {
        $user = User::factory()->create();
        $deviceToken = DeviceToken::factory()->create(['user_id' => $user->id]);

        $this->firebaseMock
            ->shouldReceive('sendNotification')
            ->once()
            ->andReturn(null);

        $result = $this->service->sendAndStoreNotification(
            title: 'Delivery Test',
            message: 'Testing delivery tracking',
            type: 'event',
            userIds: [$user->id]
        );

        $notificationId = $result['notification_id'];
        $recipient = NotificationRecipient::where('notification_id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($recipient->delivered_at);
    }

    #[Test]
    public function it_returns_accurate_sent_failed_counts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        DeviceToken::factory()->create(['user_id' => $user1->id]);
        DeviceToken::factory()->create(['user_id' => $user2->id]);

        $this->firebaseMock
            ->shouldReceive('sendNotification')
            ->times(2)
            ->andReturn(null);

        $result = $this->service->sendAndStoreNotification(
            title: 'Count Test',
            message: 'Testing counts',
            type: 'news',
            userIds: [$user1->id, $user2->id]
        );

        $this->assertEquals(2, $result['sent']);
        $this->assertEquals(0, $result['failed']);
    }

    #[Test]
    public function it_handles_users_without_device_tokens(): void
    {
        $userWithToken = User::factory()->create();
        $userWithoutToken = User::factory()->create();
        DeviceToken::factory()->create(['user_id' => $userWithToken->id]);

        $this->firebaseMock
            ->shouldReceive('sendNotification')
            ->once()
            ->andReturn(null);

        $result = $this->service->sendAndStoreNotification(
            title: 'Mixed Users Test',
            message: 'Some have tokens, some dont',
            type: 'event',
            userIds: [$userWithToken->id, $userWithoutToken->id]
        );

        // Should still create recipients for both
        $notificationId = $result['notification_id'];
        $recipients = NotificationRecipient::where('notification_id', $notificationId)->count();
        $this->assertEquals(2, $recipients);

        // But only send to the one with token
        $this->assertEquals(1, $result['sent']);
    }

    #[Test]
    public function it_uses_correct_notification_types(): void
    {
        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $types = ['event', 'news', 'announcement', 'admin', 'system'];

        User::factory()->create();

        foreach ($types as $type) {
            $result = $this->service->sendAndStoreNotification(
                title: "Test {$type}",
                message: "Message for {$type}",
                type: $type
            );

            $this->assertDatabaseHas('notifications', [
                'id' => $result['notification_id'],
                'type' => $type,
            ]);
        }
    }

    #[Test]
    public function it_stores_custom_data_correctly(): void
    {
        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        User::factory()->create();

        $customData = [
            'event_id' => 123,
            'room_id' => 45,
            'action' => 'view',
        ];

        $result = $this->service->sendAndStoreNotification(
            title: 'Data Test',
            message: 'Testing data storage',
            type: 'event',
            data: $customData
        );

        $notification = Notification::find($result['notification_id']);
        $this->assertEquals($customData, $notification->data);
    }

    #[Test]
    public function it_creates_unique_constraint_per_notification_user(): void
    {
        $user = User::factory()->create();
        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $result = $this->service->sendAndStoreNotification(
            title: 'Unique Test',
            message: 'Testing uniqueness',
            type: 'system',
            userIds: [$user->id]
        );

        $notificationId = $result['notification_id'];
        $recipients = NotificationRecipient::where('notification_id', $notificationId)
            ->where('user_id', $user->id)
            ->get();

        $this->assertCount(1, $recipients);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
