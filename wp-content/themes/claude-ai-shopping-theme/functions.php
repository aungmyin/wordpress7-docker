<?php
/**
 * Theme Functions
 *
 * @package Claude_AI_Shopping_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CLAUDE_SHOPPING_THEME_VERSION', '1.0.39');
define('CLAUDE_SHOPPING_THEME_DIR', get_template_directory());
define('CLAUDE_SHOPPING_THEME_URL', get_template_directory_uri());

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

function claude_shopping_enqueue_scripts() {
    if (is_admin()) {
        return;
    }

    $react_build_dir = CLAUDE_SHOPPING_THEME_DIR . '/react-app/dist';
    $react_manifest = $react_build_dir . '/manifest.json';

    if (file_exists($react_manifest)) {
        $manifest = json_decode(file_get_contents($react_manifest), true);

        if (isset($manifest['index.html']['file'])) {
            wp_enqueue_script(
                'claude-shopping-react',
                CLAUDE_SHOPPING_THEME_URL . '/react-app/dist/' . $manifest['index.html']['file'],
                [],
                CLAUDE_SHOPPING_THEME_VERSION,
                true
            );

            wp_localize_script('claude-shopping-react', 'claudeShoppingTheme', [
                'apiUrl' => rest_url('wc/v3'),
                'siteUrl' => site_url(),
                'nonce' => wp_create_nonce('wp_rest'),
                'restUrl' => site_url() . '/index.php/wp-json',
                'cartNonce' => wp_create_nonce('wc_store_api'),
            ]);
        }

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
    }

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
 * Preload Critical Assets for Performance
 */
function claude_shopping_preload_assets() {
    if (is_admin()) {
        return;
    }

    $react_build_dir = CLAUDE_SHOPPING_THEME_DIR . '/react-app/dist';
    $react_manifest = $react_build_dir . '/manifest.json';

    if (file_exists($react_manifest)) {
        $manifest = json_decode(file_get_contents($react_manifest), true);

        // Preload main JS bundle
        if (isset($manifest['index.html']['file'])) {
            echo '<link rel="preload" href="' . esc_url(CLAUDE_SHOPPING_THEME_URL . '/react-app/dist/' . $manifest['index.html']['file']) . '" as="script" />' . "\n";
        }

        // Preload main CSS bundle
        if (isset($manifest['index.html']['css']) && is_array($manifest['index.html']['css'])) {
            foreach ($manifest['index.html']['css'] as $css_file) {
                echo '<link rel="preload" href="' . esc_url(CLAUDE_SHOPPING_THEME_URL . '/react-app/dist/' . $css_file) . '" as="style" />' . "\n";
            }
        }
    }

    // DNS prefetch for external resources
    echo '<link rel="dns-prefetch" href="//fonts.googleapis.com" />' . "\n";
    echo '<link rel="dns-prefetch" href="//maps.googleapis.com" />' . "\n";
}
add_action('wp_head', 'claude_shopping_preload_assets', 1);

/**
 * Enable gzip compression in headers
 */
function claude_shopping_enable_compression() {
    if (!headers_sent()) {
        if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
            ob_start('ob_gzhandler');
        }
    }
}
add_action('wp', 'claude_shopping_enable_compression');

function claude_shopping_disable_woo_assets() {
    if (function_exists('wp_dequeue_script')) {
        wp_dequeue_script('wc-add-to-cart');
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('woocommerce');
    }
}
add_action('wp_enqueue_scripts', 'claude_shopping_disable_woo_assets', 100);

/**
 * REST API for Products, Cart, and Checkout
 */
