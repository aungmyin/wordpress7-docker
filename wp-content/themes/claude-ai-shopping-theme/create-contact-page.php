<?php
/**
 * Create Contact Page
 * Run this script to create the Contact page in WordPress
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../..
/wp-load.php';

if (!current_user_can('manage_pages')) {
    wp_die('You do not have permission to create pages.');
}

// Check if contact page already exists
$contact_page = get_page_by_path('contact');
if ($contact_page) {
    echo 'Contact page already exists! ID: ' . $contact_page->ID;
    exit;
}

// Create Contact page
$contact_content = '<h2>Get in Touch</h2>
<p>We\'d love to hear from you! Whether you have a question about our products, need support, or just want to say hello, feel free to reach out.</p>

<h3>Business Hours</h3>
<ul>
<li><strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM</li>
<li><strong>Saturday:</strong> 10:00 AM - 4:00 PM</li>
<li><strong>Sunday:</strong> Closed</li>
</ul>

<h3>Contact Information</h3>
<ul>
<li><strong>Email:</strong> support@claudeai.shop</li>
<li><strong>Phone:</strong> +1 (234) 567-890</li>
<li><strong>Address:</strong> 123 Shopping Street, Commerce City, CC 12345</li>
</ul>';

$page_id = wp_insert_post([
    'post_type' => 'page',
    'post_title' => 'Contact',
    'post_name' => 'contact',
    'post_content' => $contact_content,
    'post_status' => 'publish',
]);

if (is_wp_error($page_id)) {
    echo 'Error creating page: ' . $page_id->get_error_message();
} else {
    echo 'Contact page created successfully! Page ID: ' . $page_id;
}
