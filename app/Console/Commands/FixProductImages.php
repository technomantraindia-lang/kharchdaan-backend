<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class FixProductImages extends Command
{
    protected $signature = 'products:fix-images';
    protected $description = 'Ensure all products in the database have valid high quality images assigned';

    public function handle(): int
    {
        $this->info('Checking and fixing product images in database...');

        $sampleImages = [
            'masoor' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600&auto=format&fit=crop',
            'rice'   => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600&auto=format&fit=crop',
            'tea'    => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&auto=format&fit=crop',
            'dal'    => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=600&auto=format&fit=crop',
            'rajma'  => 'https://images.unsplash.com/photo-1515543237350-b3eea1ec8082?w=600&auto=format&fit=crop',
            'default'=> 'https://images.unsplash.com/photo-1610832958506-aa56368176cf?w=600&auto=format&fit=crop',
        ];

        $products = Product::all();
        $updated = 0;

        foreach ($products as $product) {
            if (empty($product->image)) {
                $nameLower = strtolower($product->name);
                $imageUrl = $sampleImages['default'];

                if (str_contains($nameLower, 'rice')) {
                    $imageUrl = $sampleImages['rice'];
                } elseif (str_contains($nameLower, 'tea')) {
                    $imageUrl = $sampleImages['tea'];
                } elseif (str_contains($nameLower, 'masoor')) {
                    $imageUrl = $sampleImages['masoor'];
                } elseif (str_contains($nameLower, 'rajma')) {
                    $imageUrl = $sampleImages['rajma'];
                } elseif (str_contains($nameLower, 'dal') || str_contains($nameLower, 'panchra') || str_contains($nameLower, 'gram') || str_contains($nameLower, 'split')) {
                    $imageUrl = $sampleImages['dal'];
                }

                $product->update(['image' => $imageUrl]);
                $updated++;
                $this->line("Assigned image for: {$product->name}");
            }
        }

        $this->info("Successfully updated {$updated} product(s) with valid image URLs.");
        return self::SUCCESS;
    }
}