function claude_shopping_rest_api_init() {
    // Public products endpoint
    register_rest_route('claude-shopping/v1', '/products', [
        'methods' => 'GET',
        'callback' => 'claude_shopping_get_products',
        'permission_callback' => '__return_true',
    ]);

    // Cart endpoint
    register_rest_route('claude-shopping/v1', '/cart', [
        'methods' => 'POST',
        'callback' => 'claude_shopping_handle_cart',
        'permission_callback' => function(\WP_REST_Request $request) {
            $nonce = $request->get_header('X-WP-Nonce');
            return $nonce && wp_verify_nonce($nonce, 'wp_rest');
        },
    ]);

    // Checkout endpoint
    register_rest_route('claude-shopping/v1', '/checkout', [
        'methods' => 'POST',
        'callback' => 'claude_shopping_process_checkout',
        'permission_callback' => function(\WP_REST_Request $request) {
            $nonce = $request->get_header('X-WP-Nonce');
            return $nonce && wp_verify_nonce($nonce, 'wp_rest');
        },
    ]);
}
add_action('rest_api_init', 'claude_shopping_rest_api_init');

function claude_shopping_get_products(\WP_REST_Request $request) {
    if (!class_exists('WC_Product')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    $per_page = intval($request->get_param('per_page')) ?? 12;
    $category = intval($request->get_param('category') ?? 0);
    $min_price = floatval($request->get_param('min_price') ?? 0);
    $max_price = floatval($request->get_param('max_price') ?? 9999999);
    $orderby = sanitize_text_field($request->get_param('orderby') ?? 'date');
    $order = sanitize_text_field($request->get_param('order') ?? 'desc');

    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'orderby' => $orderby,
        'order' => $order,
    ];

    if ($category > 0) {
        $args['tax_query'] = [
            ['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $category],
        ];
    }

    if ($min_price > 0 || $max_price < 9999999) {
        $args['meta_query'] = [
            ['key' => '_price', 'value' => [$min_price, $max_price], 'compare' => 'BETWEEN', 'type' => 'DECIMAL(10, 2)'],
        ];
    }

    $query = new WP_Query($args);
    $products = [];

    foreach ($query->posts as $post) {
        $product = wc_get_product($post->ID);
        if (!$product) continue;

        $in_stock = $product->get_stock_status() === 'instock' && ($product->get_stock_quantity() === null || $product->get_stock_quantity() > 0);

        $product_data = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'description' => wp_strip_all_tags($product->get_description()),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sku' => $product->get_sku(),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity() ?? 0,
            'in_stock' => $in_stock,
            'image' => wp_get_attachment_url($product->get_image_id()),
            'type' => $product->get_type(),
            'permalink' => $product->get_permalink(),
        ];

        if ($product->is_type('variable')) {
            $variations = [];
            foreach ($product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $variations[] = [
                        'id' => $variation->get_id(),
                        'attributes' => $variation->get_attributes(),
                        'price' => $variation->get_price(),
                        'stock_quantity' => $variation->get_stock_quantity(),
                    ];
                }
            }
            $product_data['variations'] = $variations;
        }

        $products[] = $product_data;
    }

    return $products;
}

