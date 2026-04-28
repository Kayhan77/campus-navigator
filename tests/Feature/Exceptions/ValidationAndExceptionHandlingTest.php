<?php

namespace Tests\Feature\Exceptions;

use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Tests\TestCase;

class ValidationAndExceptionHandlingTest extends TestCase
{
    protected User $user;
    protected User $anotherUser;
    protected Event $event;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::factory()->create(['capacity' => 2]);
        $this->user = User::factory()->create();
        $this->anotherUser = User::factory()->create();
        $this->event = Event::factory()->create([
            'room_id' => $this->room->id,
            'registration_required' => true,
            'max_attendees' => 2,
            'registered_users_count' => 0,
        ]);
    }

    /**
     * Test: Duplicate event registration throws AlreadyRegisteredException.
     */
    public function test_duplicate_event_registration_throws_already_registered_exception(): void
    {
        // Register user to event first time
        $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/events/{$this->event->id}/register")
            ->assertStatus(200);

        // Attempt duplicate registration
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/events/{$this->event->id}/register");

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'You are already registered for this event.');
    }
    /**
     * Test: Event capacity enforcement throws EventFullException.
     */
    public function test_event_full_throws_event_full_exception(): void
    {
        // Fill the event to capacity (max_attendees = 2)
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();

        $this->actingAs($user1, 'api')->postJson("/api/v1/events/{$this->event->id}/register")->assertStatus(200);
        $this->actingAs($user2, 'api')->postJson("/api/v1/events/{$this->event->id}/register")->assertStatus(200);

        // Verify event is full
        $this->event->refresh();
        $this->assertEquals(2, $this->event->registered_users_count);

        // Attempt registration when full
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/events/{$this->event->id}/register");

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'This event has reached maximum capacity.');
    }

    /**
     * Test: Unregister is idempotent (safe to call multiple times).
     */
    public function test_unregister_is_idempotent(): void
    {
        // Register user
        $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/events/{$this->event->id}/register")
            ->assertStatus(200);

        // Unregister first time using DELETE
        $response1 = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/events/{$this->event->id}/register");
        $response1->assertStatus(200);

        // Unregister second time (should still succeed)
        $response2 = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/events/{$this->event->id}/register");
        $response2->assertStatus(200);
    }

    /**
     * Test: All error responses use consistent ApiResponse format.
     */
    public function test_error_responses_use_consistent_format(): void
    {
        $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/events/{$this->event->id}/register")
            ->assertStatus(200);

        // Duplicate registration should fail with 409
        $errorResponse = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/events/{$this->event->id}/register");

        // Error responses have success, message (and optionally errors or data)
        $errorResponse->assertJsonStructure([
            'success',
            'message',
        ]);

        $this->assertFalse($errorResponse->json('success'));
        $this->assertEquals(409, $errorResponse->status());
    }

    /**
     * Test: 404 error on non-existent event returns consistent format.
     */
    public function test_404_error_returns_consistent_format(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/events/99999/register');

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Resource not found');
    }

    /**
     * Test: Unauthenticated requests return 401.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson("/api/v1/events/{$this->event->id}/register");

        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test: Capacity counter is accurate after multiple registrations.
     */
    public function test_capacity_counter_accuracy(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();

        // Register first user
        $this->actingAs($user1, 'api')->postJson("/api/v1/events/{$this->event->id}/register")->assertStatus(200);
        $this->event->refresh();
        $this->assertEquals(1, $this->event->registered_users_count);

        // Register second user
        $this->actingAs($user2, 'api')->postJson("/api/v1/events/{$this->event->id}/register")->assertStatus(200);
        $this->event->refresh();
        $this->assertEquals(2, $this->event->registered_users_count);

        // Third user should fail (at capacity)
        $response = $this->actingAs($user3, 'api')->postJson("/api/v1/events/{$this->event->id}/register");
        $response->assertStatus(409);

        // Unregister first user using DELETE
        $this->actingAs($user1, 'api')->deleteJson("/api/v1/events/{$this->event->id}/register")->assertStatus(200);
        $this->event->refresh();
        $this->assertEquals(1, $this->event->registered_users_count);

        // Now third user should be able to register
        $this->actingAs($user3, 'api')->postJson("/api/v1/events/{$this->event->id}/register")->assertStatus(200);
        $this->event->refresh();
        $this->assertEquals(2, $this->event->registered_users_count);
    }

    /**
     * Test: Event with no capacity limit allows unlimited registrations.
     */
    public function test_unlimited_capacity_event(): void
    {
        $unlimitedEvent = Event::factory()->create([
            'room_id' => $this->room->id,
            'registration_required' => true,
            'max_attendees' => null, // unlimited
        ]);

        $users = User::factory(10)->create();

        foreach ($users as $user) {
            $response = $this->actingAs($user, 'api')
                ->postJson("/api/v1/events/{$unlimitedEvent->id}/register");
            $response->assertStatus(200);
        }

        $unlimitedEvent->refresh();
        $this->assertEquals(10, $unlimitedEvent->registered_users_count);
    }
}
