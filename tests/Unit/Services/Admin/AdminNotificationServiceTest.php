<?php

namespace Tests\Unit\Services\Admin;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Models\DeviceToken;
use App\Services\Admin\AdminNotificationService;
use App\Services\FirebaseService;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminNotificationService $service;
    private $firebaseMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firebaseMock = Mockery::mock(FirebaseService::class);
        $this->service = new AdminNotificationService(new NotificationService($this->firebaseMock));

        // Mock authentication
        /** @var User $admin */
        $admin = User::factory()->createOne(['role' => 'super_admin']);
        $this->actingAs($admin);
    }

    #[Test]
    public function it_creates_notification_record(): void
    {
        $this->firebaseMock
            ->shouldReceive('sendNotification')
            ->andReturn(null);

        $result = $this->service->sendAdminNotification(
            title: 'Test Title',
            body: 'Test Body'
        );

        $this->assertDatabaseHas('notifications', [
            'title' => 'Test Title',
            'message' => 'Test Body',
            'type' => 'admin',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('notification_id', $result);
    }

    #[Test]
    public function it_creates_notification_recipients_for_all_users(): void
    {
        $users = User::factory()->count(5)->create();
        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $result = $this->service->sendAdminNotification(
            title: 'All Users Notification',
            body: 'Testing notification recipients'
        );

        $notificationId = $result['notification_id'];
        $recipients = NotificationRecipient::where('notification_id', $notificationId)->get();

        // Should have 6 recipients (5 created + current user)
        $this->assertGreaterThanOrEqual(5, $recipients->count());

        foreach ($recipients as $recipient) {
            $this->assertFalse($recipient->is_read);
            $this->assertNull($recipient->read_at);
        }
    }

    #[Test]
    public function it_creates_notification_recipients_for_specific_users(): void
    {
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $result = $this->service->sendAdminNotification(
            title: 'Specific Users Notification',
            body: 'Only for specific users',
            userIds: $userIds
        );

        $notificationId = $result['notification_id'];
        $recipients = NotificationRecipient::where('notification_id', $notificationId)->get();

        $this->assertCount(3, $recipients);

        foreach ($recipients as $recipient) {
            $this->assertContains($recipient->user_id, $userIds);
        }
    }

    #[Test]
    public function it_updates_delivered_at_when_notification_sent(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $deviceToken = DeviceToken::factory()->create(['user_id' => $user->id]);

        $this->firebaseMock
            ->shouldReceive('sendNotification')
            ->once()
            ->andReturn(null);

        $this->actingAs($user);

        $result = $this->service->sendAdminNotification(
            title: 'Delivery Test',
            body: 'Test delivery tracking'
        );

        $notificationId = $result['notification_id'];
        $recipient = NotificationRecipient::where('notification_id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($recipient->delivered_at);
    }

    #[Test]
    public function it_returns_sent_and_failed_counts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        DeviceToken::factory()->create(['user_id' => $user1->id]);
        DeviceToken::factory()->create(['user_id' => $user2->id]);

        $this->firebaseMock
            ->shouldReceive('sendNotification')
            ->andReturn(null);

        $result = $this->service->sendAdminNotification(
            title: 'Count Test',
            body: 'Testing sent/failed counts',
            userIds: [$user1->id, $user2->id]
        );

        $this->assertGreaterThanOrEqual(1, $result['sent']);
        $this->assertIsInt($result['failed']);
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

        $result = $this->service->sendAdminNotification(
            title: 'Preferences Test',
            body: 'Testing notification preferences'
        );

        $notificationId = $result['notification_id'];
        $recipients = NotificationRecipient::where('notification_id', $notificationId)
            ->pluck('user_id')
            ->toArray();

        $this->assertContains($enabledUser->id, $recipients);
        $this->assertNotContains($disabledUser->id, $recipients);
    }

    #[Test]
    public function it_stores_optional_data_in_notification(): void
    {
        $this->firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $data = ['special_id' => 123, 'action' => 'view_event'];

        $result = $this->service->sendAdminNotification(
            title: 'Data Test',
            body: 'Test with additional data',
            data: $data
        );

        $this->assertDatabaseHas('notifications', [
            'id' => $result['notification_id'],
            'data' => json_encode($data),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
