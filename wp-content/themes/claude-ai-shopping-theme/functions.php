<?php
/**
 * Theme Functions
 *
 * @package Claude_AI_Shopping_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CLAUDE_SHOPPING_THEME_VERSION', '1.0.0');
define('CLAUDE_SHOPPING_THEME_DIR', get_template_directory());
define('CLAUDE_SHOPPING_THEME_URL', get_template_directory_uri());

/**
 * Theme Setup
 */
function claude_shopping_setup() {
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    register_nav_menus([
        'primary' => __('Primary Menu', 'claude-ai-shopping'),
        'footer' => __('Footer Menu', 'claude-ai-shopping'),
    ]);
}
add_action('after_setup_theme', 'claude_shopping_setup');

/**
 * Enqueue React App
 */
function claude_shopping_enqueue_scripts() {
    // Only enqueue on frontend
    if (is_admin()) {
        return;
    }

    $react_build_dir = CLAUDE_SHOPPING_THEME_DIR . '/react-app/dist';
    $react_manifest = $react_build_dir . '/manifest.json';

    // Check if React app is built
    if (file_exists($react_manifest)) {
        // React app is built - enqueue compiled files
        $manifest = json_decode(file_get_contents($react_manifest), true);

        // Main JS file - look for index.html entry (Vite's default entry point)
        if (isset($manifest['index.html']['file'])) {
            wp_enqueue_script(
                'claude-shopping-react',
                CLAUDE_SHOPPING_THEME_URL . '/react-app/dist/' . $manifest['index.html']['file'],
                [],
                CLAUDE_SHOPPING_THEME_VERSION,
                true
            );

            // Pass WordPress data to React
            wp_localize_script('claude-shopping-react', 'claudeShoppingTheme', [
                'apiUrl' => rest_url('wc/v3'),
                'siteUrl' => site_url(),
                'nonce' => wp_create_nonce('wp_rest'),
                'restUrl' => rest_url(),
                'cartNonce' => wp_create_nonce('wc_store_api'),
            ]);
        }

        // Main CSS file
        if (isset($manifest['index.html']['css']) && is_array($manifest['index.html']['css'])) {
            foreach ($manifest['index.html']['css'] as $css_file) {
                wp_enqueue_style(
                    'claude-shopping-react-styles',
                    CLAUDE_SHOPPING_THEME_URL . '/react-app/dist/' . $css_file,
                    [],
                    CLAUDE_SHOPPING_THEME_VERSION
                );
            }
        }
    } else {
        // React app not built yet - show setup message
        wp_enqueue_script(
            'claude-shopping-setup-notice',
            CLAUDE_SHOPPING_THEME_URL . '/assets/setup-notice.js',
            [],
            CLAUDE_SHOPPING_THEME_VERSION,
            true
        );
    }

    // Theme stylesheet
    wp_enqueue_style(
        'claude-shopping-theme',
        CLAUDE_SHOPPING_THEME_URL . '/style.css',
        [],
        CLAUDE_SHOPPING_THEME_VERSION
    );

    wp_enqueue_style(
        'claude-shopping-base',
        CLAUDE_SHOPPING_THEME_URL . '/assets/base.css',
        [],
        CLAUDE_SHOPPING_THEME_VERSION
    );
}
add_action('wp_enqueue_scripts', 'claude_shopping_enqueue_scripts');

/**
 * Disable WooCommerce default frontend scripts
 * (React app will handle all frontend)
 */
function claude_shopping_disable_woo_assets() {
    if (function_exists('wp_dequeue_script')) {
        wp_dequeue_script('wc-add-to-cart');
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('woocommerce');
    }
}
add_action('wp_enqueue_scripts', 'claude_shopping_disable_woo_assets', 100);

/**
 * Register custom REST endpoints with proper security
 */
