<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'image' => null,
            'location' => $this->faker->address(),
            'location_override' => $this->faker->optional()->address(),
            'start_time' => $this->faker->dateTimeBetween('+1 day', '+2 days'),
            'end_time' => $this->faker->dateTimeBetween('+2 days', '+3 days'),
            'status' => $this->faker->randomElement(['scheduled', 'ongoing', 'completed', 'cancelled']),
            'is_public' => $this->faker->boolean(),
            'registration_required' => true,
            'max_attendees' => $this->faker->numberBetween(10, 100),
            'registered_users_count' => 0,
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
