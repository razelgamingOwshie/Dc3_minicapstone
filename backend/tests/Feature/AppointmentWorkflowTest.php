<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Pet, Service, Appointment, MedicalRecord};
use Carbon\Carbon;

class AppointmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $owner;
    protected string $adminToken;
    protected string $ownerToken;
    protected Pet $pet;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['email' => 'admin@test', 'role' => 'admin']);
        $this->owner = User::factory()->create(['email' => 'owner@test', 'role' => 'pet_owner']);
        $this->adminToken = $this->admin->createToken('test')->plainTextToken;
        $this->ownerToken = $this->owner->createToken('test')->plainTextToken;
        $this->pet = Pet::factory()->create(['user_id' => $this->owner->id]);
        $this->service = Service::factory()->create(['is_available' => true, 'duration_minutes' => 30]);
    }

    public function test_new_appointment_starts_as_pending(): void
    {
        $r = $this->withHeaders(['Authorization' => "Bearer {$this->ownerToken}"])->postJson('/api/appointments', [
            'pet_id' => $this->pet->id,
            'service_id' => $this->service->id,
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'reason' => 'Checkup',
        ]);
        $r->assertCreated()->assertJsonPath('data.status', 'pending');
    }

    public function test_booking_conflict_rejected(): void
    {
        $time = Carbon::tomorrow()->setTime(10, 0);
        Appointment::factory()->create([
            'user_id' => $this->owner->id,
            'pet_id' => $this->pet->id,
            'service_id' => $this->service->id,
            'appointment_date' => $time,
            'status' => 'approved',
        ]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->ownerToken}"])->postJson('/api/appointments', [
            'pet_id' => $this->pet->id,
            'service_id' => $this->service->id,
            'appointment_date' => $time->copy()->addMinutes(15)->format('Y-m-d H:i:s'),
            'reason' => 'Another checkup',
        ]);
        $r->assertUnprocessable();
    }

    public function test_unavailable_service_cannot_be_booked(): void
    {
        $unavailableService = Service::factory()->create(['is_available' => false]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->ownerToken}"])->postJson('/api/appointments', [
            'pet_id' => $this->pet->id,
            'service_id' => $unavailableService->id,
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'reason' => 'Checkup',
        ]);
        $r->assertUnprocessable();
    }

    public function test_appointment_date_validation(): void
    {
        $r = $this->withHeaders(['Authorization' => "Bearer {$this->ownerToken}"])->postJson('/api/appointments', [
            'pet_id' => $this->pet->id,
            'service_id' => $this->service->id,
            'appointment_date' => Carbon::yesterday()->format('Y-m-d H:i:s'),
            'reason' => 'Checkup',
        ]);
        $r->assertUnprocessable();
    }

    public function test_medical_record_linked_to_correct_pet_and_appointment(): void
    {
        $service = Service::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $this->owner->id,
            'pet_id' => $this->pet->id,
            'service_id' => $service->id,
            'status' => 'completed',
        ]);

        $r = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])->postJson('/api/medical-records', [
            'pet_id' => $this->pet->id,
            'appointment_id' => $appointment->id,
            'diagnosis' => 'Healthy',
            'record_date' => today()->format('Y-m-d'),
        ]);
        $r->assertCreated();
        $this->assertEquals($this->pet->id, $r->json('data.pet_id'));
        $this->assertEquals($appointment->id, $r->json('data.appointment_id'));
    }
}