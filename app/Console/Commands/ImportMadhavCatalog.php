<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportMadhavCatalog extends Command
{
    protected $signature = 'catalog:import-madhav {source : Path to the madhvafood source folder}';

    protected $description = 'Import the Madhav catalog and product/category images';

    public function handle(): int
    {
        $source = realpath((string) $this->argument('source'));
        $assetRoot = $source ? $source . DIRECTORY_SEPARATOR . 'madhav-web' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' : null;

        if (! $source || ! is_dir($assetRoot . DIRECTORY_SEPARATOR . 'products')) {
            $this->error('Could not find madhav-web/public/assets/products in the supplied source folder.');

            return self::FAILURE;
        }

        $categories = [
            'tea' => ['name' => 'Tea', 'parent' => null, 'image' => 'categories/tea.png'],
            'spices' => ['name' => 'Spices', 'parent' => null, 'image' => 'categories/spices.png'],
            'papad' => ['name' => 'Papad', 'parent' => null, 'image' => 'categories/papad.png'],
            'basic-spices' => ['name' => 'Basic Spices', 'parent' => 'spices'],
            'blended-spices' => ['name' => 'Blended Spices', 'parent' => 'spices'],
            'special-spices' => ['name' => 'Special Spices', 'parent' => 'spices'],
            'whole-spices' => ['name' => 'Whole Spices', 'parent' => 'spices'],
        ];

        $products = [
            ['sku' => 'MF-RED-CHILLI', 'name' => 'Red Chilli Powder', 'category' => 'basic-spices', 'price' => 85, 'image' => 'products/red-chilli-powder.jpeg', 'gallery' => ['products/transparent/red-chilli-powder-back.png', 'products/red-chilli-powder-2.jpeg']],
            ['sku' => 'MF-TURMERIC', 'name' => 'Turmeric Powder', 'category' => 'basic-spices', 'price' => 88, 'image' => 'products/turmeric-powder.jpeg', 'gallery' => ['products/transparent/turmeric-powder-back.png', 'products/turmeric-powder-1.jpeg']],
            ['sku' => 'MF-CORIANDER-CUMIN', 'name' => 'Coriander Cumin Powder', 'category' => 'basic-spices', 'price' => 65, 'image' => 'products/coriander-cumin-powder.jpeg', 'gallery' => ['products/transparent/coriander-cumin-powder-back.png']],
            ['sku' => 'MF-ACHAR-MASALA', 'name' => 'Achar Masala', 'category' => 'blended-spices', 'price' => 0, 'image' => 'products/achar-masala.jpeg', 'gallery' => ['products/achar-masala-2.jpeg', 'products/achar-masala-3.jpeg']],
            ['sku' => 'MF-GARAM-MASALA', 'name' => 'Garam Masala', 'category' => 'blended-spices', 'price' => 45, 'image' => 'products/garam-masala.jpeg', 'gallery' => ['products/transparent/garam-masala-back.png']],
            ['sku' => 'MF-GOL-KERI', 'name' => 'Gol Keri Achar Masala', 'category' => 'special-spices', 'price' => 0, 'image' => 'products/gol-keri-achar-masala.jpeg', 'gallery' => []],
            ['sku' => 'MF-CUMIN-SEEDS', 'name' => 'Cumin Seeds', 'category' => 'whole-spices', 'price' => 0, 'image' => 'products/coriander-cumin-powder.jpeg', 'gallery' => []],
            ['sku' => 'MF-MUSTARD-SEEDS', 'name' => 'Mustard Seeds', 'category' => 'whole-spices', 'price' => 0, 'image' => 'products/turmeric-powder.jpeg', 'gallery' => []],
            ['sku' => 'MF-BLACK-PEPPER', 'name' => 'Black Pepper', 'category' => 'whole-spices', 'price' => 0, 'image' => 'products/red-chilli-powder.jpeg', 'gallery' => []],
            ['sku' => 'MF-TASTY-CUP-TEA', 'name' => 'Tasty Cup Tea', 'category' => 'tea', 'price' => 0, 'image' => 'products/tea-premium-blend.jpeg', 'gallery' => ['products/transparent/tea-premium-blend-back.png']],
            ['sku' => 'MF-HARUN-GOLD-TEA', 'name' => 'Harun Gold Tea', 'category' => 'tea', 'price' => 0, 'image' => 'products/tea-premium-blend.jpeg', 'gallery' => ['products/transparent/tea-premium-blend-back.png']],
            ['sku' => 'MF-SPECIAL-HARUN-TEA', 'name' => 'Special Harun Tea', 'category' => 'tea', 'price' => 0, 'image' => 'products/tea-premium-blend.jpeg', 'gallery' => ['products/transparent/tea-premium-blend-back.png']],
            ['sku' => 'MF-DOLY-TEA', 'name' => 'Doly Tea', 'category' => 'tea', 'price' => 0, 'image' => 'products/tea-premium-blend.jpeg', 'gallery' => ['products/transparent/tea-premium-blend-back.png']],
            ['sku' => 'MF-MOONG-PAPAD', 'name' => 'Moong Papad', 'category' => 'papad', 'price' => 65, 'image' => 'products/moong-papad.jpeg', 'gallery' => ['products/transparent/moong-papad-back.png']],
        ];

        $copyAsset = function (string $relativePath) use ($assetRoot): ?string {
            $sourcePath = $assetRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (! File::isFile($sourcePath)) {
                return null;
            }

            $destination = 'catalog/' . $relativePath;
            Storage::disk('public')->put($destination, File::get($sourcePath));

            return $destination;
        };

        $imported = 0;
        $images = 0;

        DB::transaction(function () use ($categories, $products, $copyAsset, &$imported, &$images): void {
            $categoryModels = [];

            foreach ($categories as $slug => $data) {
                if ($data['parent']) {
                    continue;
                }

                $categoryModels[$slug] = Category::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $data['name'],
                        'image' => $data['image'] ? $copyAsset($data['image']) : null,
                        'description' => $data['name'] . ' from Madhav Foods Products.',
                        'status' => 'active',
                    ]
                );
            }

            foreach ($categories as $slug => $data) {
                if (! $data['parent']) {
                    continue;
                }

                $categoryModels[$slug] = Category::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $data['name'],
                        'parent_id' => $categoryModels[$data['parent']]->id,
                        'description' => $data['name'] . ' from Madhav Foods Products.',
                        'status' => 'active',
                    ]
                );
            }

            foreach ($products as $data) {
                $primaryImage = $copyAsset($data['image']);
                $product = Product::updateOrCreate(
                    ['sku' => $data['sku']],
                    [
                        'name' => $data['name'],
                        'slug' => Str::slug($data['name']),
                        'category_id' => $categoryModels[$data['category']]->id,
                        'price' => $data['price'],
                        'stock_qty' => 0,
                        'low_stock_qty' => 5,
                        'unit' => 'packet',
                        'min_order_qty' => 1,
                        'image' => $primaryImage,
                        'short_desc' => $data['name'] . ' from Madhav Foods Products.',
                        'description' => 'Quality ' . strtolower($data['name']) . ' packed by Madhav Foods Products.',
                        'status' => 'active',
                        'featured' => false,
                    ]
                );

                if ($primaryImage) {
                    $images++;
                }

                foreach ($data['gallery'] as $sort => $galleryImage) {
                    $galleryPath = $copyAsset($galleryImage);
                    if (! $galleryPath) {
                        continue;
                    }

                    $product->images()->updateOrCreate(
                        ['image' => $galleryPath],
                        ['sort_order' => $sort + 1]
                    );
                    $images++;
                }

                $imported++;
            }
        });

        $this->info("Imported {$imported} products and {$images} images.");
        $this->info('Categories and product media are stored in the public storage disk.');

        return self::SUCCESS;
    }
}
