<?php
/**
 * Main Template File
 *
 * @package Claude_AI_Shopping_Theme
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <div id="root"></div>

    <!-- Fallback for when JavaScript is disabled -->
    <noscript>
        <div class="no-js">
            <h1><?php bloginfo('name'); ?></h1>
            <p><?php esc_html_e('This website requires JavaScript to be enabled.', 'claude-ai-shopping'); ?></p>
            <p><a href="https://www.enable-javascript.com/" target="_blank"><?php esc_html_e('Learn how to enable JavaScript', 'claude-ai-shopping'); ?></a></p>
        </div>
    </noscript>

    <?php wp_footer(); ?>
</body>
</html>
