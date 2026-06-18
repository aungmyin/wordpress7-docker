<?php
/**
 * Standalone Demo Products Creator
 *
 * To run this script:
 * 1. Place in: wp-content/themes/claude-ai-shopping-theme/
 * 2. Load WordPress: require_once(dirname(__FILE__) . '/../../wp-load.php');
 * 3. Run: php create-demo-products.php
 *
 * Or access via: http://yoursite.com/wp-json/custom/v1/create-demo-products
 */

// Load WordPress
require_once(dirname(__FILE__) . '/../../wp-load.php');

if (!function_exists('wc_get_products')) {
    die('❌ WooCommerce is not installed or activated.');
}

// Demo products data
$demo_products = [
    [
        'name' => 'Wireless Bluetooth Headphones',
        'description' => 'Premium wireless headphones with active noise cancellation, 30-hour battery life, and crystal-clear sound quality. Perfect for work, travel, and everyday use.',
        'price' => 129.99,
        'regular_price' => 149.99,
        'category' => 'Electronics',
        'sku' => 'HEADPHONES-001',
        'stock' => 50,
    ],
    [
        'name' => 'USB-C Fast Charging Cable',
        'description' => '6ft premium USB-C charging cable with 100W power delivery. Supports fast charging for laptops, tablets, and phones. Durable braided design.',
        'price' => 19.99,
        'regular_price' => 24.99,
        'category' => 'Electronics',
        'sku' => 'USB-C-CABLE',
        'stock' => 200,
    ],
    [
        'name' => 'Portable Power Bank 20000mAh',
        'description' => 'High-capacity power bank with dual USB ports and LED display. Charges your phone 5+ times. Compact and lightweight for travel.',
        'price' => 34.99,
        'regular_price' => 44.99,
        'category' => 'Electronics',
        'sku' => 'POWERBANK-20K',
        'stock' => 75,
    ],
    [
        'name' => 'Wireless Mouse - Ergonomic',
        'description' => 'Ergonomic wireless mouse with precision tracking and 2-year battery life. Reduces wrist strain during long work sessions.',
        'price' => 24.99,
        'regular_price' => 29.99,
        'category' => 'Electronics',
        'sku' => 'MOUSE-ERGONOMIC',
        'stock' => 100,
    ],
    [
        'name' => 'Mechanical Gaming Keyboard',
        'description' => 'RGB mechanical keyboard with customizable switches and aluminum frame. Perfect for gaming and professional typing.',
        'price' => 89.99,
        'regular_price' => 109.99,
        'category' => 'Electronics',
        'sku' => 'KEYBOARD-GAMING',
        'stock' => 40,
    ],
    [
        'name' => 'Premium Laptop Stand',
        'description' => 'Adjustable aluminum laptop stand for ergonomic workspace setup. Compatible with all laptops up to 17 inches.',
        'price' => 49.99,
        'regular_price' => 59.99,
        'category' => 'Office',
        'sku' => 'LAPTOP-STAND',
        'stock' => 60,
    ],
    [
        'name' => 'Desk Lamp LED - Dimmable',
        'description' => 'LED desk lamp with 5 brightness levels and USB charging port. Energy-efficient with long-lasting LED bulb.',
        'price' => 39.99,
        'regular_price' => 49.99,
        'category' => 'Office',
        'sku' => 'DESK-LAMP-LED',
        'stock' => 80,
    ],
    [
        'name' => 'Wireless Charger Pad',
        'description' => '15W fast wireless charging pad compatible with all Qi-enabled devices. Sleek design with LED indicator light.',
        'price' => 29.99,
        'regular_price' => 39.99,
        'category' => 'Electronics',
        'sku' => 'WIRELESS-CHARGER',
        'stock' => 120,
    ],
    [
        'name' => 'Phone Screen Protector Pack',
        'description' => 'Pack of 3 tempered glass screen protectors with easy-apply technology. Protects against scratches and drops.',
        'price' => 12.99,
        'regular_price' => 16.99,
        'category' => 'Electronics',
        'sku' => 'SCREEN-PROTECTOR',
        'stock' => 300,
    ],
    [
        'name' => 'Cable Organizer Set',
        'description' => 'Set of 5 cable organizers to keep your workspace tidy and organized. Reusable silicone design.',
        'price' => 14.99,
        'regular_price' => 19.99,
        'category' => 'Office',
        'sku' => 'CABLE-ORGANIZER',
        'stock' => 150,
    ],
    [
        'name' => '4K USB Camera Webcam',
        'description' => '4K resolution USB webcam with built-in microphone for streaming and video calls. Wide 90-degree field of view.',
        'price' => 99.99,
        'regular_price' => 119.99,
        'category' => 'Electronics',
        'sku' => 'WEBCAM-4K',
        'stock' => 45,
    ],
    [
        'name' => 'Bluetooth Speaker Portable',
        'description' => 'Waterproof portable Bluetooth speaker with 12-hour battery and 360° sound. Perfect for outdoor adventures.',
        'price' => 59.99,
        'regular_price' => 79.99,
        'category' => 'Electronics',
        'sku' => 'SPEAKER-BLUETOOTH',
        'stock' => 70,
    ],
];

// Get or create categories
$categories = [];
foreach (['Electronics', 'Office'] as $cat_name) {
    $cat = get_term_by('name', $cat_name, 'product_cat');
    if (!$cat) {
        $cat = wp_insert_term($cat_name, 'product_cat');
        $cat = get_term($cat['term_id'], 'product_cat');
    }
    $categories[$cat_name] = $cat->term_id;
}

// Create products
$created = 0;
$skipped = 0;

foreach ($demo_products as $product_data) {
    // Check if product already exists
    $existing = get_posts([
        'post_type' => 'product',
        'meta_key' => '_sku',
        'meta_value' => $product_data['sku'],
    ]);

    if (!empty($existing)) {
        echo "⏭️  Skipped: {$product_data['name']} (already exists)\n";
        $skipped++;
        continue;
    }

    // Create product
    $product = new WC_Product_Simple();
    $product->set_name($product_data['name']);
    $product->set_description($product_data['description']);
    $product->set_price($product_data['price']);
    $product->set_regular_price($product_data['regular_price']);
    $product->set_sku($product_data['sku']);
    $product->set_stock($product_data['stock']);
    $product->set_manage_stock(true);
    $product->set_status('publish');

    // Add category
    if (isset($categories[$product_data['category']])) {
        $product->set_category_ids([$categories[$product_data['category']]]);
    }

    // Save product
    $product->save();
    $created++;

    echo "✅ Created: {$product_data['name']} (\${$product_data['price']})\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✨ Demo Products Created!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Created: {$created} products\n";
echo "⏭️  Skipped: {$skipped} products (already exist)\n";
echo "📂 Categories: Electronics, Office\n";
echo "💰 Price range: \$12.99 - \$149.99\n";
echo "\n🎉 Now refresh your shopping theme to see products!\n";
