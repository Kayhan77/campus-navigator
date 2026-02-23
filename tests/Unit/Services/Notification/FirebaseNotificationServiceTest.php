<?php

namespace Tests\Unit\Services\Notification;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Notification\FirebaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\MessagingError;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FirebaseNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private $messagingMock;
    private FirebaseNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messagingMock = Mockery::mock(Messaging::class);
        $this->service       = new FirebaseNotificationService($this->messagingMock);
    }

    #[Test]
    public function it_returns_true_on_successful_single_token_send(): void
    {
        $this->messagingMock
            ->shouldReceive('send')
            ->once()
            ->andReturn([]);

        $result = $this->service->sendToToken('valid-token-123', 'Hello', 'World');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_and_deletes_token_on_not_found_error(): void
    {
        $token = 'invalid-token-xyz';

        DeviceToken::factory()->create(['token' => $token]);

        $exception = new MessagingError('Registration token not found (not-found)');

        $this->messagingMock
            ->shouldReceive('send')
            ->once()
            ->andThrow($exception);

        $result = $this->service->sendToToken($token, 'Hello', 'World');

        $this->assertFalse($result);
        $this->assertDatabaseMissing('device_tokens', ['token' => $token]);
    }

    #[Test]
    public function it_returns_false_but_keeps_token_on_transient_error(): void
    {
        $token = 'valid-but-unreachable-token';

        DeviceToken::factory()->create(['token' => $token]);

        $exception = new MessagingError('Internal server error');

        $this->messagingMock
            ->shouldReceive('send')
            ->once()
            ->andThrow($exception);

        $result = $this->service->sendToToken($token, 'Hello', 'World');

        $this->assertFalse($result);
        // Token must NOT be deleted for transient errors
        $this->assertDatabaseHas('device_tokens', ['token' => $token]);
    }

    #[Test]
    public function it_skips_send_to_user_with_no_tokens(): void
    {
        $this->expectNotToPerformAssertions();

        $user = User::factory()->create();

        // No tokens registered — messaging should never be called
        $this->messagingMock->shouldNotReceive('sendMulticast');

        $this->service->sendToUser($user, 'Test', 'Message');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
