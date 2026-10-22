<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Vaccination;

class VaccinationFactory extends Factory
{
    protected $model = Vaccination::class;

    public function definition(): array
    {
        return [
            'pet_id' => \App\Models\Pet::factory(),
            'vaccine_name' => fake()->randomElement(['Rabies', 'Distemper', 'Parvovirus', 'Bordetella', 'Feline Leukemia', 'DHPP']),
            'date_administered' => fake()->dateTimeBetween('-2 years', 'now'),
            'next_due_date' => fake()->dateTimeBetween('now', '+2 years'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}