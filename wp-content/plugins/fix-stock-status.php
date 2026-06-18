<?php
require_once '/var/www/html/wp-load.php';

if (!function_exists('wc_get_products')) {
    die('❌ WooCommerce is not active');
}

// Get all products
$args = [
    'post_type' => 'product',
    'posts_per_page' => -1,
];

$query = new WP_Query($args);
$updated = 0;

foreach ($query->posts as $post) {
    $product = wc_get_product($post->ID);
    if (!$product) continue;

    // Set stock status to instock if stock is available
    if ($product->get_stock_quantity() > 0) {
        $product->set_stock_status('instock');
        $product->save();
        $updated++;
        echo "✅ Fixed stock for: " . $product->get_name() . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✨ Fixed $updated products!\n";
echo str_repeat("=", 50) . "\n";
