<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductAttribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FmcgProductsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categories
        $categoriesData = [
            [
                'name' => 'Daily Needs',
                'slug' => 'daily-needs',
                'description' => 'Grocery & Staples - Atta, Rice, Oils, Dals, and Everyday Staples'
            ],
            [
                'name' => 'Food',
                'slug' => 'food',
                'description' => 'Food & Beverages - Tea, Coffee, Biscuits, Chocolates, and Snacks'
            ],
            [
                'name' => 'Home',
                'slug' => 'home',
                'description' => 'Personal & Household Care - Detergents, Cleaners, and Oral Care'
            ],
            [
                'name' => 'Health',
                'slug' => 'health',
                'description' => 'Health & Wellness - Ayurvedic Immunity, Honey, and Hygiene'
            ]
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['name']] = Category::updateOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $cat['name'], 'description' => $cat['description'], 'status' => 'active']
            );
        }

        // Subcategories
        $subCategoriesData = [
            'Flour & Grains' => ['parent' => 'Daily Needs', 'slug' => 'flour-grains'],
            'Cooking Oils & Ghee' => ['parent' => 'Daily Needs', 'slug' => 'cooking-oils-ghee'],
            'Pulses & Dals' => ['parent' => 'Daily Needs', 'slug' => 'pulses-dals'],
            'Tea & Coffee' => ['parent' => 'Food', 'slug' => 'tea-coffee'],
            'Chocolates & Sweets' => ['parent' => 'Food', 'slug' => 'chocolates-sweets'],
            'Biscuits & Snacks' => ['parent' => 'Food', 'slug' => 'biscuits-snacks'],
            'Laundry & Cleaning' => ['parent' => 'Home', 'slug' => 'laundry-cleaning'],
            'Kitchen & Cleaning' => ['parent' => 'Home', 'slug' => 'kitchen-cleaning'],
            'Oral Care & Hygiene' => ['parent' => 'Home', 'slug' => 'oral-care-hygiene'],
            'Ayurvedic Immunity' => ['parent' => 'Health', 'slug' => 'ayurvedic-immunity'],
            'Organic Nutrition' => ['parent' => 'Health', 'slug' => 'organic-nutrition'],
            'Personal Care & Hygiene' => ['parent' => 'Health', 'slug' => 'personal-care-hygiene'],
        ];

        $subCategories = [];
        foreach ($subCategoriesData as $name => $meta) {
            $parent = $categories[$meta['parent']] ?? null;
            $subCategories[$name] = Category::updateOrCreate(
                ['slug' => $meta['slug']],
                [
                    'name' => $name,
                    'parent_id' => $parent ? $parent->id : null,
                    'status' => 'active'
                ]
            );
        }

        // 2. Brands
        $brandsList = [
            'Aashirvaad', 'Fortune', 'Daawat', 'Tata Consumer', 'Amul',
            'Tata Tea', 'Cadbury', 'Maggi', 'Surf Excel', 'Vim',
            'Colgate', 'Dabur', 'Dettol', 'Organic India', 'KharchDaan'
        ];

        $brands = [];
        foreach ($brandsList as $bName) {
            $slug = Str::slug($bName);
            $brands[$bName] = Brand::updateOrCreate(
                ['slug' => $slug],
                ['name' => $bName, 'status' => 'active']
            );
        }

        // Attributes
        $weightAttr = ProductAttribute::firstOrCreate(['name' => 'Size / Weight'], ['status' => 'active']);

        // 3. Complete FMCG Products Catalog
        $products = [
            [
                'id_code' => 'prod-atta-1',
                'name' => 'Aashirvaad Superior MP Shudh Chakki Atta',
                'brand' => 'Aashirvaad',
                'category' => 'Daily Needs',
                'sub_category' => 'Flour & Grains',
                'sku' => 'KD-ATTA-01',
                'weight' => 5,
                'unit' => 'Kg',
                'price' => 290.00,
                'sale_price' => 245.00,
                'gst_percentage' => 0,
                'stock_qty' => 250,
                'image' => '/images/aashirvaad-atta.jpg',
                'featured' => true,
                'short_desc' => '100% pure whole wheat grain ground in traditional chakki process for ultra-soft, fluffy rotis.',
                'description' => 'Aashirvaad Shudh Chakki Atta is made from the grains which are heavy on the palm, golden amber in colour and hard in bite. Ground using modern chakki process which ensures 0% Maida and 100% pure Sampoorna Atta.',
                'variants' => [
                    ['size' => '1 Kg', 'price' => 65, 'sale_price' => 58],
                    ['size' => '5 Kg', 'price' => 290, 'sale_price' => 245],
                    ['size' => '10 Kg', 'price' => 560, 'sale_price' => 475],
                ]
            ],
            [
                'id_code' => 'prod-oil-2',
                'name' => 'Fortune Sunlite Refined Sunflower Oil',
                'brand' => 'Fortune',
                'category' => 'Daily Needs',
                'sub_category' => 'Cooking Oils & Ghee',
                'sku' => 'KD-OIL-02',
                'weight' => 1,
                'unit' => 'Litre',
                'price' => 195.00,
                'sale_price' => 165.00,
                'gst_percentage' => 5,
                'stock_qty' => 180,
                'image' => '/images/fortune-oil.jpg',
                'featured' => true,
                'short_desc' => 'Light and healthy refined sunflower oil enriched with Vitamins A, D & E.',
                'description' => 'Fortune Sunlite Refined Sunflower Oil is a light, healthy and easily digestible cooking oil. Rich in natural antioxidants and Vitamin E, it keeps your heart healthy while maintaining the natural flavors of authentic Indian cooking.',
                'variants' => [
                    ['size' => '1 Litre', 'price' => 195, 'sale_price' => 165],
                    ['size' => '5 Litres (Jar)', 'price' => 950, 'sale_price' => 790],
                ]
            ],
            [
                'id_code' => 'prod-oil-mustard',
                'name' => 'Fortune Kachi Ghani Pure Mustard Oil',
                'brand' => 'Fortune',
                'category' => 'Daily Needs',
                'sub_category' => 'Cooking Oils & Ghee',
                'sku' => 'KD-OIL-03',
                'weight' => 1,
                'unit' => 'Litre',
                'price' => 205.00,
                'sale_price' => 175.00,
                'gst_percentage' => 5,
                'stock_qty' => 150,
                'image' => '/images/fortune-oil.jpg',
                'featured' => false,
                'short_desc' => 'Cold-pressed authentic mustard oil with strong pungency and high smoking point.',
                'description' => 'Fortune Kachi Ghani Mustard Oil is traditionally cold pressed from the finest quality mustard seeds, preserving natural pungency, aroma and essential omega fatty acids.',
                'variants' => [
                    ['size' => '1 Litre Pouch', 'price' => 205, 'sale_price' => 175],
                    ['size' => '5 Litre Jar', 'price' => 980, 'sale_price' => 825],
                ]
            ],
            [
                'id_code' => 'prod-rice-7',
                'name' => 'Daawat Rozana Gold Basmati Rice',
                'brand' => 'Daawat',
                'category' => 'Daily Needs',
                'sub_category' => 'Flour & Grains',
                'sku' => 'KD-RICE-04',
                'weight' => 5,
                'unit' => 'Kg',
                'price' => 440.00,
                'sale_price' => 360.00,
                'gst_percentage' => 0,
                'stock_qty' => 120,
                'image' => '/images/daawat-rice.jpg',
                'featured' => false,
                'short_desc' => 'Fluffy, long grain aromatic basmati rice for daily family meals and biryanis.',
                'description' => 'Daawat Rozana Gold is pure basmati rice specially processed for daily cooking. Each grain elongates to double its size, non-sticky and filled with authentic aroma.',
                'variants' => [
                    ['size' => '1 Kg', 'price' => 100, 'sale_price' => 85],
                    ['size' => '5 Kg', 'price' => 440, 'sale_price' => 360],
                ]
            ],
            [
                'id_code' => 'prod-dal-toor',
                'name' => 'Tata Sampann Unpolished Toor Dal',
                'brand' => 'Tata Consumer',
                'category' => 'Daily Needs',
                'sub_category' => 'Pulses & Dals',
                'sku' => 'KD-DAL-05',
                'weight' => 1,
                'unit' => 'Kg',
                'price' => 215.00,
                'sale_price' => 175.00,
                'gst_percentage' => 0,
                'stock_qty' => 200,
                'image' => '/images/tata-dal.jpg',
                'featured' => true,
                'short_desc' => '100% unpolished toor dal rich in natural plant protein and dietary fiber.',
                'description' => 'Tata Sampann Dals do not undergo any artificial polishing with water, oil or leather, thereby retaining their natural protein goodness and wholesome taste.',
                'variants' => [
                    ['size' => '1 Kg', 'price' => 215, 'sale_price' => 175],
                    ['size' => '2 Kg Pack', 'price' => 420, 'sale_price' => 340],
                ]
            ],
            [
                'id_code' => 'prod-ghee-8',
                'name' => 'Amul Pure Desi Cow Ghee (Tin Pack)',
                'brand' => 'Amul',
                'category' => 'Daily Needs',
                'sub_category' => 'Cooking Oils & Ghee',
                'sku' => 'KD-GHEE-06',
                'weight' => 1,
                'unit' => 'Litre',
                'price' => 650.00,
                'sale_price' => 580.00,
                'gst_percentage' => 12,
                'stock_qty' => 90,
                'image' => '/images/amul-ghee.jpg',
                'featured' => false,
                'short_desc' => 'Traditional granular golden desi cow ghee for wholesome family nutrition and aroma.',
                'description' => 'Amul Cow Ghee is made from pure cow milk fat. Rich in natural Fat Soluble Vitamins A, D, E and K, giving an authentic aroma to dal tadka, sweets, and parathas.',
                'variants' => [
                    ['size' => '500 ml', 'price' => 335, 'sale_price' => 300],
                    ['size' => '1 Litre (Tin)', 'price' => 650, 'sale_price' => 580],
                ]
            ],
            [
                'id_code' => 'prod-tea-5',
                'name' => 'Tata Tea Premium Desh Ki Chai Blend',
                'brand' => 'Tata Tea',
                'category' => 'Food',
                'sub_category' => 'Tea & Coffee',
                'sku' => 'KD-TEA-07',
                'weight' => 0.25,
                'unit' => 'Kg',
                'price' => 160.00,
                'sale_price' => 135.00,
                'gst_percentage' => 5,
                'stock_qty' => 300,
                'image' => '/images/tata-tea.jpg',
                'featured' => true,
                'short_desc' => 'Unique blend of fine tea leaves and big strong grains for unmatched aroma and taste.',
                'description' => 'Tata Tea Premium gives you the unique combination of bada daana for exquisite strength and chhota daana for superb aroma. Expertly selected by tea masters from Assam and Dooars gardens.',
                'variants' => [
                    ['size' => '250 g', 'price' => 160, 'sale_price' => 135],
                    ['size' => '500 g', 'price' => 310, 'sale_price' => 260],
                    ['size' => '1 Kg', 'price' => 590, 'sale_price' => 495],
                ]
            ],
            [
                'id_code' => 'prod-tea-gold',
                'name' => 'Tata Tea Gold Rich Aroma & Flavor',
                'brand' => 'Tata Tea',
                'category' => 'Food',
                'sub_category' => 'Tea & Coffee',
                'sku' => 'KD-TEA-08',
                'weight' => 0.5,
                'unit' => 'Kg',
                'price' => 375.00,
                'sale_price' => 310.00,
                'gst_percentage' => 5,
                'stock_qty' => 160,
                'image' => '/images/tata-tea.jpg',
                'featured' => true,
                'short_desc' => 'Gently rolled 15% long tea leaves blended with Assam CTC for irresistible aroma.',
                'description' => 'Tata Tea Gold is an exquisite blend of high-grown Assam black tea with 15% gently rolled long tea leaves that release rich taste and captivating aroma with every brew.',
                'variants' => [
                    ['size' => '500 g', 'price' => 375, 'sale_price' => 310],
                    ['size' => '1 Kg Value Pack', 'price' => 720, 'sale_price' => 590],
                ]
            ],
            [
                'id_code' => 'prod-choc-3',
                'name' => 'Cadbury Dairy Milk Silk Chocolate Bar',
                'brand' => 'Cadbury',
                'category' => 'Food',
                'sub_category' => 'Chocolates & Sweets',
                'sku' => 'KD-CHOC-09',
                'weight' => 0.15,
                'unit' => 'Kg',
                'price' => 135.00,
                'sale_price' => 110.00,
                'gst_percentage' => 18,
                'stock_qty' => 400,
                'image' => '/images/cadbury-dairy-milk.jpg',
                'featured' => true,
                'short_desc' => 'Creamy and smooth milk chocolate bar crafted with the finest cocoa beans.',
                'description' => 'Indulge in the rich, delicious taste of Cadbury Dairy Milk Silk. Made from 100% sustainably sourced cocoa, providing a melt-in-the-mouth texture loved by Indian families.',
                'variants' => [
                    ['size' => '60 g Regular', 'price' => 55, 'sale_price' => 45],
                    ['size' => '150 g (Silk Edition)', 'price' => 135, 'sale_price' => 110],
                ]
            ],
            [
                'id_code' => 'prod-choc-celeb',
                'name' => 'Cadbury Celebrations Rich Chocolate Gift Pack',
                'brand' => 'Cadbury',
                'category' => 'Food',
                'sub_category' => 'Chocolates & Sweets',
                'sku' => 'KD-CHOC-10',
                'weight' => 0.35,
                'unit' => 'Kg',
                'price' => 295.00,
                'sale_price' => 240.00,
                'gst_percentage' => 18,
                'stock_qty' => 150,
                'image' => '/images/cadbury-dairy-milk.jpg',
                'featured' => false,
                'short_desc' => 'Festive assortment of Dairy Milk, 5-Star, and Gems for celebrations and gifting.',
                'description' => 'Cadbury Celebrations brings joy to every family gathering with an iconic mix of favorite Cadbury treats in a royal gift box.',
                'variants' => [
                    ['size' => '180 g Mini Box', 'price' => 160, 'sale_price' => 130],
                    ['size' => '350 g Festive Gift Box', 'price' => 295, 'sale_price' => 240],
                ]
            ],
            [
                'id_code' => 'prod-snack-maggi',
                'name' => 'Maggi 2-Minute Masala Instant Noodles',
                'brand' => 'Maggi',
                'category' => 'Food',
                'sub_category' => 'Biscuits & Snacks',
                'sku' => 'KD-MAGGI-11',
                'weight' => 0.84,
                'unit' => 'Kg',
                'price' => 192.00,
                'sale_price' => 155.00,
                'gst_percentage' => 12,
                'stock_qty' => 350,
                'image' => '/images/maggi-noodles.jpg',
                'featured' => true,
                'short_desc' => 'India’s favorite instant noodle with authentic blend of 20 roasted spices and herbs.',
                'description' => 'Maggi 2-Minute Masala Noodles provide quick, delicious, iron-fortified noodles that bring smiles to snack time.',
                'variants' => [
                    ['size' => 'Pack of 6', 'price' => 96, 'sale_price' => 80],
                    ['size' => 'Pack of 12 (Mega Saver)', 'price' => 192, 'sale_price' => 155],
                ]
            ],
            [
                'id_code' => 'prod-surf-4',
                'name' => 'Surf Excel Easy Wash Detergent Powder',
                'brand' => 'Surf Excel',
                'category' => 'Home',
                'sub_category' => 'Laundry & Cleaning',
                'sku' => 'KD-SURF-12',
                'weight' => 1,
                'unit' => 'Kg',
                'price' => 210.00,
                'sale_price' => 175.00,
                'gst_percentage' => 18,
                'stock_qty' => 220,
                'image' => '/images/surf-excel.jpg',
                'featured' => true,
                'short_desc' => 'Superfine powder with power of 10 hands to remove tough stains effortlessly.',
                'description' => 'Surf Excel Easy Wash delivers ultra-fast stain removal with advanced cleaning particles that penetrate deep into fabrics, keeping your family clothes sparkling white and fresh.',
                'variants' => [
                    ['size' => '1 Kg', 'price' => 210, 'sale_price' => 175],
                    ['size' => '3 Kg (Economy Pack)', 'price' => 580, 'sale_price' => 485],
                ]
            ],
            [
                'id_code' => 'prod-surf-matic',
                'name' => 'Surf Excel Matic Top Load Liquid Detergent',
                'brand' => 'Surf Excel',
                'category' => 'Home',
                'sub_category' => 'Laundry & Cleaning',
                'sku' => 'KD-SURF-13',
                'weight' => 2,
                'unit' => 'Litre',
                'price' => 440.00,
                'sale_price' => 365.00,
                'gst_percentage' => 18,
                'stock_qty' => 140,
                'image' => '/images/surf-excel.jpg',
                'featured' => false,
                'short_desc' => 'Liquid detergent designed specifically for washing machines, produces 3x less foam.',
                'description' => 'Surf Excel Matic Liquid Detergent dissolves instantly in water, leaving zero residue on clothes while protecting machine parts and removing hard grease stains in 1 wash.',
                'variants' => [
                    ['size' => '1 Litre Bottle', 'price' => 235, 'sale_price' => 195],
                    ['size' => '2 Litres (Economy Pouch)', 'price' => 440, 'sale_price' => 365],
                ]
            ],
            [
                'id_code' => 'prod-vim-gel',
                'name' => 'Vim Dishwash Gel Lemon Power',
                'brand' => 'Vim',
                'category' => 'Home',
                'sub_category' => 'Kitchen & Cleaning',
                'sku' => 'KD-VIM-14',
                'weight' => 0.75,
                'unit' => 'Litre',
                'price' => 180.00,
                'sale_price' => 145.00,
                'gst_percentage' => 18,
                'stock_qty' => 190,
                'image' => '/images/vim-gel.jpg',
                'featured' => true,
                'short_desc' => 'Concentrated gel with 100 real lemon power that removes oily grease in 1 spoon.',
                'description' => 'Vim Gel has power of 100 lemons. It cleans tough grease from stainless steel, non-stick cookware and glassware without scratching surfaces.',
                'variants' => [
                    ['size' => '500 ml Bottle', 'price' => 130, 'sale_price' => 105],
                    ['size' => '750 ml Bottle', 'price' => 180, 'sale_price' => 145],
                ]
            ],
            [
                'id_code' => 'prod-colgate',
                'name' => 'Colgate Strong Teeth Calcium Anticavity Toothpaste',
                'brand' => 'Colgate',
                'category' => 'Home',
                'sub_category' => 'Oral Care & Hygiene',
                'sku' => 'KD-COLG-15',
                'weight' => 0.5,
                'unit' => 'Kg',
                'price' => 245.00,
                'sale_price' => 195.00,
                'gst_percentage' => 18,
                'stock_qty' => 250,
                'image' => '/images/colgate-maxfresh.jpg',
                'featured' => false,
                'short_desc' => 'Amino Shakti formula adds natural calcium to make teeth 2x stronger against cavities.',
                'description' => 'Colgate Strong Teeth with Amino Shakti strengthens teeth enamel from within and provides all-day fresh breath.',
                'variants' => [
                    ['size' => '200 g Tube', 'price' => 115, 'sale_price' => 95],
                    ['size' => '500 g (2x250g Saver)', 'price' => 245, 'sale_price' => 195],
                ]
            ],
            [
                'id_code' => 'prod-dabur-chyawanprash',
                'name' => 'Dabur Chyawanprash 2X Immunity Booster Awaleha',
                'brand' => 'Dabur',
                'category' => 'Health',
                'sub_category' => 'Ayurvedic Immunity',
                'sku' => 'KD-DABUR-16',
                'weight' => 1,
                'unit' => 'Kg',
                'price' => 415.00,
                'sale_price' => 335.00,
                'gst_percentage' => 12,
                'stock_qty' => 170,
                'image' => '/images/dabur-chyawanprash.jpg',
                'featured' => true,
                'short_desc' => 'Time-tested Ayurvedic formulation with 40+ vital herbs like Amla, Ashwagandha & Giloy.',
                'description' => 'Dabur Chyawanprash is clinically tested for 2x immunity. Enriched with natural Vitamin C from fresh amla to fight seasonal infections, cough, and cold.',
                'variants' => [
                    ['size' => '500 g Jar', 'price' => 230, 'sale_price' => 185],
                    ['size' => '1 Kg Jar (+250g Free)', 'price' => 415, 'sale_price' => 335],
                ]
            ],
            [
                'id_code' => 'prod-honey-raw',
                'name' => 'Dabur 100% Pure Raw Forest Honey',
                'brand' => 'Dabur',
                'category' => 'Health',
                'sub_category' => 'Organic Nutrition',
                'sku' => 'KD-HONEY-17',
                'weight' => 0.5,
                'unit' => 'Kg',
                'price' => 310.00,
                'sale_price' => 240.00,
                'gst_percentage' => 0,
                'stock_qty' => 140,
                'image' => '/images/health-wellness-ayurveda.jpg',
                'featured' => true,
                'short_desc' => 'Unadulterated NMR-tested pure honey sourced straight from natural beehives.',
                'description' => 'Dabur Honey is 100% pure and natural, compliant with 22 rigorous parameters of FSSAI and international NMR purity standards.',
                'variants' => [
                    ['size' => '250 g Squeezy', 'price' => 165, 'sale_price' => 130],
                    ['size' => '500 g Glass Jar', 'price' => 310, 'sale_price' => 240],
                    ['size' => '1 Kg Value Jar', 'price' => 575, 'sale_price' => 440],
                ]
            ],
            [
                'id_code' => 'prod-dettol-6',
                'name' => 'Dettol Skincare Germ Protection Handwash',
                'brand' => 'Dettol',
                'category' => 'Health',
                'sub_category' => 'Personal Care & Hygiene',
                'sku' => 'KD-DETTOL-18',
                'weight' => 0.75,
                'unit' => 'Litre',
                'price' => 240.00,
                'sale_price' => 195.00,
                'gst_percentage' => 18,
                'stock_qty' => 210,
                'image' => '/images/dettol-handwash.jpg',
                'featured' => true,
                'short_desc' => '10x better protection against illness-causing germs with moisturizing glycerine.',
                'description' => 'Dettol Skincare Liquid Handwash is specially formulated with added moisture to help protect against 100 illness-causing germs while keeping your hands soft and smooth.',
                'variants' => [
                    ['size' => '200 ml (Pump)', 'price' => 145, 'sale_price' => 120],
                    ['size' => '750 ml (Refill Pouch)', 'price' => 240, 'sale_price' => 195],
                ]
            ],
            [
                'id_code' => 'prod-tulsi-tea',
                'name' => 'Organic India Certified Tulsi Green Tea',
                'brand' => 'Organic India',
                'category' => 'Health',
                'sub_category' => 'Ayurvedic Immunity',
                'sku' => 'KD-TULSI-19',
                'weight' => 0.2,
                'unit' => 'Kg',
                'price' => 220.00,
                'sale_price' => 175.00,
                'gst_percentage' => 5,
                'stock_qty' => 130,
                'image' => '/images/health-wellness-ayurveda.jpg',
                'featured' => false,
                'short_desc' => 'Holy basil blend with antioxidant rich green tea leaves for daily detox and stress relief.',
                'description' => 'Organic India Tulsi Green Tea combines Krishna, Rama, and Vana Tulsi with premium green tea for complete bodily rejuvenation and natural metabolism support.',
                'variants' => [
                    ['size' => '25 Tea Bags Box', 'price' => 220, 'sale_price' => 175],
                    ['size' => '100g Loose Leaf Tin', 'price' => 290, 'sale_price' => 230],
                ]
            ],
            [
                'id_code' => 'prod-besan',
                'name' => 'Besan',
                'brand' => 'KharchDaan',
                'category' => 'Daily Needs',
                'sub_category' => 'Flour & Grains',
                'sku' => 'KD-BESAN-20',
                'weight' => 0.5,
                'unit' => 'pcs',
                'price' => 250.00,
                'sale_price' => 200.00,
                'gst_percentage' => 5,
                'stock_qty' => 150,
                'image' => '/images/besan.jpg',
                'featured' => true,
                'short_desc' => '100% pure premium unadulterated Chana Dal Besan for traditional cooking and sweets.',
                'description' => 'Made from sorted, cleaned and stone-ground Grade-A Bengal gram pulses. Rich in natural plant protein and dietary fiber.',
                'variants' => [
                    ['size' => 'pcs', 'price' => 250, 'sale_price' => 200],
                    ['size' => '1 Kg Pack', 'price' => 480, 'sale_price' => 380],
                ]
            ],
        ];

        foreach ($products as $pData) {
            $cat = $categories[$pData['category']] ?? null;
            $subCat = $subCategories[$pData['sub_category']] ?? null;
            $brand = $brands[$pData['brand']] ?? null;
            $slug = Str::slug($pData['name']);

            $product = Product::updateOrCreate(
                ['sku' => $pData['sku']],
                [
                    'name' => $pData['name'],
                    'slug' => $slug,
                    'category_id' => $cat ? $cat->id : 1,
                    'sub_category_id' => $subCat ? $subCat->id : null,
                    'brand_id' => $brand ? $brand->id : null,
                    'price' => $pData['price'],
                    'sale_price' => $pData['sale_price'],
                    'gst_percentage' => $pData['gst_percentage'],
                    'stock_qty' => $pData['stock_qty'],
                    'low_stock_qty' => 10,
                    'weight' => $pData['weight'],
                    'unit' => $pData['unit'],
                    'min_order_qty' => 1,
                    'image' => $pData['image'],
                    'short_desc' => $pData['short_desc'],
                    'description' => $pData['description'],
                    'status' => 'active',
                    'featured' => $pData['featured'],
                ]
            );

            // Add variations if any
            if (!empty($pData['variants'])) {
                foreach ($pData['variants'] as $v) {
                    ProductVariation::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'attr_val' => $v['size'],
                        ],
                        [
                            'attr_id' => $weightAttr->id,
                            'sku' => $product->sku . '-' . Str::slug($v['size']),
                            'price' => $v['price'],
                            'sale_price' => $v['sale_price'],
                            'stock_qty' => 50,
                            'status' => 'active',
                        ]
                    );
                }
            }
        }
    }
}
