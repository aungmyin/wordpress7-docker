<?php
/**
 * Demo Products Generator for Claude AI Shopping Theme
 *
 * Add this to your WordPress admin and visit:
 * yoursite.com/wp-admin/admin.php?page=demo-products
 *
 * This will create sample WooCommerce products for testing the theme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hook to add admin page
add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Create Demo Products',
        'Create Demo Products',
        'manage_woocommerce',
        'demo-products',
        'claude_demo_products_page'
    );
});

// Demo products page
function claude_demo_products_page() {
    ?>
    <div class="wrap">
        <h1>Claude AI Shopping - Demo Products Generator</h1>

        <?php
        if (isset($_POST['create_demo_products']) && check_admin_referer('demo_products_nonce')) {
            if (current_user_can('manage_woocommerce')) {
                $created = claude_create_demo_products();
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo sprintf(__('Created %d demo products!', 'woocommerce'), $created); ?></p>
                </div>
                <?php
            }
        }
        ?>

        <div style="background: white; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>Generate Sample Products</h2>
            <p>This will create 12 sample WooCommerce products for testing the Claude AI Shopping theme.</p>

            <form method="post">
                <?php wp_nonce_field('demo_products_nonce'); ?>
                <button type="submit" name="create_demo_products" class="button button-primary button-large">
                    ✨ Create Demo Products
                </button>
            </form>

            <hr style="margin: 30px 0;">

            <h3>What Will Be Created:</h3>
            <ul style="list-style: none; padding-left: 0;">
                <li>✓ 12 sample products across 3 categories</li>
                <li>✓ Products from $9.99 to $299.99</li>
                <li>✓ Product descriptions and details</li>
                <li>✓ Random product images (placeholder)</li>
                <li>✓ Inventory tracking enabled</li>
                <li>✓ Products visible on storefront</li>
            </ul>

            <h3 style="margin-top: 30px;">After Creating Products:</h3>
            <ol style="padding-left: 20px;">
                <li>Click "Create Demo Products" button</li>
                <li>Go to <a href="<?php echo admin_url('admin.php?page=claude-ai-scanner'); ?>">AI Scanner → Dashboard</a></li>
                <li>Your products will now display on the storefront</li>
                <li>Test filtering, sorting, and shopping cart</li>
            </ol>
        </div>
    </div>
    <?php
}

// Create demo products
function claude_create_demo_products() {
    $demo_products = [
        [
            'name' => 'Wireless Bluetooth Headphones',
            'description' => 'Premium wireless headphones with active noise cancellation, 30-hour battery life, and crystal-clear sound quality.',
            'price' => 129.99,
            'category' => 'Electronics',
            'sku' => 'HEADPHONES-001',
            'stock' => 50,
        ],
        [
            'name' => 'USB-C Fast Charging Cable',
            'description' => '6ft premium USB-C charging cable with 100W power delivery. Works with all USB-C devices.',
            'price' => 19.99,
            'category' => 'Electronics',
            'sku' => 'USB-C-CABLE',
            'stock' => 200,
        ],
        [
            'name' => 'Portable Power Bank 20000mAh',
            'description' => 'High-capacity power bank with dual USB ports and LED display. Charges your phone 5+ times.',
            'price' => 34.99,
            'category' => 'Electronics',
            'sku' => 'POWERBANK-20K',
            'stock' => 75,
        ],
        [
            'name' => 'Wireless Mouse - Ergonomic',
            'description' => 'Ergonomic wireless mouse with precision tracking and 2-year battery life.',
            'price' => 24.99,
            'category' => 'Electronics',
            'sku' => 'MOUSE-ERGONOMIC',
            'stock' => 100,
        ],
        [
            'name' => 'Mechanical Gaming Keyboard',
            'description' => 'RGB mechanical keyboard with customizable switches and aluminum frame.',
            'price' => 89.99,
            'category' => 'Electronics',
            'sku' => 'KEYBOARD-GAMING',
            'stock' => 40,
        ],
        [
            'name' => 'Premium Laptop Stand',
            'description' => 'Adjustable aluminum laptop stand for ergonomic workspace setup.',
            'price' => 49.99,
            'category' => 'Office',
            'sku' => 'LAPTOP-STAND',
            'stock' => 60,
        ],
        [
            'name' => 'Desk Lamp LED - Dimmable',
            'description' => 'LED desk lamp with 5 brightness levels and USB charging port.',
            'price' => 39.99,
            'category' => 'Office',
            'sku' => 'DESK-LAMP-LED',
            'stock' => 80,
        ],
        [
            'name' => 'Wireless Charger Pad',
            'description' => '15W fast wireless charging pad compatible with all Qi-enabled devices.',
            'price' => 29.99,
            'category' => 'Electronics',
            'sku' => 'WIRELESS-CHARGER',
            'stock' => 120,
        ],
        [
            'name' => 'Phone Screen Protector Pack',
            'description' => 'Pack of 3 tempered glass screen protectors with easy-apply technology.',
            'price' => 12.99,
            'category' => 'Electronics',
            'sku' => 'SCREEN-PROTECTOR',
            'stock' => 300,
        ],
        [
            'name' => 'Cable Organizer Set',
            'description' => 'Set of 5 cable organizers to keep your workspace tidy and organized.',
            'price' => 14.99,
            'category' => 'Office',
            'sku' => 'CABLE-ORGANIZER',
            'stock' => 150,
        ],
        [
            'name' => '4K USB Camera Webcam',
            'description' => '4K resolution USB webcam with built-in microphone for streaming and video calls.',
            'price' => 99.99,
            'category' => 'Electronics',
            'sku' => 'WEBCAM-4K',
            'stock' => 45,
        ],
        [
            'name' => 'Bluetooth Speaker Portable',
            'description' => 'Waterproof portable Bluetooth speaker with 12-hour battery and 360° sound.',
            'price' => 59.99,
            'category' => 'Electronics',
            'sku' => 'SPEAKER-BLUETOOTH',
            'stock' => 70,
        ],
    ];

    $created = 0;

    if (!class_exists('WC_Product_Simple')) {
        return 0;
    }

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
    foreach ($demo_products as $product_data) {
        // Check if product already exists
        $existing = get_posts([
            'post_type' => 'product',
            'meta_key' => '_sku',
            'meta_value' => $product_data['sku'],
        ]);

        if (!empty($existing)) {
            continue;
        }

        // Create product
        $product = new WC_Product_Simple();
        $product->set_name($product_data['name']);
        $product->set_description($product_data['description']);
        $product->set_price($product_data['price']);
        $product->set_regular_price($product_data['price']);
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
    }

    return $created;
}
