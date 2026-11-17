<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSelfDeactivationGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_regular_admin_cannot_deactivate_own_account(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $permission = Permission::firstOrCreate(['name' => 'users.manage'], ['guard_name' => 'web']);
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);

        $admin = User::create([
            'name' => 'Self Admin',
            'email' => 'selfadmin@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->from(admin_route('users.index'))
            ->patch(admin_route('users.toggleStatus', $admin));

        $response->assertSessionHas('error');
        $admin->refresh();
        $this->assertEquals('active', $admin->status);
    }
}
