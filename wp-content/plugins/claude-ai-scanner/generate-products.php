<?php
/**
 * Generate Simple and Variable Products for WooCommerce
 *
 * Access this file via: http://localhost:8080/wp-content/plugins/claude-ai-scanner/generate-products.php
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../wp-load.php';

if (!function_exists('wc_get_products')) {
    die('❌ WooCommerce is not installed or activated.');
}

// Simple products data
$simple_products = [
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
];

// Variable products (with variations)
$variable_products = [
    [
        'name' => 'Wireless Mouse - Ergonomic',
        'description' => 'Ergonomic wireless mouse with precision tracking and 2-year battery life. Reduces wrist strain during long work sessions.',
        'category' => 'Electronics',
        'sku' => 'MOUSE-ERGONOMIC',
        'attributes' => [
            'color' => ['Black', 'Silver', 'White'],
        ],
        'variations' => [
            [
                'sku' => 'MOUSE-BLACK',
                'price' => 24.99,
                'regular_price' => 29.99,
                'stock' => 100,
                'attribute' => ['color' => 'Black'],
            ],
            [
                'sku' => 'MOUSE-SILVER',
                'price' => 24.99,
                'regular_price' => 29.99,
                'stock' => 100,
                'attribute' => ['color' => 'Silver'],
            ],
            [
                'sku' => 'MOUSE-WHITE',
                'price' => 24.99,
                'regular_price' => 29.99,
                'stock' => 100,
                'attribute' => ['color' => 'White'],
            ],
        ],
    ],
    [
        'name' => 'Mechanical Gaming Keyboard',
        'description' => 'RGB mechanical keyboard with customizable switches and aluminum frame. Perfect for gaming and professional typing.',
        'category' => 'Electronics',
        'sku' => 'KEYBOARD-GAMING',
        'attributes' => [
            'switch_type' => ['Blue', 'Brown', 'Red'],
        ],
        'variations' => [
            [
                'sku' => 'KEYBOARD-BLUE',
                'price' => 89.99,
                'regular_price' => 109.99,
                'stock' => 40,
                'attribute' => ['switch_type' => 'Blue'],
            ],
            [
                'sku' => 'KEYBOARD-BROWN',
                'price' => 89.99,
                'regular_price' => 109.99,
                'stock' => 40,
                'attribute' => ['switch_type' => 'Brown'],
            ],
            [
                'sku' => 'KEYBOARD-RED',
                'price' => 89.99,
                'regular_price' => 109.99,
                'stock' => 40,
                'attribute' => ['switch_type' => 'Red'],
            ],
        ],
    ],
    [
        'name' => 'Wireless Charger Pad',
        'description' => '15W fast wireless charging pad compatible with all Qi-enabled devices. Sleek design with LED indicator light.',
        'category' => 'Electronics',
        'sku' => 'WIRELESS-CHARGER',
        'attributes' => [
            'wattage' => ['15W', '10W'],
        ],
        'variations' => [
            [
                'sku' => 'CHARGER-15W',
                'price' => 29.99,
                'regular_price' => 39.99,
                'stock' => 120,
                'attribute' => ['wattage' => '15W'],
            ],
            [
                'sku' => 'CHARGER-10W',
                'price' => 19.99,
                'regular_price' => 24.99,
                'stock' => 150,
                'attribute' => ['wattage' => '10W'],
            ],
        ],
    ],
    [
        'name' => 'Phone Screen Protector Pack',
        'description' => 'Pack of tempered glass screen protectors with easy-apply technology. Protects against scratches and drops.',
        'category' => 'Electronics',
        'sku' => 'SCREEN-PROTECTOR',
        'attributes' => [
            'quantity' => ['2 Pack', '3 Pack', '5 Pack'],
        ],
        'variations' => [
            [
                'sku' => 'PROTECTOR-2PACK',
                'price' => 9.99,
                'regular_price' => 12.99,
                'stock' => 300,
                'attribute' => ['quantity' => '2 Pack'],
            ],
            [
                'sku' => 'PROTECTOR-3PACK',
                'price' => 12.99,
                'regular_price' => 16.99,
                'stock' => 300,
                'attribute' => ['quantity' => '3 Pack'],
            ],
            [
                'sku' => 'PROTECTOR-5PACK',
                'price' => 19.99,
                'regular_price' => 24.99,
                'stock' => 200,
                'attribute' => ['quantity' => '5 Pack'],
            ],
        ],
    ],
];

// Get or create categories
$categories = [];
foreach (['Electronics', 'Office'] as $cat_name) {
    $cat = get_term_by('name', $cat_name, 'product_cat');
    if (!$cat) {
        $term = wp_insert_term($cat_name, 'product_cat');
        $cat = get_term($term['term_id'], 'product_cat');
    }
    $categories[$cat_name] = $cat->term_id;
}

// Create simple products
echo "<h2>Creating Simple Products...</h2>\n";
$simple_count = 0;
foreach ($simple_products as $product_data) {
    $existing = get_posts([
        'post_type' => 'product',
        'meta_key' => '_sku',
        'meta_value' => $product_data['sku'],
    ]);

    if (!empty($existing)) {
        echo "⏭️  Skipped: {$product_data['name']} (already exists)<br>\n";
        continue;
    }

    $product = new WC_Product_Simple();
    $product->set_name($product_data['name']);
    $product->set_description($product_data['description']);
    $product->set_price($product_data['price']);
    $product->set_regular_price($product_data['regular_price']);
    $product->set_sku($product_data['sku']);
    $product->set_stock($product_data['stock']);
    $product->set_manage_stock(true);
    $product->set_status('publish');

    if (isset($categories[$product_data['category']])) {
        $product->set_category_ids([$categories[$product_data['category']]]);
    }

    $product->save();
    $simple_count++;
    echo "✅ Created: {$product_data['name']} (\${$product_data['price']})<br>\n";
}

// Create variable products
echo "<h2>Creating Variable Products...</h2>\n";
$variable_count = 0;
foreach ($variable_products as $product_data) {
    $existing = get_posts([
        'post_type' => 'product',
        'meta_key' => '_sku',
        'meta_value' => $product_data['sku'],
    ]);

    if (!empty($existing)) {
        echo "⏭️  Skipped: {$product_data['name']} (already exists)<br>\n";
        continue;
    }

    // Create variable product
    $product = new WC_Product_Variable();
    $product->set_name($product_data['name']);
    $product->set_description($product_data['description']);
    $product->set_sku($product_data['sku']);
    $product->set_status('publish');

    if (isset($categories[$product_data['category']])) {
        $product->set_category_ids([$categories[$product_data['category']]]);
    }

    // Register attributes
    $attributes = [];
    foreach ($product_data['attributes'] as $attr_name => $attr_values) {
        $attr_id = wc_create_attribute([
            'name' => ucfirst($attr_name),
            'slug' => $attr_name,
            'type' => 'select',
            'orderby' => 'menu_order',
            'has_archives' => false,
        ]);

        // Add attribute terms
        foreach ($attr_values as $value) {
            wp_insert_term($value, 'pa_' . $attr_name);
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id($attr_id);
        $attribute->set_name('pa_' . $attr_name);
        $attribute->set_options($attr_values);
        $attribute->set_visible(true);
        $attribute->set_variation(true);

        $attributes[] = $attribute;
    }

    $product->set_attributes($attributes);
    $product->save();

    // Create variations
    foreach ($product_data['variations'] as $variation_data) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($product->get_id());
        $variation->set_sku($variation_data['sku']);
        $variation->set_price($variation_data['price']);
        $variation->set_regular_price($variation_data['regular_price']);
        $variation->set_stock($variation_data['stock']);
        $variation->set_manage_stock(true);
        $variation->set_status('publish');

        // Set attributes
        $attr_data = $variation_data['attribute'];
        foreach ($attr_data as $attr_name => $attr_value) {
            $variation->set_attributes(['pa_' . $attr_name => $attr_value]);
        }

        $variation->save();
    }

    $variable_count++;
    echo "✅ Created: {$product_data['name']} (with " . count($product_data['variations']) . " variations)<br>\n";
}

echo "<hr style='margin: 30px 0;'>\n";
echo "<h2 style='color: green;'>✨ Products Created!</h2>\n";
echo "<p><strong>Simple Products:</strong> {$simple_count} created</p>\n";
echo "<p><strong>Variable Products:</strong> {$variable_count} created</p>\n";
echo "<p><strong>Total:</strong> " . ($simple_count + $variable_count) . " products</p>\n";
echo "<p style='margin-top: 20px;'><a href='http://localhost:8080' style='padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 5px;'>✓ View Products on Store</a></p>\n";
?>
