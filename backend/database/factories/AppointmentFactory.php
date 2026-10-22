<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $start = Carbon::tomorrow()->setTime(
            fake()->numberBetween(8, 16),
            fake()->randomElement([0, 15, 30, 45])
        );

        return [
            'user_id' => \App\Models\User::factory(),
            'pet_id' => \App\Models\Pet::factory(),
            'service_id' => \App\Models\Service::factory(),
            'appointment_date' => $start,
            'reason' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['pending', 'approved', 'completed', 'cancelled']),
        ];
    }
}