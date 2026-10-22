<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\MedicalRecord;

class MedicalRecordFactory extends Factory
{
    protected $model = MedicalRecord::class;

    public function definition(): array
    {
        return [
            'pet_id' => \App\Models\Pet::factory(),
            'appointment_id' => null,
            'diagnosis' => fake()->sentence(),
            'treatment' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'record_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}