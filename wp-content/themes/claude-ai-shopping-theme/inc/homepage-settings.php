<?php
/**
 * Homepage ACF-like Settings Fields
 * Manages all homepage content through WordPress admin
 */

/**
 * Register REST API endpoint for homepage settings
 */
function claude_shopping_register_home_settings() {
    register_rest_route('claude-shopping/v1', '/home-settings', array(
        'methods' => array('GET', 'POST'),
        'callback' => 'claude_shopping_home_settings_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'claude_shopping_register_home_settings');

/**
 * Homepage settings REST callback
 */
function claude_shopping_home_settings_callback($request) {
    if ($request->get_method() === 'POST') {
        if (!current_user_can('manage_options')) {
            return new WP_Error('unauthorized', 'Only admins can update settings', array('status' => 403));
        }

        $data = $request->get_json_params();

        foreach ($data as $key => $value) {
            update_option('claude_home_' . $key, sanitize_text_field($value));
        }

        return array('success' => true, 'message' => 'Settings updated');
    }

    // GET - Return all settings with defaults
    return array(
        'hero_title' => get_option('claude_home_hero_title', 'Welcome to Claude AI Shopping'),
        'hero_subtitle' => get_option('claude_home_hero_subtitle', 'Discover amazing products at great prices'),
        'popular_section_title' => get_option('claude_home_popular_section_title', '🔥 Best Sellers'),
        'popular_section_subtitle' => get_option('claude_home_popular_section_subtitle', 'Most loved products by our customers'),
        'discount_title' => get_option('claude_home_discount_title', 'Limited Time Offer!'),
        'discount_subtitle' => get_option('claude_home_discount_subtitle', 'Get up to 40% OFF on selected items'),
        'testimonial_section_title' => get_option('claude_home_testimonial_section_title', 'What Our Customers Say'),
        'trust_section_title' => get_option('claude_home_trust_section_title', 'Trusted by Thousands'),
        'faq_section_title' => get_option('claude_home_faq_section_title', 'Frequently Asked Questions'),
    );
}

/**
 * Add admin menu for homepage settings
 */
function claude_shopping_add_admin_menu() {
    add_submenu_page(
        'themes.php',
        'Homepage Settings',
        'Homepage Settings',
        'manage_options',
        'claude-home-settings',
        'claude_shopping_homepage_settings_page'
    );
}
add_action('admin_menu', 'claude_shopping_add_admin_menu');

/**
 * Render homepage settings admin page
 */
function claude_shopping_homepage_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>🏠 Homepage Settings</h1>
        <p>Customize all homepage sections here. Changes appear instantly on the frontend.</p>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>Hero Section</h2>
            <table class="form-table">
                <tr>
                    <th><label for="hero_title">Hero Title</label></th>
                    <td><input type="text" id="hero_title" name="hero_title" value="<?php echo esc_attr(get_option('claude_home_hero_title', 'Welcome to Claude AI Shopping')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="hero_subtitle">Hero Subtitle</label></th>
                    <td><input type="text" id="hero_subtitle" name="hero_subtitle" value="<?php echo esc_attr(get_option('claude_home_hero_subtitle', 'Discover amazing products at great prices')); ?>" class="regular-text" /></td>
                </tr>
            </table>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>Popular Products Section</h2>
            <table class="form-table">
                <tr>
                    <th><label for="popular_section_title">Title</label></th>
                    <td><input type="text" id="popular_section_title" name="popular_section_title" value="<?php echo esc_attr(get_option('claude_home_popular_section_title', '🔥 Best Sellers')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="popular_section_subtitle">Subtitle</label></th>
                    <td><input type="text" id="popular_section_subtitle" name="popular_section_subtitle" value="<?php echo esc_attr(get_option('claude_home_popular_section_subtitle', 'Most loved products by our customers')); ?>" class="regular-text" /></td>
                </tr>
            </table>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>Discount Section</h2>
            <table class="form-table">
                <tr>
                    <th><label for="discount_title">Title</label></th>
                    <td><input type="text" id="discount_title" name="discount_title" value="<?php echo esc_attr(get_option('claude_home_discount_title', 'Limited Time Offer!')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="discount_subtitle">Subtitle</label></th>
                    <td><input type="text" id="discount_subtitle" name="discount_subtitle" value="<?php echo esc_attr(get_option('claude_home_discount_subtitle', 'Get up to 40% OFF on selected items')); ?>" class="regular-text" /></td>
                </tr>
            </table>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>Testimonials Section</h2>
            <table class="form-table">
                <tr>
                    <th><label for="testimonial_section_title">Title</label></th>
                    <td><input type="text" id="testimonial_section_title" name="testimonial_section_title" value="<?php echo esc_attr(get_option('claude_home_testimonial_section_title', 'What Our Customers Say')); ?>" class="regular-text" /></td>
                </tr>
            </table>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>Trust Badges Section</h2>
            <table class="form-table">
                <tr>
                    <th><label for="trust_section_title">Title</label></th>
                    <td><input type="text" id="trust_section_title" name="trust_section_title" value="<?php echo esc_attr(get_option('claude_home_trust_section_title', 'Trusted by Thousands')); ?>" class="regular-text" /></td>
                </tr>
            </table>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>FAQ Section</h2>
            <table class="form-table">
                <tr>
                    <th><label for="faq_section_title">Title</label></th>
                    <td><input type="text" id="faq_section_title" name="faq_section_title" value="<?php echo esc_attr(get_option('claude_home_faq_section_title', 'Frequently Asked Questions')); ?>" class="regular-text" /></td>
                </tr>
            </table>
        </div>

        <?php submit_button('Save All Settings', 'primary', 'submit', true); ?>
    </div>

    <script>
        document.querySelector('input[type="submit"]').addEventListener('click', async (e) => {
            e.preventDefault();
            const data = {};
            document.querySelectorAll('input[name]').forEach(input => {
                data[input.name] = input.value;
            });

            try {
                const response = await fetch('/index.php/wp-json/claude-shopping/v1/home-settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    alert('✅ Homepage settings saved successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + result.message);
                }
            } catch (err) {
                alert('❌ Error saving settings: ' + err.message);
            }
        });
    </script>
    <?php
}
