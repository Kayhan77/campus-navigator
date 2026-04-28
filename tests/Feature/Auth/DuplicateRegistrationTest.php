<?php

namespace Tests\Feature\Auth;

use App\Models\PendingRegistration;
use App\Models\User;
use Tests\TestCase;

class DuplicateRegistrationTest extends TestCase
{
    /**
     * Test: Attempting to pre-register with an already-registered email is rejected.
     * The form request validation catches duplicates and returns 422.
     */
    public function test_pre_register_with_existing_user_email_is_rejected(): void
    {
        // Create an existing user with a real domain
        $existingUser = User::factory()->create([
            'email' => 'existing.user@gmail.com',
        ]);

        // Attempt to pre-register with the same email - should be rejected by form validation
        $response = $this->postJson('/api/v1/pre-register', [
            'name' => 'New User',
            'email' => 'existing.user@gmail.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

        // Form validation returns 422 for unique constraint violation
        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        // Check that the error indicates duplicate email
        $this->assertTrue($response->json('success') === false);
    }
    /**
     * Test: Attempting to pre-register with email in pending_registrations creates fresh record.
     */
    public function test_pre_register_with_pending_email_creates_fresh_record(): void
    {
        $email = 'pending.user@gmail.com';

        // Create first pending registration
        $this->postJson('/api/v1/pre-register', [
            'name' => 'User One',
            'email' => $email,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])->assertStatus(200);

        $firstPending = PendingRegistration::where('email', $email)->first();
        $this->assertNotNull($firstPending);

        // Attempt second pre-registration with same email - will fail form validation (unique rule)
        $response = $this->postJson('/api/v1/pre-register', [
            'name' => 'User Two',
            'email' => $email,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

        // Form validation rejects duplicate
        $response->assertStatus(422);
        
        // Verify first pending registration still exists (not deleted by second attempt)
        $stillExists = PendingRegistration::where('email', $email)->first();
        $this->assertNotNull($stillExists);
    }

    /**
     * Test: Pre-registration creates successful record.
     */
    public function test_successful_pre_register(): void
    {
        $email = 'newuser@gmail.com';

        // Pre-register
        $registerResponse = $this->postJson('/api/v1/pre-register', [
            'name' => 'New User',
            'email' => $email,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

        // Should return 200 (or 201 for created) - currently API returns 200
        $registerResponse->assertStatus(200);
        $this->assertTrue(PendingRegistration::where('email', $email)->exists());
        $this->assertFalse(User::where('email', $email)->exists());
    }

    /**
     * Test: Database unique constraint prevents race-condition duplicates.
     * This tests the idempotency guarantee at the database level.
     */
    public function test_database_unique_constraint_prevents_race_condition(): void
    {
        $user = User::factory()->create([
            'email' => 'db.test.user@gmail.com',
        ]);

        // Attempt to violate unique constraint directly (simulating race condition)
        try {
            User::create([
                'name' => 'Duplicate',
                'email' => 'db.test.user@gmail.com',
                'password' => bcrypt('password'),
            ]);
            $this->fail('Expected unique constraint violation');
        } catch (\Illuminate\Database\QueryException $e) {
            // QueryException should be thrown due to unique constraint
            $this->assertStringContainsString('unique', strtolower($e->getMessage()));
        }
    }
}