function claude_shopping_handle_cart(\WP_REST_Request $request) {
    // Ensure WooCommerce is properly loaded
    if (!function_exists('WC') || !function_exists('WC_Session_Handler')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

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

function claude_shopping_get_cart() {
    if (!function_exists('WC')) {
        return ['items' => [], 'total' => '$0', 'count' => 0];
    }

    try {
        $cart = WC()->cart;
        if (!$cart) {
            return ['items' => [], 'total' => '$0', 'count' => 0];
        }

        $cart_items = $cart->get_cart() ?: [];
        return [
            'items' => array_map(function ($item) {
                return [
                    'key' => $item['key'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'total' => wc_price($item['line_total'], ['echo' => false]),
                    'product_name' => $item['data']->get_name(),
                    'product_image' => wp_get_attachment_url($item['data']->get_image_id()),
                    'price' => $item['data']->get_price(),
                ];
            }, $cart_items),
            'total' => $cart->get_cart_total(),
            'count' => $cart->get_cart_contents_count(),
        ];
    } catch (Exception $e) {
        error_log('Cart error: ' . $e->getMessage());
        return ['items' => [], 'total' => '$0', 'count' => 0];
    }
}

function claude_shopping_add_to_cart(\WP_REST_Request $request) {
    if (!function_exists('WC')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    try {
        $product_id = intval($request->get_param('product_id'));
        $quantity = intval($request->get_param('quantity') ?? 1);
        $variation_id = intval($request->get_param('variation_id') ?? 0);

        if (!$product_id) {
            return new \WP_Error('missing_product_id', 'Product ID is required', ['status' => 400]);
        }

        // Add to WooCommerce cart
        $cart = WC()->cart;
        if ($cart) {
            $result = $cart->add_to_cart($product_id, $quantity, $variation_id);
            if (!$result) {
                error_log("Add to cart failed for product $product_id");
            }
        }

        return claude_shopping_get_cart();
    } catch (Exception $e) {
        error_log('Add to cart error: ' . $e->getMessage());
        return new \WP_Error('add_failed', 'Failed to add product to cart: ' . $e->getMessage(), ['status' => 500]);
    }
}

function claude_shopping_update_cart(\WP_REST_Request $request) {
    if (!function_exists('WC')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    try {
        $cart_item_key = sanitize_text_field($request->get_param('cart_item_key'));
        $quantity = intval($request->get_param('quantity'));

        if (!$cart_item_key) {
            return new \WP_Error('missing_cart_item_key', 'Cart item key is required', ['status' => 400]);
        }

        $cart = WC()->cart;
        if ($cart) {
            $cart->set_quantity($cart_item_key, $quantity);
        }

        return claude_shopping_get_cart();
    } catch (Exception $e) {
        error_log('Update cart error: ' . $e->getMessage());
        return new \WP_Error('update_failed', 'Failed to update cart', ['status' => 500]);
    }
}

function claude_shopping_remove_from_cart(\WP_REST_Request $request) {
    if (!function_exists('WC')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    try {
        $cart_item_key = sanitize_text_field($request->get_param('cart_item_key'));

        if (!$cart_item_key) {
            return new \WP_Error('missing_cart_item_key', 'Cart item key is required', ['status' => 400]);
        }

        $cart = WC()->cart;
        if ($cart) {
            $cart->remove_cart_item($cart_item_key);
        }

        return claude_shopping_get_cart();
    } catch (Exception $e) {
        error_log('Remove from cart error: ' . $e->getMessage());
        return new \WP_Error('remove_failed', 'Failed to remove from cart', ['status' => 500]);
    }
}

function claude_shopping_process_checkout(\WP_REST_Request $request) {
    if (!class_exists('WC_Cart') || !class_exists('WC_Order')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }
    $params = $request->get_json_params();
    $required_fields = ['firstName', 'lastName', 'email', 'address', 'city', 'zip', 'country'];
    foreach ($required_fields as $field) {
        if (empty($params[$field])) {
            return new \WP_Error('missing_field', "Missing required field: {$field}", ['status' => 400]);
        }
    }
    try {
        $cart = WC()->cart;
        if ($cart->is_empty()) {
            return new \WP_Error('empty_cart', 'Cart is empty', ['status' => 400]);
        }
        $order = wc_create_order(['status' => 'pending']);
        $order->set_billing_first_name(sanitize_text_field($params['firstName']));
        $order->set_billing_last_name(sanitize_text_field($params['lastName']));
        $order->set_billing_email(sanitize_email($params['email']));
        $order->set_billing_phone(sanitize_text_field($params['phone'] ?? ''));
        $order->set_billing_address_1(sanitize_text_field($params['address']));
        $order->set_billing_city(sanitize_text_field($params['city']));
        $order->set_billing_state(sanitize_text_field($params['state'] ?? ''));
        $order->set_billing_postcode(sanitize_text_field($params['zip']));
        $order->set_billing_country(sanitize_text_field($params['country']));
        $order->set_shipping_first_name($order->get_billing_first_name());
        $order->set_shipping_last_name($order->get_billing_last_name());
        $order->set_shipping_address_1($order->get_billing_address_1());
        $order->set_shipping_city($order->get_billing_city());
        $order->set_shipping_state($order->get_billing_state());
        $order->set_shipping_postcode($order->get_billing_postcode());
        $order->set_shipping_country($order->get_billing_country());
        foreach ($cart->get_cart() as $cart_item) {
            $order->add_product($cart_item['data'], $cart_item['quantity']);
        }
        $order->calculate_totals();
        $cart->empty_cart();
        return [
            'success' => true,
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'message' => 'Order created successfully',
        ];
    } catch (\Exception $e) {
        return new \WP_Error('checkout_error', $e->getMessage(), ['status' => 500]);
    }
}

function claude_shopping_register_image_sizes() {
    add_image_size('claude-shopping-product-thumbnail', 300, 300, true);
    add_image_size('claude-shopping-product-single', 600, 600, true);
    add_image_size('claude-shopping-product-grid', 400, 400, true);
}
add_action('after_setup_theme', 'claude_shopping_register_image_sizes');

remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

/**
 * REST Endpoint to Get Page Content by Slug
 */
function claude_shopping_register_page_endpoint() {
    register_rest_route('claude-shopping/v1', '/page/(?P<slug>[a-zA-Z0-9-]+)', [
        'methods' => 'GET',
        'callback' => 'claude_shopping_get_page_content',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'claude_shopping_register_page_endpoint');

function claude_shopping_get_page_content($request) {
    $slug = $request->get_param('slug');

    $page = get_page_by_path($slug, OBJECT, 'page');

    if (!$page) {
        return new \WP_Error('page_not_found', 'Page not found', ['status' => 404]);
    }

    return [
        'id' => $page->ID,
        'title' => get_the_title($page->ID),
        'content' => wp_kses_post($page->post_content),
        'excerpt' => wp_kses_post($page->post_excerpt),
    ];
}

/**
 * REST Endpoint to Get Product Categories
 */
function claude_shopping_register_categories_endpoint() {
    register_rest_route('claude-shopping/v1', '/categories', [
        'methods' => 'GET',
        'callback' => 'claude_shopping_get_categories',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'claude_shopping_register_categories_endpoint');

function claude_shopping_get_categories() {
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'orderby' => 'name',
    ]);

    if (is_wp_error($categories)) {
        return new \WP_Error('categories_error', 'Error fetching categories', ['status' => 500]);
    }

    return array_map(function ($cat) {
        return [
            'id' => $cat->term_id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'description' => $cat->description,
            'count' => $cat->count,
        ];
    }, $categories);
}

/**
 * REST Endpoint to Get Single Product by ID
 */
function claude_shopping_register_product_endpoint() {
    register_rest_route('claude-shopping/v1', '/product/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'claude_shopping_get_single_product',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'claude_shopping_register_product_endpoint');

function claude_shopping_get_single_product($request) {
    $product_id = intval($request->get_param('id'));
    $product = wc_get_product($product_id);

    if (!$product) {
        return new \WP_Error('product_not_found', 'Product not found', ['status' => 404]);
    }

    $in_stock = $product->get_stock_status() === 'instock' && ($product->get_stock_quantity() === null || $product->get_stock_quantity() > 0);

    $product_data = [
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'description' => wp_kses_post($product->get_description()),
        'short_description' => wp_kses_post($product->get_short_description()),
        'price' => $product->get_price(),
        'regular_price' => $product->get_regular_price(),
        'sale_price' => $product->get_sale_price(),
        'sku' => $product->get_sku(),
        'stock_status' => $product->get_stock_status(),
        'stock_quantity' => $product->get_stock_quantity() ?? 0,
        'in_stock' => $in_stock,
        'image' => wp_get_attachment_url($product->get_image_id()),
        'type' => $product->get_type(),
        'permalink' => $product->get_permalink(),
        'categories' => array_map(function($cat) {
            return [
                'id' => $cat->term_id,
                'name' => $cat->name,
            ];
        }, $product->get_category_ids() ? array_map('get_term', $product->get_category_ids()) : []),
    ];

    if ($product->is_type('variable')) {
        $variations = [];
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation) {
                $variations[] = [
                    'id' => $variation->get_id(),
                    'attributes' => $variation->get_attributes(),
                    'price' => $variation->get_price(),
                    'stock_quantity' => $variation->get_stock_quantity(),
                ];
            }
        }
        $product_data['variations'] = $variations;
    }

    return $product_data;
}

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
            <p>This will create demo WooCommerce products for testing:</p>
            <ul style="list-style: none; padding-left: 0; line-height: 1.8;">
                <li><strong>Simple Products (5):</strong> Headphones, Cable, Power Bank, Laptop Stand, Lamp</li>
                <li><strong>Variable Products (3):</strong> Mouse (colors), Keyboard (switches), Screen Protector (packs)</li>
                <li><strong>Total: 13 Products</strong></li>
            </ul>
            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('generate_products_nonce'); ?>
                <button type="submit" name="generate_products" class="button button-primary button-large">
                    ✨ Generate Demo Products
                </button>
            </form>
        </div>
    </div>
    <?php
}

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

function claude_shopping_generate_demo_products() {
    if (!class_exists('WC_Product_Simple')) {
        return new \WP_Error('woocommerce_not_active', 'WooCommerce is not active', ['status' => 500]);
    }

    $simple_products = [
        ['name' => 'Wireless Bluetooth Headphones', 'price' => 129.99, 'regular_price' => 149.99, 'category' => 'Electronics', 'sku' => 'HEADPHONES-001', 'stock' => 50],
        ['name' => 'USB-C Fast Charging Cable', 'price' => 19.99, 'regular_price' => 24.99, 'category' => 'Electronics', 'sku' => 'USB-C-CABLE', 'stock' => 200],
        ['name' => 'Portable Power Bank 20000mAh', 'price' => 34.99, 'regular_price' => 44.99, 'category' => 'Electronics', 'sku' => 'POWERBANK-20K', 'stock' => 75],
        ['name' => 'Premium Laptop Stand', 'price' => 49.99, 'regular_price' => 59.99, 'category' => 'Office', 'sku' => 'LAPTOP-STAND', 'stock' => 60],
        ['name' => 'Desk Lamp LED - Dimmable', 'price' => 39.99, 'regular_price' => 49.99, 'category' => 'Office', 'sku' => 'DESK-LAMP-LED', 'stock' => 80],
    ];

    $variable_products = [
        ['name' => 'Wireless Mouse - Ergonomic', 'category' => 'Electronics', 'sku' => 'MOUSE-ERGONOMIC', 'attributes' => ['color' => ['Black', 'Silver', 'White']], 'variations' => [['sku' => 'MOUSE-BLACK', 'price' => 24.99, 'stock' => 100], ['sku' => 'MOUSE-SILVER', 'price' => 24.99, 'stock' => 100], ['sku' => 'MOUSE-WHITE', 'price' => 24.99, 'stock' => 100]]],
        ['name' => 'Mechanical Gaming Keyboard', 'category' => 'Electronics', 'sku' => 'KEYBOARD-GAMING', 'attributes' => ['switch_type' => ['Blue', 'Brown', 'Red']], 'variations' => [['sku' => 'KEYBOARD-BLUE', 'price' => 89.99, 'stock' => 40], ['sku' => 'KEYBOARD-BROWN', 'price' => 89.99, 'stock' => 40], ['sku' => 'KEYBOARD-RED', 'price' => 89.99, 'stock' => 40]]],
    ];

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
    foreach ($simple_products as $pd) {
        $existing = get_posts(['post_type' => 'product', 'meta_key' => '_sku', 'meta_value' => $pd['sku']]);
        if (!empty($existing)) continue;
        $product = new WC_Product_Simple();
        $product->set_name($pd['name']);
        $product->set_price($pd['price']);
        $product->set_regular_price($pd['regular_price']);
        $product->set_sku($pd['sku']);
        $product->set_stock($pd['stock']);
        $product->set_manage_stock(true);
        $product->set_stock_status('instock');
        $product->set_status('publish');
        $product->set_category_ids([$categories[$pd['category']]]);
        $product->save();
        $simple_count++;
    }

    $variable_count = 0;
    foreach ($variable_products as $pd) {
        $existing = get_posts(['post_type' => 'product', 'meta_key' => '_sku', 'meta_value' => $pd['sku']]);
        if (!empty($existing)) continue;
        $product = new WC_Product_Variable();
        $product->set_name($pd['name']);
        $product->set_sku($pd['sku']);
        $product->set_status('publish');
        $product->set_category_ids([$categories[$pd['category']]]);
        $attributes = [];
        foreach ($pd['attributes'] as $attr_name => $attr_values) {
            $attr_id = wc_create_attribute(['name' => ucfirst(str_replace('_', ' ', $attr_name)), 'slug' => $attr_name, 'type' => 'select', 'orderby' => 'menu_order', 'has_archives' => false]);
            if (!is_wp_error($attr_id)) {
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
        }
        $product->set_attributes($attributes);
        $product->save();
        foreach ($pd['variations'] as $var_data) {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product->get_id());
            $variation->set_sku($var_data['sku']);
            $variation->set_price($var_data['price']);
            $variation->set_regular_price($var_data['price']);
            $variation->set_stock($var_data['stock']);
            $variation->set_manage_stock(true);
            $variation->set_stock_status('instock');
            $variation->set_status('publish');
            $variation->save();
        }
        $variable_count++;
    }

    return ['success' => true, 'simple_products_created' => $simple_count, 'variable_products_created' => $variable_count, 'total' => $simple_count + $variable_count];
}

/**
 * Register Meta Fields for Contact & About Pages
 */
function claude_shopping_register_page_meta() {
    // Contact page fields
    register_meta('post', 'contact_email', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_meta('post', 'contact_phone', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_meta('post', 'contact_address', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);

    // About page fields
    register_meta('post', 'about_hero_title', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_meta('post', 'about_hero_subtitle', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_meta('post', 'about_mission', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_meta('post', 'about_vision', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_meta('post', 'about_values', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
}
add_action('init', 'claude_shopping_register_page_meta');

/**
 * Add Meta Box for Contact Page Fields
 */
function claude_shopping_add_contact_meta_box() {
    add_meta_box(
        'contact_info',
        'Contact Information',
        'claude_shopping_contact_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'claude_shopping_add_contact_meta_box');

function claude_shopping_contact_meta_box_callback($post) {
    $contact_email = get_post_meta($post->ID, 'contact_email', true);
    $contact_phone = get_post_meta($post->ID, 'contact_phone', true);
    $contact_address = get_post_meta($post->ID, 'contact_address', true);

    wp_nonce_field('claude_contact_meta_nonce', 'claude_contact_meta_nonce');
    ?>
    <style>
        .contact-meta-field {
            margin-bottom: 15px;
        }
        .contact-meta-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .contact-meta-field input,
        .contact-meta-field textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: Arial, sans-serif;
        }
        .contact-meta-field textarea {
            resize: vertical;
            min-height: 80px;
        }
        .contact-meta-notice {
            background: #f0f7ff;
            border-left: 4px solid #0073aa;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #333;
        }
    </style>

    <div class="contact-meta-notice">
        <strong>ℹ️ Contact Info:</strong> Edit the fields below to update the contact information displayed on the Contact page (3-column layout).
    </div>

    <div class="contact-meta-field">
        <label for="contact_email">Email Address</label>
        <input
            type="email"
            id="contact_email"
            name="contact_email"
            value="<?php echo esc_attr($contact_email); ?>"
            placeholder="support@example.com"
        />
    </div>

    <div class="contact-meta-field">
        <label for="contact_phone">Phone Number</label>
        <input
            type="tel"
            id="contact_phone"
            name="contact_phone"
            value="<?php echo esc_attr($contact_phone); ?>"
            placeholder="+1 (234) 567-890"
        />
    </div>

    <div class="contact-meta-field">
        <label for="contact_address">Address</label>
        <textarea
            id="contact_address"
            name="contact_address"
            placeholder="123 Shopping Street, Commerce City, CC 12345"
        ><?php echo esc_textarea($contact_address); ?></textarea>
    </div>
    <?php
}

/**
 * Save Contact Meta Fields
 */
function claude_shopping_save_contact_meta($post_id) {
    if (!isset($_POST['claude_contact_meta_nonce']) || !wp_verify_nonce($_POST['claude_contact_meta_nonce'], 'claude_contact_meta_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_page', $post_id)) {
        return;
    }

    if (isset($_POST['contact_email'])) {
        update_post_meta($post_id, 'contact_email', sanitize_email($_POST['contact_email']));
    }
    if (isset($_POST['contact_phone'])) {
        update_post_meta($post_id, 'contact_phone', sanitize_text_field($_POST['contact_phone']));
    }
    if (isset($_POST['contact_address'])) {
        update_post_meta($post_id, 'contact_address', sanitize_text_field($_POST['contact_address']));
    }
}
add_action('save_post', 'claude_shopping_save_contact_meta');

/**
 * Add Meta Box for About Page Fields
 */
function claude_shopping_add_about_meta_box() {
    add_meta_box(
        'about_info',
        'About Page Sections',
        'claude_shopping_about_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'claude_shopping_add_about_meta_box');

function claude_shopping_about_meta_box_callback($post) {
    $about_hero_title = get_post_meta($post->ID, 'about_hero_title', true);
    $about_hero_subtitle = get_post_meta($post->ID, 'about_hero_subtitle', true);
    $about_mission = get_post_meta($post->ID, 'about_mission', true);
    $about_vision = get_post_meta($post->ID, 'about_vision', true);
    $about_values = get_post_meta($post->ID, 'about_values', true);

    wp_nonce_field('claude_about_meta_nonce', 'claude_about_meta_nonce');
    ?>
    <style>
        .about-meta-field {
            margin-bottom: 15px;
        }
        .about-meta-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .about-meta-field input,
        .about-meta-field textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: Arial, sans-serif;
        }
        .about-meta-field textarea {
            resize: vertical;
            min-height: 80px;
        }
        .about-meta-notice {
            background: #f0f7ff;
            border-left: 4px solid #0073aa;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #333;
        }
    </style>

    <div class="about-meta-notice">
        <strong>ℹ️ About Page Layout:</strong> Edit the sections below to create a nice about page with hero section, mission, vision, and values displayed in a 3-column layout.
    </div>

    <div class="about-meta-field">
        <label for="about_hero_title">Hero Title</label>
        <input
            type="text"
            id="about_hero_title"
            name="about_hero_title"
            value="<?php echo esc_attr($about_hero_title); ?>"
            placeholder="About Our Company"
        />
    </div>

    <div class="about-meta-field">
        <label for="about_hero_subtitle">Hero Subtitle</label>
        <textarea
            id="about_hero_subtitle"
            name="about_hero_subtitle"
            placeholder="A brief description of your company"
        ><?php echo esc_textarea($about_hero_subtitle); ?></textarea>
    </div>

    <div class="about-meta-field">
        <label for="about_mission">Our Mission</label>
        <textarea
            id="about_mission"
            name="about_mission"
            placeholder="What is your company's mission?"
        ><?php echo esc_textarea($about_mission); ?></textarea>
    </div>

    <div class="about-meta-field">
        <label for="about_vision">Our Vision</label>
        <textarea
            id="about_vision"
            name="about_vision"
            placeholder="What is your company's vision for the future?"
        ><?php echo esc_textarea($about_vision); ?></textarea>
    </div>

    <div class="about-meta-field">
        <label for="about_values">Our Values</label>
        <textarea
            id="about_values"
            name="about_values"
            placeholder="What values does your company hold?"
        ><?php echo esc_textarea($about_values); ?></textarea>
    </div>
    <?php
}

/**
 * Save About Page Meta Fields
 */
function claude_shopping_save_about_meta($post_id) {
    if (!isset($_POST['claude_about_meta_nonce']) || !wp_verify_nonce($_POST['claude_about_meta_nonce'], 'claude_about_meta_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_page', $post_id)) {
        return;
    }

    if (isset($_POST['about_hero_title'])) {
        update_post_meta($post_id, 'about_hero_title', sanitize_text_field($_POST['about_hero_title']));
    }
    if (isset($_POST['about_hero_subtitle'])) {
        update_post_meta($post_id, 'about_hero_subtitle', sanitize_textarea_field($_POST['about_hero_subtitle']));
    }
    if (isset($_POST['about_mission'])) {
        update_post_meta($post_id, 'about_mission', sanitize_textarea_field($_POST['about_mission']));
    }
    if (isset($_POST['about_vision'])) {
        update_post_meta($post_id, 'about_vision', sanitize_textarea_field($_POST['about_vision']));
    }
    if (isset($_POST['about_values'])) {
        update_post_meta($post_id, 'about_values', sanitize_textarea_field($_POST['about_values']));
    }
}
add_action('save_post', 'claude_shopping_save_about_meta');

/**
 * REST Endpoint to Handle Contact Form Submissions
 */
function claude_shopping_register_contact_endpoint() {
    register_rest_route('claude-shopping/v1', '/contact', [
        'methods' => 'POST',
        'callback' => 'claude_shopping_handle_contact_form',
        'permission_callback' => function($request) {
            $nonce = $request->get_header('X-WP-Nonce');
            return wp_verify_nonce($nonce, 'wp_rest');
        },
    ]);
}
add_action('rest_api_init', 'claude_shopping_register_contact_endpoint');

function claude_shopping_handle_contact_form($request) {
    $body = $request->get_json_params();

    $name = sanitize_text_field($body['name'] ?? '');
    $email = sanitize_email($body['email'] ?? '');
    $phone = sanitize_text_field($body['phone'] ?? '');
    $subject = sanitize_text_field($body['subject'] ?? '');
    $message = sanitize_textarea_field($body['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        return new \WP_Error('missing_fields', 'Please fill in all required fields', ['status' => 400]);
    }

    if (!is_email($email)) {
        return new \WP_Error('invalid_email', 'Please provide a valid email address', ['status' => 400]);
    }

    $admin_email = get_option('admin_email');
    $site_name = get_option('blogname');

    $email_subject = "New Contact Form Enquiry - {$subject}";

    $email_body = "New contact form submission:\n\n";
    $email_body .= "Name: {$name}\n";
    $email_body .= "Email: {$email}\n";
    if (!empty($phone)) {
        $email_body .= "Phone: {$phone}\n";
    }
    $email_body .= "Subject: {$subject}\n\n";
    $email_body .= "Message:\n{$message}\n\n";
    $email_body .= "---\n";
    $email_body .= "This message was sent from {$site_name}";

    $headers = ["Content-Type: text/plain; charset=UTF-8", "From: {$name} <{$email}>"];

    $mail_sent = wp_mail($admin_email, $email_subject, $email_body, $headers);

    if (!$mail_sent) {
        return new \WP_Error('email_failed', 'Failed to send your message. Please try again later.', ['status' => 500]);
    }

    return [
        'success' => true,
        'message' => 'Your enquiry has been sent successfully. We will get back to you soon.',
    ];
}
