<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('integrity-test')->plainTextToken];
    }

    public function test_admin_must_assign_new_pet_to_a_pet_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->withHeaders($this->token($admin))->postJson('/api/pets', [
            'name' => 'Milo', 'species' => 'Cat',
        ]);
        $response->assertUnprocessable();
    }

    public function test_admin_cannot_create_appointment_with_owner_mismatched_from_pet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $otherOwner = User::factory()->create(['role' => 'pet_owner']);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create(['is_available' => true]);

        $response = $this->withHeaders($this->token($admin))->postJson('/api/appointments', [
            'user_id' => $otherOwner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);
        $response->assertUnprocessable();
    }

    public function test_owner_cannot_reschedule_own_appointment_through_update_endpoint(): void
    {
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $service = Service::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(2),
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->token($owner))->putJson('/api/appointments/' . $appointment->id, [
            'appointment_date' => now()->addDays(4)->format('Y-m-d H:i:s'),
            'status' => 'pending',
        ]);
        $response->assertUnprocessable();
    }

    public function test_pet_with_medical_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'pet_owner']);
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        MedicalRecord::factory()->create(['pet_id' => $pet->id, 'appointment_id' => null]);

        $response = $this->withHeaders($this->token($admin))->deleteJson('/api/pets/' . $pet->id);
        $response->assertUnprocessable();
        $this->assertDatabaseHas('pets', ['id' => $pet->id]);
    }
}
