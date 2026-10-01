<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPortalSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $subAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::where('name', 'Super Administrator')->first() ?? Role::create([
            'name' => 'Super Administrator',
            'guard_name' => 'web',
        ]);

        $subAdminRole = Role::where('name', 'Sub Admin')->first() ?? Role::create([
            'name' => 'Sub Admin',
            'guard_name' => 'web',
        ]);

        $dashPerm = Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin Master',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'plain_password' => 'password',
            'staff_code' => 'ADM-001',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $this->subAdmin = User::create([
            'name' => 'Store Manager',
            'email' => 'subadmin@example.com',
            'password' => bcrypt('password'),
            'plain_password' => 'password',
            'staff_code' => 'SUB-1001',
            'role_id' => $subAdminRole->id,
            'status' => 'active',
        ]);

        $this->subAdmin->permissions()->attach($dashPerm->id);
    }

    public function test_guest_is_redirected_to_specific_login_urls(): void
    {
        $response = $this->get('/super-admin/dashboard');
        $response->assertRedirect('/super-admin/login');

        $response = $this->get('/sub-admin/dashboard');
        $response->assertRedirect('/sub-admin/login');

        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_super_admin_is_redirected_to_super_admin_urls(): void
    {
        // 1. Super admin accessing /admin/dashboard -> redirects to /super-admin/dashboard
        $response = $this->actingAs($this->superAdmin)->get('/admin/dashboard');
        $response->assertRedirect('/super-admin/dashboard');

        // 2. Super admin accessing /sub-admin/dashboard -> redirects to /super-admin/dashboard
        $response = $this->actingAs($this->superAdmin)->get('/sub-admin/dashboard');
        $response->assertRedirect('/super-admin/dashboard');

        // 3. Super admin accessing /super-admin/dashboard -> 200 OK
        $response = $this->actingAs($this->superAdmin)->get('/super-admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('SUPER ADMIN MASTER');
    }

    public function test_sub_admin_is_redirected_to_sub_admin_urls(): void
    {
        // 1. Sub-admin accessing /admin/dashboard -> redirects to /sub-admin/dashboard
        $response = $this->actingAs($this->subAdmin)->get('/admin/dashboard');
        $response->assertRedirect('/sub-admin/dashboard');

        // 2. Sub-admin attempting to access /super-admin/dashboard -> redirected to /sub-admin/dashboard
        $response = $this->actingAs($this->subAdmin)->get('/super-admin/dashboard');
        $response->assertRedirect('/sub-admin/dashboard');

        // 3. Sub-admin accessing /sub-admin/dashboard -> 200 OK
        $response = $this->actingAs($this->subAdmin)->get('/sub-admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('SUB-ADMIN STAFF');
    }
}
