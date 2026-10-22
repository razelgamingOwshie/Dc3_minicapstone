<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Pet, Service, Appointment};
use Carbon\Carbon;

class SearchFilterPaginationTest extends TestCase
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

    public function test_pets_search_by_name_and_species(): void
    {
        Pet::factory()->count(3)->create(['user_id' => $this->admin->id, 'species' => 'Dog']);
        Pet::factory()->create(['user_id' => $this->admin->id, 'name' => 'Buddy', 'species' => 'Dog']);
        Pet::factory()->create(['user_id' => $this->admin->id, 'name' => 'Whiskers', 'species' => 'Cat']);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson('/api/pets?search=Buddy');
        $r->assertOk();
        $this->assertCount(1, $r->json('data'));

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson('/api/pets?search=Cat');
        $r->assertOk();
        $this->assertCount(1, $r->json('data'));
    }

    public function test_appointments_filter_by_status(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create();

        Appointment::factory()->create(['user_id' => $owner->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'status' => 'pending']);
        Appointment::factory()->create(['user_id' => $owner->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'status' => 'approved']);
        Appointment::factory()->create(['user_id' => $owner->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'status' => 'completed']);

        $r = $this->withHeaders(['Authorization' => "Bearer {$owner->createToken('test')->plainTextToken}"])->getJson('/api/appointments?status=pending');
        $r->assertOk();
        $this->assertCount(1, $r->json('data'));
        $this->assertEquals('pending', $r->json('data.0.status'));

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson('/api/appointments?status=approved');
        $r->assertOk();
        $this->assertCount(1, $r->json('data'));
    }

    public function test_appointments_filter_by_date(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create();

        $date = today()->format('Y-m-d');
        Appointment::factory()->create(['user_id' => $owner->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'appointment_date' => "$date 10:00:00"]);
        Appointment::factory()->create(['user_id' => $owner->id, 'pet_id' => $pet->id, 'service_id' => $service->id, 'appointment_date' => today()->addDay()->format('Y-m-d H:i:s')]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson("/api/appointments?date=$date");
        $r->assertOk();
        $this->assertCount(1, $r->json('data'));
    }

    public function test_services_filter_by_available(): void
    {
        Service::factory()->create(['is_available' => true]);
        Service::factory()->create(['is_available' => false]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson('/api/services?available=true');
        $r->assertOk();
        $this->assertCount(1, $r->json('data'));
        $this->assertTrue($r->json('data.0.is_available'));
    }

    public function test_pagination_returns_correct_metadata(): void
    {
        Pet::factory()->count(25)->create(['user_id' => $this->admin->id]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson('/api/pets?per_page=10');
        $r->assertOk();
        $this->assertCount(10, $r->json('data'));
        $this->assertEquals(25, $r->json('total'));
        $this->assertEquals(3, $r->json('last_page'));
        $this->assertEquals(1, $r->json('current_page'));

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson('/api/pets?per_page=10&page=2');
        $r->assertOk();
        $this->assertCount(10, $r->json('data'));
        $this->assertEquals(2, $r->json('current_page'));
    }
}