<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Pet, Service, Appointment};
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Sanctum::actingAs() sets a TransientToken on the user, bypassing
        // bearer-token resolution. This avoids a known SQLite test environment
        // issue where session-based guard resolution can return the wrong user
        // when multiple users have tokens in the same test.
    }

    protected function createOwner(): User
    {
        return User::factory()->create(['role' => 'pet_owner', 'email' => 'owner-' . uniqid() . '@test']);
    }

    protected function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin-' . uniqid() . '@test']);
    }

    public function test_owner_cannot_access_another_owners_pets(): void
    {
        $owner = $this->createOwner();
        Sanctum::actingAs($owner);
        $otherOwner = $this->createOwner();
        $pet = Pet::factory()->create(['user_id' => $otherOwner->id]);

        $r = $this->getJson("/api/pets/{$pet->id}");
        $r->assertNotFound();
    }

    public function test_owner_cannot_access_another_owners_appointments(): void
    {
        $owner = $this->createOwner();
        Sanctum::actingAs($owner);
        $otherOwner = $this->createOwner();
        $pet = Pet::factory()->create(['user_id' => $otherOwner->id]);
        $service = Service::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $otherOwner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
        ]);

        $r = $this->getJson("/api/appointments/{$appointment->id}");
        $r->assertNotFound();
    }

    public function test_owner_cannot_access_admin_dashboard(): void
    {
        $owner = $this->createOwner();
        Sanctum::actingAs($owner);

        $r = $this->getJson('/api/dashboard');
        $r->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $r = $this->getJson('/api/dashboard');
        $r->assertOk()->assertJsonStructure([
            'total_pets',
            'total_pet_owners',
            'active_services',
            'appointments_today',
            'pending_appointments',
            'completed_consultations',
            'upcoming_vaccinations',
        ]);
    }

    public function test_admin_endpoints_enforce_admin_permissions(): void
    {
        $owner = $this->createOwner();
        $admin = $this->createAdmin();
        $service = Service::factory()->create();
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($owner);

        $r = $this->postJson('/api/services', [
            'name' => 'Unauthorized Service',
            'description' => 'Should be blocked',
            'price' => 100,
            'duration_minutes' => 30,
            'is_available' => true,
        ]);
        $r->assertForbidden();

        $r = $this->postJson('/api/medical-records', [
            'pet_id' => $pet->id,
            'diagnosis' => 'Should be blocked',
            'record_date' => today()->format('Y-m-d'),
        ]);
        $r->assertForbidden();

        $r = $this->postJson('/api/vaccinations', [
            'pet_id' => $pet->id,
            'vaccine_name' => 'Should be blocked',
        ]);
        $r->assertForbidden();

        $r = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'approved',
        ]);
        $r->assertForbidden();

        Sanctum::actingAs($admin);
        $r = $this->postJson('/api/services', [
            'name' => 'Admin Service',
            'description' => 'Allowed',
            'price' => 200,
            'duration_minutes' => 45,
            'is_available' => true,
        ]);
        $r->assertCreated();
    }

    public function test_owner_can_create_pet(): void
    {
        $owner = $this->createOwner();
        Sanctum::actingAs($owner);

        $r = $this->postJson('/api/pets', [
            'name' => 'Buddy',
            'species' => 'Dog',
            'breed' => 'Golden Retriever',
            'sex' => 'Male',
            'birth_date' => '2020-01-01',
            'weight' => 25.5,
        ]);
        $r->assertCreated()->assertJsonPath('data.user_id', $owner->id);
    }

    public function test_owner_can_create_appointment(): void
    {
        $owner = $this->createOwner();
        Sanctum::actingAs($owner);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create(['is_available' => true]);

        $r = $this->postJson('/api/appointments', [
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'reason' => 'Checkup',
        ]);
        $r->assertCreated()->assertJsonPath('data.status', 'pending');
    }

    public function test_owner_cannot_create_appointment_for_other_pet(): void
    {
        $owner = $this->createOwner();
        Sanctum::actingAs($owner);
        $otherOwner = $this->createOwner();
        $pet = Pet::factory()->create(['user_id' => $otherOwner->id]);
        $service = Service::factory()->create(['is_available' => true]);

        $r = $this->postJson('/api/appointments', [
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'reason' => 'Checkup',
        ]);
        $r->assertForbidden();
    }

    public function test_owner_can_cancel_own_pending_appointment(): void
    {
        $owner = $this->createOwner();
        Sanctum::actingAs($owner);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create(['is_available' => true]);
        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'status' => 'pending',
        ]);

        $r = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'cancelled',
        ]);
        $r->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_owner_cannot_cancel_approved_appointment(): void
    {
        $owner = $this->createOwner();
        Sanctum::actingAs($owner);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create(['is_available' => true]);
        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'status' => 'approved',
        ]);

        $r = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'cancelled',
        ]);
        $r->assertForbidden();
    }

    public function test_admin_can_approve_appointment(): void
    {
        $owner = $this->createOwner();
        $admin = $this->createAdmin();
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create(['is_available' => true]);
        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);
        $r = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'approved',
        ]);
        $r->assertOk()->assertJsonPath('data.status', 'approved');
    }

    public function test_admin_can_complete_appointment(): void
    {
        $owner = $this->createOwner();
        $admin = $this->createAdmin();
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create(['is_available' => true]);
        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'status' => 'approved',
        ]);

        Sanctum::actingAs($admin);
        $r = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'completed',
        ]);
        $r->assertOk()->assertJsonPath('data.status', 'completed');
    }

    public function test_invalid_status_transition_rejected(): void
    {
        $owner = $this->createOwner();
        $admin = $this->createAdmin();
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create(['is_available' => true]);
        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'status' => 'completed',
        ]);

        Sanctum::actingAs($admin);
        $r = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'pending',
        ]);
        $r->assertUnprocessable();
    }
}