function claude_shopping_rest_api_init() {
    // Register custom REST endpoint for cart operations
    register_rest_route('claude-shopping/v1', '/cart', [
        'methods' => 'POST',
        'callback' => 'claude_shopping_handle_cart',
        'permission_callback' => function(\WP_REST_Request $request) {
            // Verify nonce for CSRF protection
            $nonce = $request->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new \WP_Error('invalid_nonce', 'Nonce verification failed', ['status' => 403]);
            }
            return true;
        },
        'args' => [
            'action' => [
                'required' => true,
                'type' => 'string',
                'enum' => ['add', 'get', 'update', 'remove'],
            ],
        ],
    ]);

    // Register checkout endpoint
    register_rest_route('claude-shopping/v1', '/checkout', [
        'methods' => 'POST',
        'callback' => 'claude_shopping_process_checkout',
        'permission_callback' => function(\WP_REST_Request $request) {
            $nonce = $request->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new \WP_Error('invalid_nonce', 'Nonce verification failed', ['status' => 403]);
            }
            return true;
        },
    ]);
}
add_action('rest_api_init', 'claude_shopping_rest_api_init');

/**
 * Handle cart operations via REST API
 */
function claude_shopping_handle_cart(\WP_REST_Request $request) {
    $action = $request->get_param('action');

    switch ($action) {
        case 'add':
            return claude_shopping_add_to_cart($request);
        case 'update':
            return claude_shopping_update_cart($request);
        case 'remove':
            return claude_shopping_remove_from_cart($request);
        case 'get':
            return claude_shopping_get_cart();
        default:
            return new \WP_Error('invalid_action', 'Invalid cart action', ['status' => 400]);
    }
}

/**
 * Get cart data
 */
