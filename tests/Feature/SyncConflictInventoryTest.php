<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\WooCommerceSyncConflict;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncConflictInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $role = Role::where('name', 'Super Admin')->first();
        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'adminconflict@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    public function test_sync_conflict_resolution_uses_inventory_service(): void
    {
        $category = Category::first();
        $product = Product::create([
            'name' => 'Conflict Product',
            'slug' => 'conflict-product',
            'sku' => 'CONF-001',
            'category_id' => $category->id,
            'price' => 100,
            'stock_qty' => 10,
            'status' => 'active',
        ]);

        $conflict = WooCommerceSyncConflict::create([
            'entity_type' => 'inventory',
            'entity_id' => $product->id,
            'woocommerce_id' => 123,
            'field_name' => 'stock_qty',
            'laravel_value' => '10',
            'woocommerce_value' => '50',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.woocommerce.conflicts.resolve', $conflict), [
            'resolution' => 'use_woocommerce',
        ]);

        $response->assertRedirect();

        $product->refresh();
        $this->assertEquals(50, $product->stock_qty);

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'previous_stock' => 10,
            'new_stock' => 50,
            'reference_type' => WooCommerceSyncConflict::class,
            'reference_id' => $conflict->id,
        ]);
    }
}
