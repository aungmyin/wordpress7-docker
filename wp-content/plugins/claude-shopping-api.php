<?php
/**
 * Plugin Name: Claude Shopping REST API
 * Plugin URI: https://github.com/aungmyin/wordpress7-docker
 * Description: REST API endpoints for Claude AI Shopping Theme
 * Version: 1.0.0
 * Author: Aung My In
 * License: GPL v2 or later
 *
 * @package Claude_Shopping_API
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST routes
 */
function claude_shopping_register_routes() {
    // Products endpoint - PUBLIC
    register_rest_route('claude-shopping/v1', '/products', [
        'methods' => 'GET',
        'callback' => 'claude_shopping_get_products',
        'permission_callback' => '__return_true',
    ]);

    // Cart endpoint - PRIVATE (nonce required)
    register_rest_route('claude-shopping/v1', '/cart', [
        'methods' => 'POST',
        'callback' => 'claude_shopping_handle_cart',
        'permission_callback' => function(\WP_REST_Request $request) {
            $nonce = $request->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new \WP_Error('invalid_nonce', 'Nonce verification failed', ['status' => 403]);
            }
            return true;
        },
    ]);

    // Checkout endpoint - PRIVATE (nonce required)
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
add_action('rest_api_init', 'claude_shopping_register_routes');

/**
 * Get products
 */
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
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category,
            ],
        ];
    }

    if ($min_price > 0 || $max_price < 9999999) {
        $args['meta_query'] = [
            [
                'key' => '_price',
                'value' => [$min_price, $max_price],
                'compare' => 'BETWEEN',
                'type' => 'DECIMAL(10, 2)',
            ],
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

/**
 * Handle cart operations
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
 * Get cart
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
 * Add to cart
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
 * Update cart
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
 * Remove from cart
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
 * Process checkout
 */
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