function claude_shopping_get_cart() {
    if (!class_exists('WC_Cart')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    $cart = WC()->cart;

    return [
        'items' => array_map(function ($item) {
            return [
                'key' => $item['key'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'total' => $item['line_total'],
                'product_name' => $item['data']->get_name(),
                'product_image' => wp_get_attachment_url($item['data']->get_image_id()),
                'price' => $item['data']->get_price(),
            ];
        }, $cart->get_cart()),
        'total' => $cart->get_cart_total(),
        'count' => $cart->get_cart_contents_count(),
    ];
}

/**
 * Add product to cart
 */
function claude_shopping_add_to_cart(\WP_REST_Request $request) {
    if (!class_exists('WC_Cart')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    $product_id = intval($request->get_param('product_id'));
    $quantity = intval($request->get_param('quantity') ?? 1);

    if (!$product_id) {
        return new \WP_Error('missing_product_id', 'Product ID is required', ['status' => 400]);
    }

    WC()->cart->add_to_cart($product_id, $quantity);

    return claude_shopping_get_cart();
}

/**
 * Update cart item quantity
 */
function claude_shopping_update_cart(\WP_REST_Request $request) {
    if (!class_exists('WC_Cart')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    $cart_item_key = sanitize_text_field($request->get_param('cart_item_key'));
    $quantity = intval($request->get_param('quantity'));

    if (!$cart_item_key) {
        return new \WP_Error('missing_cart_item_key', 'Cart item key is required', ['status' => 400]);
    }

    WC()->cart->set_quantity($cart_item_key, $quantity);

    return claude_shopping_get_cart();
}

/**
 * Remove item from cart
 */
function claude_shopping_remove_from_cart(\WP_REST_Request $request) {
    if (!class_exists('WC_Cart')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    $cart_item_key = sanitize_text_field($request->get_param('cart_item_key'));

    if (!$cart_item_key) {
        return new \WP_Error('missing_cart_item_key', 'Cart item key is required', ['status' => 400]);
    }

    WC()->cart->remove_cart_item($cart_item_key);

    return claude_shopping_get_cart();
}

/**
 * Register image sizes for products
 */
function claude_shopping_register_image_sizes() {
    add_image_size('claude-shopping-product-thumbnail', 300, 300, true);
    add_image_size('claude-shopping-product-single', 600, 600, true);
    add_image_size('claude-shopping-product-grid', 400, 400, true);
}
add_action('after_setup_theme', 'claude_shopping_register_image_sizes');

/**
 * Process checkout and create WooCommerce order
 */
function claude_shopping_process_checkout(\WP_REST_Request $request) {
    if (!class_exists('WC_Cart') || !class_exists('WC_Order')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    $params = $request->get_json_params();

    // Validate required fields
    $required_fields = ['firstName', 'lastName', 'email', 'address', 'city', 'zip', 'country'];
    foreach ($required_fields as $field) {
        if (empty($params[$field])) {
            return new \WP_Error('missing_field', "Missing required field: {$field}", ['status' => 400]);
        }
    }

    try {
        // Get cart
        $cart = WC()->cart;
        if ($cart->is_empty()) {
            return new \WP_Error('empty_cart', 'Cart is empty', ['status' => 400]);
        }

        // Create order
        $order = wc_create_order([
            'status' => 'pending',
        ]);

        // Add order data
        $order->set_billing_first_name(sanitize_text_field($params['firstName']));
        $order->set_billing_last_name(sanitize_text_field($params['lastName']));
        $order->set_billing_email(sanitize_email($params['email']));
        $order->set_billing_phone(sanitize_text_field($params['phone'] ?? ''));
        $order->set_billing_address_1(sanitize_text_field($params['address']));
        $order->set_billing_city(sanitize_text_field($params['city']));
        $order->set_billing_state(sanitize_text_field($params['state'] ?? ''));
        $order->set_billing_postcode(sanitize_text_field($params['zip']));
        $order->set_billing_country(sanitize_text_field($params['country']));

        // Copy billing to shipping
        $order->set_shipping_first_name($order->get_billing_first_name());
        $order->set_shipping_last_name($order->get_billing_last_name());
        $order->set_shipping_address_1($order->get_billing_address_1());
        $order->set_shipping_city($order->get_billing_city());
        $order->set_shipping_state($order->get_billing_state());
        $order->set_shipping_postcode($order->get_billing_postcode());
        $order->set_shipping_country($order->get_billing_country());

        // Add cart items to order
        foreach ($cart->get_cart() as $cart_item) {
            $order->add_product(
                $cart_item['data'],
                $cart_item['quantity']
            );
        }

        // Calculate totals
        $order->calculate_totals();

        // Clear cart
        $cart->empty_cart();

        return [
            'success' => true,
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'message' => 'Order created successfully. You will receive confirmation email shortly.',
        ];
    } catch (\Exception $e) {
        return new \WP_Error('checkout_error', $e->getMessage(), ['status' => 500]);
    }
}

/**
 * Remove WooCommerce default sidebar
 */
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

/**
 * Admin page for generating demo products
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Generate Demo Products',
        'Generate Demo Products',
        'manage_woocommerce',
        'claude-generate-products',
        'claude_shopping_admin_generate_products_page'
    );
});

function claude_shopping_admin_generate_products_page() {
    if (isset($_POST['generate_products']) && check_admin_referer('generate_products_nonce')) {
        $result = claude_shopping_generate_demo_products();
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong>✅ Products Generated!</strong><br>
                Simple Products: <?php echo $result['simple_products_created']; ?><br>
                Variable Products: <?php echo $result['variable_products_created']; ?><br>
                Total: <?php echo $result['total']; ?>
            </p>
        </div>
        <?php
    }
    ?>
    <div class="wrap">
        <h1>Generate Demo Products</h1>

        <div style="background: white; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>Create Sample Products</h2>
            <p>This will create demo WooCommerce products for testing the shopping theme:</p>

            <h3>What Will Be Created:</h3>
            <ul style="list-style: none; padding-left: 0; line-height: 1.8;">
                <li><strong>Simple Products (5):</strong></li>
                <ul style="margin-left: 20px;">
                    <li>✓ Wireless Bluetooth Headphones</li>
                    <li>✓ USB-C Fast Charging Cable</li>
                    <li>✓ Portable Power Bank 20000mAh</li>
                    <li>✓ Premium Laptop Stand</li>
                    <li>✓ Desk Lamp LED - Dimmable</li>
                </ul>

                <li style="margin-top: 15px;"><strong>Variable Products (3):</strong></li>
                <ul style="margin-left: 20px;">
                    <li>✓ Wireless Mouse (Colors: Black, Silver, White)</li>
                    <li>✓ Mechanical Gaming Keyboard (Switches: Blue, Brown, Red)</li>
                    <li>✓ Phone Screen Protector Pack (Packs: 2, 3, 5)</li>
                </ul>

                <li style="margin-top: 15px;"><strong>Total: 13 Products</strong></li>
            </ul>

            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('generate_products_nonce'); ?>
                <button type="submit" name="generate_products" class="button button-primary button-large">
                    ✨ Generate Demo Products
                </button>
            </form>

            <hr style="margin: 30px 0;">

            <h3>After Creating Products:</h3>
            <ol style="padding-left: 20px;">
                <li>Click the button above</li>
                <li>Products will be created in your store</li>
                <li>Go to <a href="<?php echo home_url(); ?>" target="_blank">your storefront</a> and refresh</li>
                <li>You should see all products displayed!</li>
            </ol>
        </div>
    </div>
    <?php
}

/**
 * REST endpoint to generate demo products
 */
function claude_shopping_rest_generate_products() {
    register_rest_route('claude-shopping/v1', '/generate-products', [
        'methods' => 'POST',
        'callback' => 'claude_shopping_generate_demo_products',
        'permission_callback' => function() {
            return current_user_can('manage_woocommerce');
        },
    ]);
}
add_action('rest_api_init', 'claude_shopping_rest_generate_products');

/**
 * Generate simple and variable products
 */
function claude_shopping_generate_demo_products() {
    if (!class_exists('WC_Product_Simple')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    // Simple products
    $simple_products = [
        [
            'name' => 'Wireless Bluetooth Headphones',
            'description' => 'Premium wireless headphones with active noise cancellation, 30-hour battery life, and crystal-clear sound quality.',
            'price' => 129.99,
            'regular_price' => 149.99,
            'category' => 'Electronics',
            'sku' => 'HEADPHONES-001',
            'stock' => 50,
        ],
        [
            'name' => 'USB-C Fast Charging Cable',
            'description' => '6ft premium USB-C charging cable with 100W power delivery. Supports fast charging for laptops, tablets, and phones.',
            'price' => 19.99,
            'regular_price' => 24.99,
            'category' => 'Electronics',
            'sku' => 'USB-C-CABLE',
            'stock' => 200,
        ],
        [
            'name' => 'Portable Power Bank 20000mAh',
            'description' => 'High-capacity power bank with dual USB ports and LED display. Charges your phone 5+ times.',
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

    // Variable products
    $variable_products = [
        [
            'name' => 'Wireless Mouse - Ergonomic',
            'description' => 'Ergonomic wireless mouse with precision tracking and 2-year battery life.',
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
            'description' => 'RGB mechanical keyboard with customizable switches and aluminum frame.',
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
            'description' => 'Pack of tempered glass screen protectors with easy-apply technology.',
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

    $simple_count = 0;
    $variable_count = 0;

    // Create simple products
    foreach ($simple_products as $product_data) {
        $existing = get_posts([
            'post_type' => 'product',
            'meta_key' => '_sku',
            'meta_value' => $product_data['sku'],
        ]);

        if (!empty($existing)) {
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
    }

    // Create variable products
    foreach ($variable_products as $product_data) {
        $existing = get_posts([
            'post_type' => 'product',
            'meta_key' => '_sku',
            'meta_value' => $product_data['sku'],
        ]);

        if (!empty($existing)) {
            continue;
        }

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
        foreach ($product_data['variations'] as $variation_data) {
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
    }

    return [
        'success' => true,
        'simple_products_created' => $simple_count,
        'variable_products_created' => $variable_count,
        'total' => $simple_count + $variable_count,
        'message' => "Created {$simple_count} simple products and {$variable_count} variable products",
    ];
}
