<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Pet;
use Carbon\Carbon;

class PetFactory extends Factory
{
    protected $model = Pet::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->firstName(),
            'species' => fake()->randomElement(['Dog', 'Cat', 'Bird', 'Rabbit', 'Hamster']),
            'breed' => fake()->optional()->word(),
            'sex' => fake()->randomElement(['Male', 'Female', 'Unknown']),
            'birth_date' => fake()->optional()->dateTimeBetween('-10 years', 'now'),
            'weight' => fake()->optional()->randomFloat(2, 0.5, 50),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}