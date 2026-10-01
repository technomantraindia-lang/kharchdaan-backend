<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerWebLoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_active_customer_can_login_to_web(): void
    {
        $role = Role::where('name', 'Customer')->first();
        $user = User::create([
            'name' => 'Web Customer',
            'email' => 'webcust@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'webcust@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_cannot_login_to_customer_web_area(): void
    {
        $role = Role::where('name', 'Admin')->first();
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'adminweb@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'adminweb@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_cannot_login_to_customer_web_area(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'superweb@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'superweb@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_or_suspended_customer_cannot_login(): void
    {
        $role = Role::where('name', 'Customer')->first();
        $inactive = User::create([
            'name' => 'Inactive Cust',
            'email' => 'inactivecust@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'inactivecust@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
