<?php
require_once '/var/www/html/wp-load.php';

if (!function_exists('wc_get_products')) {
    die('❌ WooCommerce is not active');
}

// Product stock mapping
$product_stocks = [
    'HEADPHONES-001' => 50,
    'USB-C-CABLE' => 200,
    'POWERBANK-20K' => 75,
    'LAPTOP-STAND' => 60,
    'DESK-LAMP-LED' => 80,
    'MOUSE-BLACK' => 100,
    'MOUSE-SILVER' => 100,
    'MOUSE-WHITE' => 100,
    'KEYBOARD-BLUE' => 40,
    'KEYBOARD-BROWN' => 40,
    'KEYBOARD-RED' => 40,
    'PROTECTOR-2PACK' => 300,
    'PROTECTOR-3PACK' => 300,
    'PROTECTOR-5PACK' => 200,
];

// Get all products and variations
$args = [
    'post_type' => ['product', 'product_variation'],
    'posts_per_page' => -1,
];

$query = new WP_Query($args);
$updated = 0;

foreach ($query->posts as $post) {
    $product = wc_get_product($post->ID);
    if (!$product) continue;

    $sku = $product->get_sku();

    if (isset($product_stocks[$sku])) {
        $stock = $product_stocks[$sku];

        // Update via direct query for reliability
        update_post_meta($post->ID, '_stock', $stock);
        update_post_meta($post->ID, '_stock_status', 'instock');

        $product = wc_get_product($post->ID); // Refresh
        $product->set_stock($stock);
        $product->set_stock_status('instock');
        $product->save();

        echo "✅ Fixed: " . $product->get_name() . " (SKU: $sku, Stock: $stock)\n";
        $updated++;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✨ Fixed $updated products to IN STOCK!\n";
echo str_repeat("=", 50) . "\n";
