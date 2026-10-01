<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerApiLoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_customer_token_issued_for_valid_active_customer(): void
    {
        $role = Role::where('name', 'Customer')->first();
        $user = User::create([
            'name' => 'API Customer',
            'email' => 'apicust@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'apicust@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_admin_cannot_obtain_customer_api_token(): void
    {
        $role = Role::where('name', 'Admin')->first();
        $user = User::create([
            'name' => 'API Admin',
            'email' => 'apiadmin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'apiadmin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_cannot_obtain_customer_api_token(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::create([
            'name' => 'API Super Admin',
            'email' => 'apisuper@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'apisuper@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_inactive_or_suspended_customer_api_login_rejected(): void
    {
        $role = Role::where('name', 'Customer')->first();
        $user = User::create([
            'name' => 'Suspended Customer',
            'email' => 'suspendedapi@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'status' => 'suspended',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'suspendedapi@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
