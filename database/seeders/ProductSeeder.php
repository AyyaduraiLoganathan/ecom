<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $categories = Category::whereNotNull('parent_id')->get(); // Only child categories

        $productTemplates = [
            // Electronics - Smartphones
            [
                'category' => 'Smartphones',
                'products' => [
                    ['name' => 'iPhone 15 Pro Max', 'price' => 1199.99, 'sale_price' => 1099.99],
                    ['name' => 'Samsung Galaxy S24 Ultra', 'price' => 1299.99],
                    ['name' => 'Google Pixel 8 Pro', 'price' => 999.99, 'sale_price' => 899.99],
                    ['name' => 'OnePlus 12', 'price' => 799.99],
                    ['name' => 'Xiaomi 14 Pro', 'price' => 699.99, 'sale_price' => 649.99],
                ]
            ],
            // Electronics - Laptops
            [
                'category' => 'Laptops',
                'products' => [
                    ['name' => 'MacBook Pro 16"', 'price' => 2499.99, 'sale_price' => 2299.99],
                    ['name' => 'Dell XPS 15', 'price' => 1899.99],
                    ['name' => 'HP Spectre x360', 'price' => 1599.99, 'sale_price' => 1399.99],
                    ['name' => 'Lenovo ThinkPad X1 Carbon', 'price' => 1799.99],
                    ['name' => 'ASUS ZenBook Pro', 'price' => 1299.99, 'sale_price' => 1199.99],
                ]
            ],
            // Fashion - Men's Clothing
            [
                'category' => 'Men\'s Clothing',
                'products' => [
                    ['name' => 'Classic Cotton T-Shirt', 'price' => 29.99, 'sale_price' => 24.99],
                    ['name' => 'Slim Fit Jeans', 'price' => 79.99],
                    ['name' => 'Casual Button-Down Shirt', 'price' => 59.99, 'sale_price' => 49.99],
                    ['name' => 'Wool Blend Sweater', 'price' => 89.99],
                    ['name' => 'Leather Jacket', 'price' => 299.99, 'sale_price' => 249.99],
                ]
            ],
            // Fashion - Women's Clothing
            [
                'category' => 'Women\'s Clothing',
                'products' => [
                    ['name' => 'Floral Summer Dress', 'price' => 69.99, 'sale_price' => 54.99],
                    ['name' => 'High-Waisted Jeans', 'price' => 89.99],
                    ['name' => 'Silk Blouse', 'price' => 79.99, 'sale_price' => 64.99],
                    ['name' => 'Cashmere Cardigan', 'price' => 149.99],
                    ['name' => 'Little Black Dress', 'price' => 119.99, 'sale_price' => 99.99],
                ]
            ],
            // Home & Garden - Furniture
            [
                'category' => 'Furniture',
                'products' => [
                    ['name' => 'Modern Sofa Set', 'price' => 1299.99, 'sale_price' => 1099.99],
                    ['name' => 'Oak Dining Table', 'price' => 899.99],
                    ['name' => 'Ergonomic Office Chair', 'price' => 399.99, 'sale_price' => 349.99],
                    ['name' => 'Queen Size Bed Frame', 'price' => 699.99],
                    ['name' => 'Coffee Table Set', 'price' => 299.99, 'sale_price' => 249.99],
                ]
            ],
            // Sports & Outdoors - Fitness Equipment
            [
                'category' => 'Fitness Equipment',
                'products' => [
                    ['name' => 'Adjustable Dumbbells Set', 'price' => 299.99, 'sale_price' => 249.99],
                    ['name' => 'Yoga Mat Premium', 'price' => 49.99],
                    ['name' => 'Resistance Bands Kit', 'price' => 39.99, 'sale_price' => 29.99],
                    ['name' => 'Exercise Bike', 'price' => 599.99],
                    ['name' => 'Pull-up Bar', 'price' => 79.99, 'sale_price' => 69.99],
                ]
            ],
        ];

        foreach ($productTemplates as $template) {
            $category = $categories->where('name', $template['category'])->first();
            if (!$category) continue;

            foreach ($template['products'] as $productData) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'slug' => Str::slug($productData['name']),
                    'description' => $faker->paragraphs(3, true),
                    'short_description' => $faker->sentence(15),
                    'sku' => strtoupper($faker->lexify('???')) . $faker->numerify('####'),
                    'price' => $productData['price'],
                    'sale_price' => $productData['sale_price'] ?? null,
                    'stock_quantity' => $faker->numberBetween(10, 100),
                    'manage_stock' => true,
                    'in_stock' => true,
                    'status' => 'active',
                    'featured_image' => $this->getProductImage($template['category']),
                    'gallery_images' => [
                        $this->getProductImage($template['category']),
                        $this->getProductImage($template['category']),
                        $this->getProductImage($template['category']),
                    ],
                    'weight' => $faker->randomFloat(2, 0.1, 10),
                    'dimensions' => $faker->numerify('##') . 'x' . $faker->numerify('##') . 'x' . $faker->numerify('##') . ' cm',
                    'is_featured' => $faker->boolean(30), // 30% chance of being featured
                    'is_digital' => false,
                    'views_count' => $faker->numberBetween(0, 1000),
                    'average_rating' => $faker->randomFloat(2, 3.0, 5.0),
                    'reviews_count' => $faker->numberBetween(0, 50),
                    'meta_title' => $productData['name'] . ' - Best Price Online',
                    'meta_description' => 'Buy ' . $productData['name'] . ' at the best price. Fast shipping and excellent customer service.',
                    'attributes' => $this->getProductAttributes($template['category']),
                ]);
            }
        }

        // Create additional random products for other categories
        $remainingCategories = $categories->whereNotIn('name', array_column($productTemplates, 'category'));
        
        foreach ($remainingCategories as $category) {
            for ($i = 0; $i < 5; $i++) {
                $basePrice = $faker->randomFloat(2, 19.99, 999.99);
                $hasSale = $faker->boolean(40); // 40% chance of sale
                
                Product::create([
                    'category_id' => $category->id,
                    'name' => $this->generateProductName($category->name, $faker),
                    'slug' => Str::slug($this->generateProductName($category->name, $faker)),
                    'description' => $faker->paragraphs(3, true),
                    'short_description' => $faker->sentence(15),
                    'sku' => strtoupper($faker->lexify('???')) . $faker->numerify('####'),
                    'price' => $basePrice,
                    'sale_price' => $hasSale ? $basePrice * 0.8 : null,
                    'stock_quantity' => $faker->numberBetween(5, 50),
                    'manage_stock' => true,
                    'in_stock' => true,
                    'status' => 'active',
                    'featured_image' => $this->getProductImage($category->parent->name ?? $category->name),
                    'gallery_images' => [
                        $this->getProductImage($category->parent->name ?? $category->name),
                        $this->getProductImage($category->parent->name ?? $category->name),
                    ],
                    'weight' => $faker->randomFloat(2, 0.1, 5),
                    'dimensions' => $faker->numerify('##') . 'x' . $faker->numerify('##') . 'x' . $faker->numerify('##') . ' cm',
                    'is_featured' => $faker->boolean(20),
                    'is_digital' => false,
                    'views_count' => $faker->numberBetween(0, 500),
                    'average_rating' => $faker->randomFloat(2, 3.0, 5.0),
                    'reviews_count' => $faker->numberBetween(0, 30),
                    'meta_title' => $this->generateProductName($category->name, $faker) . ' - Shop Online',
                    'meta_description' => 'High-quality ' . strtolower($category->name) . ' products at competitive prices.',
                    'attributes' => $this->getProductAttributes($category->name),
                ]);
            }
        }
    }

    private function getProductImage(string $category): string
    {
        $imageMap = [
            'Smartphones' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&h=500&fit=crop',
            'Laptops' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&h=500&fit=crop',
            'Men\'s Clothing' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500&h=500&fit=crop',
            'Women\'s Clothing' => 'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=500&h=500&fit=crop',
            'Furniture' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500&h=500&fit=crop',
            'Fitness Equipment' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=500&h=500&fit=crop',
            'Electronics' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=500&h=500&fit=crop',
            'Fashion' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=500&h=500&fit=crop',
            'Home & Garden' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500&h=500&fit=crop',
            'Sports & Outdoors' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=500&h=500&fit=crop',
        ];

        return $imageMap[$category] ?? 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=500&h=500&fit=crop';
    }

    private function generateProductName(string $category, $faker): string
    {
        $prefixes = [
            'Premium', 'Professional', 'Deluxe', 'Classic', 'Modern', 'Vintage', 
            'Luxury', 'Essential', 'Ultimate', 'Advanced', 'Standard', 'Elite'
        ];

        $suffixes = [
            'Pro', 'Plus', 'Max', 'Elite', 'Premium', 'Standard', 'Classic', 
            'Deluxe', 'Ultimate', 'Advanced', 'Essential', 'Special Edition'
        ];

        $prefix = $faker->randomElement($prefixes);
        $suffix = $faker->boolean(60) ? ' ' . $faker->randomElement($suffixes) : '';
        
        return $prefix . ' ' . $category . ' Item' . $suffix;
    }

    private function getProductAttributes(string $category): array
    {
        $attributeMap = [
            'Smartphones' => [
                'color' => ['Black', 'White', 'Blue', 'Red'],
                'storage' => ['128GB', '256GB', '512GB'],
                'screen_size' => ['6.1"', '6.7"', '6.9"'],
            ],
            'Laptops' => [
                'color' => ['Silver', 'Space Gray', 'Black'],
                'ram' => ['8GB', '16GB', '32GB'],
                'storage' => ['256GB SSD', '512GB SSD', '1TB SSD'],
            ],
            'Men\'s Clothing' => [
                'size' => ['S', 'M', 'L', 'XL', 'XXL'],
                'color' => ['Black', 'White', 'Navy', 'Gray', 'Blue'],
            ],
            'Women\'s Clothing' => [
                'size' => ['XS', 'S', 'M', 'L', 'XL'],
                'color' => ['Black', 'White', 'Red', 'Blue', 'Pink'],
            ],
            'Furniture' => [
                'material' => ['Wood', 'Metal', 'Fabric', 'Leather'],
                'color' => ['Brown', 'Black', 'White', 'Gray'],
            ],
        ];

        return $attributeMap[$category] ?? [
            'color' => ['Black', 'White', 'Gray'],
            'size' => ['Small', 'Medium', 'Large'],
        ];
    }
}
