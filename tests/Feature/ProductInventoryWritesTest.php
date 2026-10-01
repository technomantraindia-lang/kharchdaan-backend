<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductInventoryWritesTest extends TestCase
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
            'email' => 'admininv@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    public function test_product_creation_records_opening_stock_inventory_transaction(): void
    {
        $category = Category::first();

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Inventory Product',
            'sku' => 'SKU-INV-001',
            'category_id' => $category->id,
            'price' => 150,
            'stock_qty' => 25,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('sku', 'SKU-INV-001')->firstOrFail();
        $this->assertEquals(25, $product->stock_qty);

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'new_stock' => 25,
            'reason' => 'opening_stock',
        ]);
    }

    public function test_bulk_product_creation_records_inventory_transactions(): void
    {
        $category = Category::first();

        $response = $this->actingAs($this->admin)->post(route('admin.products.bulkStore'), [
            'products' => [
                [
                    'name' => 'Bulk Product 1',
                    'sku' => 'BULK-001',
                    'category_id' => $category->id,
                    'price' => 100,
                    'stock_qty' => 10,
                    'status' => 'active',
                ],
                [
                    'name' => 'Bulk Product 2',
                    'sku' => 'BULK-002',
                    'category_id' => $category->id,
                    'price' => 200,
                    'stock_qty' => 15,
                    'status' => 'active',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $p1 = Product::where('sku', 'BULK-001')->firstOrFail();
        $p2 = Product::where('sku', 'BULK-002')->firstOrFail();

        $this->assertDatabaseHas('inventory_transactions', ['product_id' => $p1->id, 'new_stock' => 10]);
        $this->assertDatabaseHas('inventory_transactions', ['product_id' => $p2->id, 'new_stock' => 15]);
    }

    public function test_csv_import_creates_inventory_transactions(): void
    {
        $category = Category::first();

        $csvHeader = "name,sku,category,price,stock_qty,unit,status\n";
        $csvRow = "CSV Product,CSV-001,{$category->name},100,30,pcs,active\n";

        $file = UploadedFile::fake()->createWithContent('products.csv', $csvHeader . $csvRow);

        $response = $this->actingAs($this->admin)->post(route('admin.products.importStore'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('sku', 'CSV-001')->firstOrFail();
        $this->assertEquals(30, $product->stock_qty);

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'new_stock' => 30,
        ]);
    }
}
