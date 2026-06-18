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

        // Main JS file
        if (isset($manifest['src/index.jsx']['file'])) {
            wp_enqueue_script(
                'claude-shopping-react',
                CLAUDE_SHOPPING_THEME_URL . '/react-app/dist/' . $manifest['src/index.jsx']['file'],
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
        if (isset($manifest['src/index.css']['file'])) {
            wp_enqueue_style(
                'claude-shopping-react-styles',
                CLAUDE_SHOPPING_THEME_URL . '/react-app/dist/' . $manifest['src/index.css']['file'],
                [],
                CLAUDE_SHOPPING_THEME_VERSION
            );
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
 * Allow WooCommerce REST API access without authentication for public endpoints
 */
function claude_shopping_rest_api_init() {
    // Register custom REST endpoint for cart operations
    register_rest_route('claude-shopping/v1', '/cart', [
        'methods' => 'POST',
        'callback' => 'claude_shopping_handle_cart',
        'permission_callback' => '__return_true',
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
 * Remove WooCommerce default sidebar
 */
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
