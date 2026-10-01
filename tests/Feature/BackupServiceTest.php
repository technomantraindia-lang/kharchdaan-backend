<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $role = Role::where('name', 'Super Admin')->first();
        $this->superAdmin = User::create([
            'name' => 'Super Admin Backup',
            'email' => 'backupadmin@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_create_and_list_backups(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.system.backups.create'));
        $response->assertRedirect();

        $listResponse = $this->actingAs($this->superAdmin)->get(route('admin.system.backups.index'));
        $listResponse->assertStatus(200);
    }
}
