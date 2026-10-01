<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAuthAndIdorAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerA;
    protected User $customerB;
    protected Role $customerRole;
    protected Order $orderB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);

        $this->customerA = User::create([
            'name' => 'Customer A',
            'email' => 'customera@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
            'status' => 'active',
        ]);

        $this->customerB = User::create([
            'name' => 'Customer B',
            'email' => 'customerb@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
            'status' => 'active',
        ]);

        $this->orderB = Order::create([
            'order_num' => 'ORD-CUST-B',
            'user_id' => $this->customerB->id,
            'subtotal' => 500.00,
            'total' => 500.00,
            'status' => 'pending',
            'pay_status' => 'unpaid',
        ]);
    }

    /** 1. Test Customer cannot access any admin panel routes */
    public function test_1_customer_cannot_access_admin_panel_routes(): void
    {
        $adminRoutes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->uri(), 'admin/') && ! str_contains($route->uri(), 'login');
        });

        $this->assertNotEmpty($adminRoutes, 'Admin routes should be registered.');

        foreach ($adminRoutes as $route) {
            $methods = array_diff($route->methods(), ['HEAD']);
            $method = reset($methods);
            $uri = '/' . ltrim($route->uri(), '/');

            // Replace parameters with 1
            $testUrl = preg_replace('/\{[a-zA-Z_]+\}/', '1', $uri);

            $response = $this->actingAs($this->customerA, 'web')
                ->json($method, $testUrl);

            $this->assertContains(
                $response->status(),
                [401, 403, 302, 404, 405],
                "Customer was able to bypass admin auth on {$method} {$testUrl} (Status: {$response->status()})"
            );
        }
    }

    /** 2. Test IDOR protection: Customer A cannot view Customer B's order */
    public function test_2_customer_cannot_view_another_customers_order_idor(): void
    {
        $tokenData = \App\Models\PersonalAccessToken::generateToken($this->customerA, 'test-token');

        // Customer A attempts to view Customer B's order ID
        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenData['plainTextToken'])
            ->getJson('/api/v1/orders/' . $this->orderB->id);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
