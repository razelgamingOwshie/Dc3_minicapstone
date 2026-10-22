<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Pet, Service, Appointment, Vaccination};
use Carbon\Carbon;

class DashboardTest extends TestCase
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

    public function test_dashboard_counts_match_database(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $pet1 = Pet::factory()->create(['user_id' => $owner->id]);
        $pet2 = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create();

        Appointment::factory()->create(['user_id' => $owner->id, 'pet_id' => $pet1->id, 'service_id' => $service->id, 'appointment_date' => today(), 'status' => 'pending']);
        Appointment::factory()->create(['user_id' => $owner->id, 'pet_id' => $pet2->id, 'service_id' => $service->id, 'appointment_date' => today(), 'status' => 'completed']);
        Appointment::factory()->create(['user_id' => $owner->id, 'pet_id' => $pet1->id, 'service_id' => $service->id, 'appointment_date' => today()->addDay(), 'status' => 'approved']);

        Vaccination::factory()->create(['pet_id' => $pet1->id, 'next_due_date' => today()->addDays(15)]);
        Vaccination::factory()->create(['pet_id' => $pet2->id, 'next_due_date' => today()->addDays(45)]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson('/api/dashboard');
        $r->assertOk();
        $this->assertEquals(2, $r->json('total_pets'));
        $this->assertEquals(2, $r->json('appointments_today'));
        $this->assertEquals(1, $r->json('pending_appointments'));
        $this->assertEquals(1, $r->json('completed_consultations'));
        $this->assertEquals(1, $r->json('upcoming_vaccinations'));
    }

    public function test_dashboard_handles_empty_database(): void
    {
        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson('/api/dashboard');
        $r->assertOk();
        $this->assertEquals(0, $r->json('total_pets'));
        $this->assertEquals(0, $r->json('appointments_today'));
        $this->assertEquals(0, $r->json('pending_appointments'));
        $this->assertEquals(0, $r->json('completed_consultations'));
        $this->assertEquals(0, $r->json('upcoming_vaccinations'));
    }
}