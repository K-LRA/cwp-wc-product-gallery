<?php
/*
Plugin Name: ✅ WooCommerce Product Gallery
Plugin URI: #
Description: A simple, filterable, and configurable WooCommerce product gallery.
Author: conner
Author URI: #
version: 1.0.0
*/

/**
 * Prevent direct access to this file.
 */
if (!defined('ABSPATH')) {
    die;
}

include_once plugin_dir_path(__FILE__) . 'util/cwp-utils.php';

add_shortcode('cwp_wc_product_gallery', 'cwp_register_wc_product_gallery');

function cwp_register_wc_product_gallery($atts)
{

    $atts = shortcode_atts(
        array(
            'accent_color_hex' => '#ff0000',
            'display_in_stock' => true,
            'display_oos' => true,
            'display_backorder' => true,
            'display_rating' => true,
            'display_stock_status' => true,
            'display_badges' => true,
            'display_review_count' => true,
            'display_virtual' => true,
            'display_purchase_count' => true,
            'display_emojis' => true,
        ),
        $atts
    );

    foreach ($atts as $key => $value) {
        if ($value === 'false' || $value === '0') {
            $atts[$key] = false;
        } elseif ($value === 'true' || $value === '1') {
            $atts[$key] = true;
        }
    }

    extract($atts);

    ob_start();
    include plugin_dir_path(__FILE__) . 'includes/cwp-wc-product-gallery.php';
    return ob_get_clean();
}
