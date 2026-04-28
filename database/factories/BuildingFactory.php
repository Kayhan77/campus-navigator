<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word() . ' Building',
            'type' => $this->faker->randomElement(['academic', 'administrative', 'residential', 'library', 'laboratory']),
            'category' => $this->faker->randomElement(['main', 'annex', 'extension']),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'description' => $this->faker->paragraph(),
            'image' => null,
            'opening_hours' => '08:00 - 20:00',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
