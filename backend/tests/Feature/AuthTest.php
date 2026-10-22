<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Pet, Service, Appointment};
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_always_creates_pet_owner(): void
    {
        $r = $this->postJson('/api/register', [
            'name' => 'Test Owner',
            'email' => 'owner@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ]);
        $r->assertCreated()->assertJsonPath('user.role', 'pet_owner');
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicate@example.test']);

        $r = $this->postJson('/api/register', [
            'name' => 'Another Owner',
            'email' => 'duplicate@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $r->assertUnprocessable();
    }

    public function test_registration_rejects_invalid_data(): void
    {
        $r = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);
        $r->assertUnprocessable();
    }

    public function test_login_accepts_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.test',
            'password' => Hash::make('Password123!'),
        ]);

        $r = $this->postJson('/api/login', [
            'email' => 'test@example.test',
            'password' => 'Password123!',
        ]);
        $r->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $r = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.test',
            'password' => 'WrongPassword123!',
        ]);
        $r->assertUnprocessable();
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $r = $this->withHeaders(['Authorization' => "Bearer $token"])->postJson('/api/logout');
        $r->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['token' => hash('sha256', $token)]);
    }

    public function test_protected_endpoints_reject_guests(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/pets')->assertUnauthorized();
        $this->postJson('/api/pets', [])->assertUnauthorized();
    }

    public function test_current_user_endpoint(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $r = $this->withHeaders(['Authorization' => "Bearer $token"])->getJson('/api/user');
        $r->assertOk()->assertJsonPath('user.id', $user->id);
    }
}