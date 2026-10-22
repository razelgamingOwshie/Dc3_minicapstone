<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Pet, Service, Appointment, MedicalRecord, Vaccination};
use Carbon\Carbon;

class CRUDTest extends TestCase
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

    public function test_pet_crud(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->postJson('/api/pets', [
            'user_id' => $owner->id,
            'name' => 'Buddy',
            'species' => 'Dog',
            'breed' => 'Golden Retriever',
        ]);
        $r->assertCreated();
        $petId = $r->json('data.id');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson("/api/pets/{$petId}");
        $r->assertOk()->assertJsonPath('data.name', 'Buddy');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->putJson("/api/pets/{$petId}", [
            'name' => 'Buddy Updated',
        ]);
        $r->assertOk()->assertJsonPath('data.name', 'Buddy Updated');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->deleteJson("/api/pets/{$petId}");
        $r->assertOk();

        $this->assertDatabaseMissing('pets', ['id' => $petId]);
    }

    public function test_service_crud(): void
    {
        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->postJson('/api/services', [
            'name' => 'Wellness Exam',
            'description' => 'Routine checkup',
            'price' => 500,
            'duration_minutes' => 30,
            'is_available' => true,
        ]);
        $r->assertCreated();
        $serviceId = $r->json('data.id');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson("/api/services/{$serviceId}");
        $r->assertOk();

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->putJson("/api/services/{$serviceId}", [
            'price' => 550,
        ]);
        $r->assertOk()->assertJsonPath('data.price', '550.00');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->deleteJson("/api/services/{$serviceId}");
        $r->assertOk();

        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
    }

    public function test_appointment_crud(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create(['is_available' => true]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->postJson('/api/appointments', [
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'reason' => 'Checkup',
        ]);
        $r->assertCreated();
        $appointmentId = $r->json('data.id');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson("/api/appointments/{$appointmentId}");
        $r->assertOk();

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->putJson("/api/appointments/{$appointmentId}", [
            'reason' => 'Updated reason',
        ]);
        $r->assertOk()->assertJsonPath('data.reason', 'Updated reason');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->deleteJson("/api/appointments/{$appointmentId}");
        $r->assertOk();

        $this->assertDatabaseMissing('appointments', ['id' => $appointmentId]);
    }

    public function test_medical_record_crud(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'status' => 'completed',
        ]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->postJson('/api/medical-records', [
            'pet_id' => $pet->id,
            'appointment_id' => $appointment->id,
            'diagnosis' => 'Healthy',
            'treatment' => 'None needed',
            'record_date' => today()->format('Y-m-d'),
        ]);
        $r->assertCreated();
        $recordId = $r->json('data.id');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson("/api/medical-records/{$recordId}");
        $r->assertOk();

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->putJson("/api/medical-records/{$recordId}", [
            'treatment' => 'Vitamins prescribed',
        ]);
        $r->assertOk()->assertJsonPath('data.treatment', 'Vitamins prescribed');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->deleteJson("/api/medical-records/{$recordId}");
        $r->assertOk();

        $this->assertDatabaseMissing('medical_records', ['id' => $recordId]);
    }

    public function test_vaccination_crud(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->postJson('/api/vaccinations', [
            'pet_id' => $pet->id,
            'vaccine_name' => 'Rabies',
            'date_administered' => today()->format('Y-m-d'),
            'next_due_date' => today()->addYear()->format('Y-m-d'),
        ]);
        $r->assertCreated();
        $vaccinationId = $r->json('data.id');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->getJson("/api/vaccinations/{$vaccinationId}");
        $r->assertOk();

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->putJson("/api/vaccinations/{$vaccinationId}", [
            'notes' => 'Administered without issues',
        ]);
        $r->assertOk()->assertJsonPath('data.notes', 'Administered without issues');

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->deleteJson("/api/vaccinations/{$vaccinationId}");
        $r->assertOk();

        $this->assertDatabaseMissing('vaccinations', ['id' => $vaccinationId]);
    }

    public function test_owner_can_view_own_pets_medical_records(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $ownerToken = $owner->createToken('test')->plainTextToken;
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'status' => 'completed',
        ]);
        $record = MedicalRecord::factory()->create([
            'pet_id' => $pet->id,
            'appointment_id' => $appointment->id,
        ]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$ownerToken}"])->getJson('/api/medical-records');
        $r->assertOk();
        $this->assertCount(1, $r->json('data'));
    }

    public function test_owner_can_view_own_pets_vaccinations(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $ownerToken = $owner->createToken('test')->plainTextToken;
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $vaccination = Vaccination::factory()->create(['pet_id' => $pet->id]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$ownerToken}"])->getJson('/api/vaccinations');
        $r->assertOk();
        $this->assertCount(1, $r->json('data'));
    }
}