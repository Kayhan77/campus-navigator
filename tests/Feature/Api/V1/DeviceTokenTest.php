<?php

namespace Tests\Feature\Api\V1;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): array
    {
        $user  = User::factory()->create(['is_verified' => true]);
        $token = JWTAuth::fromUser($user);

        return [$user, ['Authorization' => "Bearer {$token}"]];
    }

    #[Test]
    public function authenticated_user_can_register_a_device_token(): void
    {
        [$user, $headers] = $this->actingAsUser();

        $response = $this->postJson('/api/v1/device-tokens', [
            'token'    => 'fcm-token-abcdefghijk123456789',
            'platform' => 'android',
        ], $headers);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Device token registered successfully.');

        $this->assertDatabaseHas('device_tokens', [
            'user_id'  => $user->id,
            'token'    => 'fcm-token-abcdefghijk123456789',
            'platform' => 'android',
        ]);
    }

    #[Test]
    public function registering_existing_token_updates_it_instead_of_duplicating(): void
    {
        [$user, $headers] = $this->actingAsUser();

        DeviceToken::create([
            'user_id'  => $user->id,
            'token'    => 'existing-fcm-token-long-enough',
            'platform' => 'ios',
        ]);

        $response = $this->postJson('/api/v1/device-tokens', [
            'token'    => 'existing-fcm-token-long-enough',
            'platform' => 'android',
        ], $headers);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Device token updated successfully.');

        // Still only one record for this token
        $this->assertCount(1, DeviceToken::where('token', 'existing-fcm-token-long-enough')->get());
    }

    #[Test]
    public function token_field_is_required(): void
    {
        [, $headers] = $this->actingAsUser();

        $response = $this->postJson('/api/v1/device-tokens', ['platform' => 'android'], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    #[Test]
    public function platform_must_be_valid_enum_value(): void
    {
        [, $headers] = $this->actingAsUser();

        $response = $this->postJson('/api/v1/device-tokens', [
            'token'    => 'some-long-enough-test-token-abc',
            'platform' => 'windows',
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }

    #[Test]
    public function unauthenticated_user_cannot_register_token(): void
    {
        $response = $this->postJson('/api/v1/device-tokens', [
            'token'    => 'some-long-enough-test-token-abc',
            'platform' => 'android',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_can_delete_their_device_token(): void
    {
        [$user, $headers] = $this->actingAsUser();

        DeviceToken::create([
            'user_id' => $user->id,
            'token'   => 'token-to-delete-long-enough-xx',
            'platform' => 'android',
        ]);

        $response = $this->deleteJson('/api/v1/device-tokens', [
            'token' => 'token-to-delete-long-enough-xx',
        ], $headers);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Device token removed successfully.');

        $this->assertDatabaseMissing('device_tokens', ['token' => 'token-to-delete-long-enough-xx']);
    }
}
