<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Pet, Vaccination};

class VaccinationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['email' => 'admin@test', 'role' => 'admin']);
        $this->adminToken = $this->admin->createToken('test')->plainTextToken;
    }

    public function test_vaccination_dates_and_filters(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);

        Vaccination::factory()->create(['pet_id' => $pet->id, 'vaccine_name' => 'Rabies', 'date_administered' => today()->subYear(), 'next_due_date' => today()->addDays(10)]);
        Vaccination::factory()->create(['pet_id' => $pet->id, 'vaccine_name' => 'Distemper', 'date_administered' => today()->subMonths(6), 'next_due_date' => today()->addDays(20)]);
        Vaccination::factory()->create(['pet_id' => $pet->id, 'vaccine_name' => 'Bordetella', 'date_administered' => today()->subMonths(3), 'next_due_date' => today()->addDays(40)]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson('/api/vaccinations');
        $r->assertOk();
        $this->assertCount(3, $r->json('data'));
    }
}