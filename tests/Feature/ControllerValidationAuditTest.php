<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControllerValidationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $customer;
    protected Role $superAdminRole;
    protected Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'sa@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->superAdminRole->id,
            'status' => 'active',
        ]);

        $this->customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer_val@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
            'status' => 'active',
        ]);
    }

    /** 1. Test CustomerAuthApiController updateProfile validates input */
    public function test_1_customer_update_profile_validation(): void
    {
        $tokenData = \App\Models\PersonalAccessToken::generateToken($this->customer, 'test-token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenData['plainTextToken'])
            ->putJson('/api/v1/me', [
                'name' => str_repeat('a', 300), // Exceeds 255 max limit
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /** 2. Test AdminUserController store validates mandatory fields */
    public function test_2_admin_user_controller_store_validation(): void
    {
        $response = $this->actingAs($this->superAdmin, 'web')
            ->post('/admin/users', [
                'name' => 'Incomplete Admin',
                // Missing email, password, role_id
            ]);

        $response->assertSessionHasErrors(['email', 'role_id', 'status', 'password']);
    }

    /** 3. Test CategoryController store validates mandatory name */
    public function test_3_category_controller_store_validation(): void
    {
        $perm = \App\Models\Permission::firstOrCreate(['name' => 'categories.view'], ['guard_name' => 'web']);
        $this->superAdminRole->permissions()->syncWithoutDetaching([$perm->id]);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->post('/admin/categories', [
                'status' => 'active',
                // Missing name
            ]);

        $response->assertSessionHasErrors(['name']);
    }
}
