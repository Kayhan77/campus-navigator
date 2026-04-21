<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/admin/notifications', [
            'title' => 'Test',
            'body' => 'Test body',
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser, 'api')
            ->postJson('/api/v1/admin/notifications', [
                'title' => 'Test',
                'body' => 'Test body',
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function it_sends_notification_to_all_users(): void
    {
        $users = User::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/notifications', [
                'title' => 'Broadcast Notification',
                'body' => 'This goes to everyone',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('notifications', [
            'title' => 'Broadcast Notification',
        ]);

        $notification = Notification::where('title', 'Broadcast Notification')->first();

        // Check that recipients were created
        $recipients = NotificationRecipient::where('notification_id', $notification->id)->get();
        $this->assertGreaterThan(0, $recipients->count());
    }

    #[Test]
    public function it_sends_notification_to_specific_users(): void
    {
        $targetUsers = User::factory()->count(2)->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/notifications', [
                'title' => 'Targeted Notification',
                'body' => 'For specific users only',
                'user_ids' => $targetUsers->pluck('id')->toArray(),
            ]);

        $response->assertOk();

        $notification = Notification::where('title', 'Targeted Notification')->first();
        $recipients = NotificationRecipient::where('notification_id', $notification->id)
            ->pluck('user_id')
            ->toArray();

        $this->assertEqualsCanonicalizing($targetUsers->pluck('id')->toArray(), $recipients);
        $this->assertNotContains($otherUser->id, $recipients);
    }

    #[Test]
    public function it_validates_title_required(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/notifications', [
                'body' => 'Missing title',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('title');
    }

    #[Test]
    public function it_validates_body_required(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/notifications', [
                'title' => 'Test Title',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('body');
    }

    #[Test]
    public function it_validates_user_ids_exist(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/notifications', [
                'title' => 'Test',
                'body' => 'Test body',
                'user_ids' => [99999], // Non-existent user ID
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('user_ids.0');
    }

    #[Test]
    public function it_returns_sent_count_in_response(): void
    {
        User::factory()->count(2)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/notifications', [
                'title' => 'Test',
                'body' => 'Test body',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => ['sent', 'failed', 'notification_id'],
        ]);
    }

    #[Test]
    public function it_respects_user_notification_preferences(): void
    {
        $enabledUser = User::factory()->create([
            'notification_preferences' => json_encode(['enabled' => true])
        ]);
        $disabledUser = User::factory()->create([
            'notification_preferences' => json_encode(['enabled' => false])
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/notifications', [
                'title' => 'Preference Test',
                'body' => 'Testing preferences',
            ]);

        $response->assertOk();

        $notification = Notification::where('title', 'Preference Test')->first();
        $recipients = NotificationRecipient::where('notification_id', $notification->id)
            ->pluck('user_id')
            ->toArray();

        $this->assertContains($enabledUser->id, $recipients);
        $this->assertNotContains($disabledUser->id, $recipients);
    }
}
