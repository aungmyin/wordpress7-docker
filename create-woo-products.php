<?php
require_once '/var/www/html/wp-load.php';

if (!function_exists('wc_get_products')) {
    die('❌ WooCommerce is not active');
}

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

// Simple products
$simple_products = [
    [
        'name' => 'Wireless Bluetooth Headphones',
        'description' => 'Premium wireless headphones with active noise cancellation',
        'price' => 129.99,
        'regular_price' => 149.99,
        'category' => 'Electronics',
        'sku' => 'HEADPHONES-001',
        'stock' => 50,
    ],
    [
        'name' => 'USB-C Fast Charging Cable',
        'description' => '6ft premium USB-C charging cable with 100W power delivery',
        'price' => 19.99,
        'regular_price' => 24.99,
        'category' => 'Electronics',
        'sku' => 'USB-C-CABLE',
        'stock' => 200,
    ],
    [
        'name' => 'Portable Power Bank 20000mAh',
        'description' => 'High-capacity power bank with dual USB ports',
        'price' => 34.99,
        'regular_price' => 44.99,
        'category' => 'Electronics',
        'sku' => 'POWERBANK-20K',
        'stock' => 75,
    ],
    [
        'name' => 'Premium Laptop Stand',
        'description' => 'Adjustable aluminum laptop stand for ergonomic setup',
        'price' => 49.99,
        'regular_price' => 59.99,
        'category' => 'Office',
        'sku' => 'LAPTOP-STAND',
        'stock' => 60,
    ],
    [
        'name' => 'Desk Lamp LED - Dimmable',
        'description' => 'LED desk lamp with 5 brightness levels',
        'price' => 39.99,
        'regular_price' => 49.99,
        'category' => 'Office',
        'sku' => 'DESK-LAMP-LED',
        'stock' => 80,
    ],
];

// Variable products
$variable_products = [
    [
        'name' => 'Wireless Mouse - Ergonomic',
        'description' => 'Ergonomic wireless mouse with precision tracking',
        'category' => 'Electronics',
        'sku' => 'MOUSE-ERGONOMIC',
        'attributes' => ['color' => ['Black', 'Silver', 'White']],
        'variations' => [
            ['sku' => 'MOUSE-BLACK', 'price' => 24.99, 'regular_price' => 29.99, 'stock' => 100, 'attribute' => ['color' => 'Black']],
            ['sku' => 'MOUSE-SILVER', 'price' => 24.99, 'regular_price' => 29.99, 'stock' => 100, 'attribute' => ['color' => 'Silver']],
            ['sku' => 'MOUSE-WHITE', 'price' => 24.99, 'regular_price' => 29.99, 'stock' => 100, 'attribute' => ['color' => 'White']],
        ],
    ],
    [
        'name' => 'Mechanical Gaming Keyboard',
        'description' => 'RGB mechanical keyboard with customizable switches',
        'category' => 'Electronics',
        'sku' => 'KEYBOARD-GAMING',
        'attributes' => ['switch_type' => ['Blue', 'Brown', 'Red']],
        'variations' => [
            ['sku' => 'KEYBOARD-BLUE', 'price' => 89.99, 'regular_price' => 109.99, 'stock' => 40, 'attribute' => ['switch_type' => 'Blue']],
            ['sku' => 'KEYBOARD-BROWN', 'price' => 89.99, 'regular_price' => 109.99, 'stock' => 40, 'attribute' => ['switch_type' => 'Brown']],
            ['sku' => 'KEYBOARD-RED', 'price' => 89.99, 'regular_price' => 109.99, 'stock' => 40, 'attribute' => ['switch_type' => 'Red']],
        ],
    ],
    [
        'name' => 'Phone Screen Protector Pack',
        'description' => 'Pack of tempered glass screen protectors',
        'category' => 'Electronics',
        'sku' => 'SCREEN-PROTECTOR',
        'attributes' => ['quantity' => ['2 Pack', '3 Pack', '5 Pack']],
        'variations' => [
            ['sku' => 'PROTECTOR-2PACK', 'price' => 9.99, 'regular_price' => 12.99, 'stock' => 300, 'attribute' => ['quantity' => '2 Pack']],
            ['sku' => 'PROTECTOR-3PACK', 'price' => 12.99, 'regular_price' => 16.99, 'stock' => 300, 'attribute' => ['quantity' => '3 Pack']],
            ['sku' => 'PROTECTOR-5PACK', 'price' => 19.99, 'regular_price' => 24.99, 'stock' => 200, 'attribute' => ['quantity' => '5 Pack']],
        ],
    ],
];

echo "=== Creating Simple Products ===\n";
$simple_count = 0;
foreach ($simple_products as $pd) {
    $existing = get_posts(['post_type' => 'product', 'meta_key' => '_sku', 'meta_value' => $pd['sku']]);
    if (!empty($existing)) {
        echo "⏭️  Skipped: " . $pd['name'] . " (exists)\n";
        continue;
    }

    $product = new WC_Product_Simple();
    $product->set_name($pd['name']);
    $product->set_description($pd['description']);
    $product->set_price($pd['price']);
    $product->set_regular_price($pd['regular_price']);
    $product->set_sku($pd['sku']);
    $product->set_stock($pd['stock']);
    $product->set_manage_stock(true);
    $product->set_status('publish');
    $product->set_category_ids([$categories[$pd['category']]]);
    $product->save();
    $simple_count++;
    echo "✅ Created: " . $pd['name'] . "\n";
}

echo "\n=== Creating Variable Products ===\n";
$variable_count = 0;
foreach ($variable_products as $pd) {
    $existing = get_posts(['post_type' => 'product', 'meta_key' => '_sku', 'meta_value' => $pd['sku']]);
    if (!empty($existing)) {
        echo "⏭️  Skipped: " . $pd['name'] . " (exists)\n";
        continue;
    }

    $product = new WC_Product_Variable();
    $product->set_name($pd['name']);
    $product->set_description($pd['description']);
    $product->set_sku($pd['sku']);
    $product->set_status('publish');
    $product->set_category_ids([$categories[$pd['category']]]);

    // Register attributes
    $attributes = [];
    foreach ($pd['attributes'] as $attr_name => $attr_values) {
        $attr = wc_get_attribute(wc_attribute_taxonomy_to_name($attr_name));
        if (!$attr) {
            $attr_id = wc_create_attribute([
                'name' => ucfirst(str_replace('_', ' ', $attr_name)),
                'slug' => $attr_name,
                'type' => 'select',
                'orderby' => 'menu_order',
                'has_archives' => false,
            ]);
        } else {
            $attr_id = $attr->get_id();
        }

        foreach ($attr_values as $value) {
            wp_insert_term($value, 'pa_' . $attr_name, ['description' => '']);
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
    foreach ($pd['variations'] as $variation_data) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($product->get_id());
        $variation->set_sku($variation_data['sku']);
        $variation->set_price($variation_data['price']);
        $variation->set_regular_price($variation_data['regular_price']);
        $variation->set_stock($variation_data['stock']);
        $variation->set_manage_stock(true);
        $variation->set_status('publish');

        foreach ($variation_data['attribute'] as $attr_name => $attr_value) {
            $variation->set_attributes(['pa_' . $attr_name => $attr_value]);
        }

        $variation->save();
    }

    $variable_count++;
    echo "✅ Created: " . $pd['name'] . " (3 variations)\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✨ Products Created Successfully!\n";
echo "Simple Products: $simple_count\n";
echo "Variable Products: $variable_count\n";
echo "Total: " . ($simple_count + $variable_count) . " products\n";
echo str_repeat("=", 50) . "\n";
