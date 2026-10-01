<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_login_is_throttled_after_5_failed_attempts(): void
    {
        $role = Role::where('name', 'Admin')->first();
        User::create([
            'name' => 'Target Admin',
            'email' => 'targetadmin@example.com',
            'password' => Hash::make('correct_password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/admin/login', [
                'email' => 'targetadmin@example.com',
                'password' => 'wrong_password',
            ]);
            $response->assertSessionHas('error', 'Invalid credentials');
        }

        // 6th attempt should return throttle error message
        $response = $this->post('/admin/login', [
            'email' => 'targetadmin@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Too many login attempts', session('error'));
    }
}
