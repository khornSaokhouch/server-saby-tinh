<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductItem;
use App\Models\ProductItemVariant;
use App\Models\Seller;
use App\Models\Size;
use App\Models\Store;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        file_put_contents('seeder_log.txt', "Starting enhanced seeding run...\n");

        // 0. Cleanup previous seeded data
        file_put_contents('seeder_log.txt', "Cleaning up old data...\n", FILE_APPEND);
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_images')->delete();
        DB::table('product_item_variants')->delete();
        DB::table('product_items')->delete();
        DB::table('products')->delete();
        DB::table('stores')->delete();
        DB::table('sellers')->delete();
        DB::table('types')->delete();
        DB::table('brands')->delete();
        DB::table('categories')->delete();
        User::where('role', 'owner')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Seed Categories, Types, and Brands if empty
        $categoryData = [
            'Electronics' => [
                'types' => ['Smartphones', 'Audio', 'Computing', 'Accessories'],
                'brands' => ['Apple', 'Samsung', 'Sony', 'Anker'],
                'image' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?q=80&w=1000'
            ],
            'Fashion' => [
                'types' => ['Streetwear', 'Formal', 'Casual', 'Outerwear'],
                'brands' => ['Nike', 'Adidas', 'Zara', 'Levi\'s'],
                'image' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?q=80&w=1000'
            ],
            'Home' => [
                'types' => ['Furniture', 'Decor', 'Kitchenware', 'Bedding'],
                'brands' => ['IKEA', 'West Elm', 'Crate & Barrel', 'Dyson'],
                'image' => 'https://images.unsplash.com/photo-1484101403633-562f891dc89a?q=80&w=1000'
            ],
            'Beauty' => [
                'types' => ['Skincare', 'Makeup', 'Fragrance', 'Haircare'],
                'brands' => ['L\'Oreal', 'Estée Lauder', 'MAC', 'Chanel'],
                'image' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?q=80&w=1000'
            ],
            'Sports' => [
                'types' => ['Fitness', 'Outdoor', 'Team Sports', 'Yoga'],
                'brands' => ['Nike', 'Under Armour', 'Puma', 'Reebok'],
                'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?q=80&w=1000'
            ],
        ];

        foreach ($categoryData as $catName => $data) {
            $category = Category::updateOrCreate(
                ['name' => $catName],
                ['category_image' => $data['image'], 'status' => 1]
            );

            foreach ($data['types'] as $typeName) {
                Type::updateOrCreate(['category_id' => $category->id, 'name' => $typeName]);
            }

            $brandImages = [
                'https://images.unsplash.com/photo-1599305096181-01e5d7a3f157?q=80&w=1000',
                'https://images.unsplash.com/photo-1541167760496-1628856ab772?q=80&w=1000',
                'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1000',
                'https://images.unsplash.com/photo-1560179707-f14e90ef3623?q=80&w=1000',
                'https://images.unsplash.com/photo-1626785774573-4b799315345d?q=80&w=1000'
            ];
            foreach ($data['brands'] as $brandName) {
                Brand::updateOrCreate(
                    ['name' => $brandName, 'category_id' => $category->id],
                    [
                        'brand_image' => $brandImages[array_rand($brandImages)],
                        'status' => 1
                    ]
                );
            }
        }

        // 2. Seed Colors and Sizes
        $colors = ['Jet Black', 'Pure White', 'Space Gray', 'Navy Blue', 'Forest Green'];
        foreach ($colors as $color) {
            Color::updateOrCreate(['name' => $color]);
        }
        $sizes = ['S', 'M', 'L', 'XL', 'Standard'];
        foreach ($sizes as $size) {
            Size::updateOrCreate(['name' => $size]);
        }

        $allCategories = Category::all();
        $allBrands = Brand::all();
        $allTypes = Type::all();
        $allColors = Color::all();
        $allSizes = Size::all();

        $storeNames = ['Tech Haven', 'Urban Chic', 'Home Bliss', 'Glow & Grace', 'Peak Performance', 'Gadget World', 'Style Station', 'Living Luxury', 'Beauty Bloom', 'Sports Spirit'];
        $storeImages = [
            'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1000',
            'https://images.unsplash.com/photo-1567401893414-76b7b1e5a7a5?q=80&w=1000',
            'https://images.unsplash.com/photo-1522204523234-8729aa6e3d5f?q=80&w=1000',
            'https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?q=80&w=1000',
            'https://images.unsplash.com/photo-1583258292688-d0213dc5a3a8?q=80&w=1000',
            'https://images.unsplash.com/photo-1534452203293-497d1f970081?q=80&w=1000'
        ];
        file_put_contents('seeder_log.txt', "Counts - Categories: " . $allCategories->count() . ", Brands: " . $allBrands->count() . ", Types: " . $allTypes->count() . "\n", FILE_APPEND);

        // 3. Create 10 Stores with Sellers
        file_put_contents('seeder_log.txt', "Seeding 10 stores...\n", FILE_APPEND);
        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => "Seller " . ($i + 1),
                'email' => "seller" . ($i + 1) . "@example.com",
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]);

            Seller::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'company_name' => $storeNames[$i] . " Co.",
                'email' => $user->email,
                'status' => 'approved',
            ]);

            file_put_contents('seeder_log.txt', "Creating store " . ($i+1) . "...\n", FILE_APPEND);
            $store = Store::create([
                'user_id' => $user->id,
                'name' => $storeNames[$i],
                'store_image' => $storeImages[array_rand($storeImages)],
            ]);

            // 4. Create 10 Products for each Store (Total 100)
            file_put_contents('seeder_log.txt', "Creating 10 products for store " . ($i+1) . "...\n", FILE_APPEND);
            for ($j = 1; $j <= 10; $j++) {
                $category = $allCategories->random();
                $brandsInCategory = $allBrands->where('category_id', $category->id);
                $brand = ($brandsInCategory->count() > 0) ? $brandsInCategory->random() : $allBrands->random();

                $typesInCategory = $allTypes->where('category_id', $category->id);
                $type = ($typesInCategory->count() > 0) ? $typesInCategory->random() : $allTypes->random();

                $productName = $category->name . " Item " . $j;
                // Try to make it a bit more realistic if possible
                $productSamples = [
                    'Electronics' => ['Smart Phone', 'Wireless Buds', 'Laptop Pro', 'Smart Watch'],
                    'Fashion' => ['Designer Tee', 'Denim Jeans', 'Winter Coat', 'Summer Dress'],
                    'Home' => ['Wooden Table', 'Desk Lamp', 'Chef Knife', 'Silk Sheets'],
                    'Beauty' => ['Face Cream', 'Lip Gloss', 'Men\'s Perfume', 'Eye Palette'],
                    'Sports' => ['Running Shoes', 'Yoga Block', 'Dumbbell Set', 'Sports Bag'],
                ];
                if (isset($productSamples[$category->name])) {
                    $productName = $productSamples[$category->name][array_rand($productSamples[$category->name])] . " - " . Str::random(4);
                }

                file_put_contents('seeder_log.txt', "  Creating product $j ($productName)...\n", FILE_APPEND);
                $product = Product::create([
                    'store_id' => $store->id,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'type_id' => $type->id,
                    'name' => $productName,
                    'description' => "Experience the best of " . $category->name . " with our " . $productName . ".",
                    'price' => rand(20, 999),
                    'status' => 1
                ]);

                // Add 2 images for each product
                $catImg = $category->category_image;
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $catImg,
                    'is_primary' => 1
                ]);
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=1000', // Generic product shot
                    'is_primary' => 0
                ]);

                $productItem = ProductItem::create([
                    'product_id' => $product->id,
                    'sku' => strtoupper(Str::random(8)),
                    'base_price' => $product->price,
                    'quantity_in_stock' => rand(5, 50),
                    'status' => 1
                ]);

                ProductItemVariant::create([
                    'product_item_id' => $productItem->id,
                    'color_id' => $allColors->random()->id,
                    'size_id' => $allSizes->random()->id,
                    'price_modifier' => 0,
                    'quantity_in_stock' => $productItem->quantity_in_stock,
                    'status' => 1
                ]);
            }
        }

        file_put_contents('seeder_log.txt', "Seeding finished!\n", FILE_APPEND);
        file_put_contents('seeder_log.txt', "Final Brand Count with Image: " . Brand::whereNotNull('brand_image')->count() . "/" . Brand::count() . "\n", FILE_APPEND);
        $this->command->info('Successfully seeded 10 stores and 100 products with images!');
    }
}
