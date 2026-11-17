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
            'terms_accepted_at' => now(),
        ]);

        $response = $this->post('/login', [
            'login' => 'webcust@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_cannot_login_to_customer_web_area(): void
    {
        $role = Role::where('name', 'Admin')->first();
        User::create([
            'name' => 'Admin User',
            'email' => 'adminweb@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'adminweb@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_super_admin_cannot_login_to_customer_web_area(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        User::create([
            'name' => 'Super Admin',
            'email' => 'superweb@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'superweb@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_or_suspended_customer_cannot_login(): void
    {
        $role = Role::where('name', 'Customer')->first();
        User::create([
            'name' => 'Inactive Cust',
            'email' => 'inactivecust@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
            'status' => 'inactive',
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'inactivecust@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }
}
