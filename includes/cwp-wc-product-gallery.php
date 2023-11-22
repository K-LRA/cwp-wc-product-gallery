<?php

/**
 * Prevent direct access to this file.
 */
if (!defined('ABSPATH')) {
    die;
}

$is_woocommerce_active = ws_is_plugin_active('woocommerce');

/**
 * If WooCommerce is not active, stop.
 */
if (!$is_woocommerce_active) {
    return;
}

/**
 * Get all published products.
 */
$products = wc_get_products(array(
    'status' => 'publish',
    'limit' => '-1',
));

/**
 * If there are no products, stop.
 */
if (empty($products)) {
    echo 'No products found.';
    return;
}

/**
 * Get all categories that have products in them.
 */
$categories = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => true,
]);

/**
 * If there are no categories, stop.
 */
if (empty($categories)) {
    echo 'No categories found.';
    return;
}

/**
 * Build the category classes for a product we're displaying. These will be used for filtering.
 */
function build_html_category_classes($product)
{
    $category_ids = $product->get_category_ids();
    $classes = "";
    foreach ($category_ids as $id) {
        $classes .= "category-id-{$id} ";
    }
    return $classes;
}

/**
 * Creates boilerplate html code for product badges.
 */
function build_html_badge($text, $title)
{
    return "<span class='ws_wc-product-badge' title='$title'>$text</span>";
}

?>

<!-- Create our product search bar -->
<div class="ws_wc-product-search-bar-container">
    <input id="ws_wc-product-search-bar" type="text" placeholder="Filter products">
</div>
<div class="ws_wc-product-filter-buttons">
    <?php
    /**
     * Create our filter buttons.
     */
    echo "<button class='ws_wc-product-filter-button category-id--1 all ws_active' onclick='wsFilterByCategoryId(-1);'>All</button>";
    foreach ($categories as $category) {
        $name = $category->name;
        $slug = $category->slug;
        $count = $category->count;
        $category_id = $category->term_id;
        $category_url = esc_url(get_category_link($category->term_id));

        echo "<button class='ws_wc-product-filter-button category-id-$category_id $slug ' onclick='wsFilterByCategoryId($category_id)'>$name</button>";
    }
    ?>
</div>
<div class="ws_wc-products">
    <?php
    /**
     * Populate all products on the page.
     */
    foreach ($products as $product) {
        $stock_status = $product->get_stock_status();

        if (!$display_in_stock) {
            if ($stock_status == 'instock')
                continue;
        }
        if (!$display_oos) {
            if ($stock_status == 'outofstock')
                continue;
        }
        if (!$display_backorder) {
            if ($stock_status == 'onbackorder')
                continue;
        }
        $title = $product->get_title();
        $image = $product->get_image();
        $price = ws_string_round_trailing_0($product->get_price_html());
        $is_on_sale = $product->is_on_sale();
        $product_classes = "ws_wc-product " . build_html_category_classes($product) . ($is_on_sale ? "ws_wc-sale" : "ws_wc-regular");

        $id = $product->get_id();
        $url = get_permalink($id);
        $short_description = $product->get_short_description();
        echo "<a href='$url' class='$product_classes'" . (!empty($short_description) ? "title='$short_description'" : "") . ">";
        echo $image;
        if ($is_on_sale) {
            echo "<div class='ws_wc-sale-badge'>Sale</div>";
        }
        echo "<div class='ws_wc-product-info'>";
        echo "<div class='ws_wc-meta'>";
        echo "<p class='ws_wc-title'>$title</p>";
        echo "<p class='ws_wc-price'>$price</p> ";
        echo "</div>";
        echo "<div class='ws_wc-product-badges'>";
        if ($display_badges) {

            if ($display_stock_status) {

                if ($stock_status === 'outofstock')
                    echo build_html_badge("Out of stock", 'This product is out of stock.');
                else if ($stock_status === 'onbackorder')
                    echo build_html_badge("Back-order", 'This product is on back-order.');
                else if ($stock_status === 'instock') {
                    echo build_html_badge("In stock", 'This product is in stock.');

                    if ($product->is_virtual()) {
                        if ($display_virtual)
                            echo build_html_badge($display_emojis ? "💾" : "Virtual", 'This is a virtual product.');
                    }
                }
            }
            if ($display_purchase_count) {
                $total_sales = $product->get_total_sales();
                if ($total_sales > 0) {
                    echo build_html_badge($total_sales . ($display_emojis ? ' 🛒' : ' sales'), "This product has been purchased $total_sales " . ($total_sales == 1 ? "time" : "times") . ".");
                }
            }
            if ($display_rating) {
                $rating = $product->get_average_rating();
                if ($rating > 0) {
                    $round_rating = $rating == 5 || $rating == 4 || $rating == 3 || $rating == 2 || $rating == 1;
                    $final_rating = number_format($rating, $round_rating ? 0 : 1);
                    echo build_html_badge($final_rating . ($display_emojis ? ' ❤️' : ' stars'), "This product receives an average rating of $final_rating.");
                }
            }
            if ($display_review_count) {
                $review_count = $product->get_review_count();

                if ($review_count > 0) {
                    echo build_html_badge($review_count . ($display_emojis ? ' ✍🏻' : ' reviews'), 'This product has received ' . $review_count . ' ' . ($review_count == 1 ? 'review' : 'reviews') . '.');
                }
            }
        }
        echo "</div>";
        echo "</div>";
        echo "</a>";
    }
    ?>
</div>

<script>
    /**
     * Our products container
     */
    const container = document.getElementsByClassName('ws_wc-products');
    /**
     * Our collection of products
     */
    const products = document.getElementsByClassName('ws_wc-product');
    /**
     * Our search bar
     */
    const search = document.getElementById("ws_wc-product-search-bar");
    /**
     * Our collection of filter buttons
     */
    const buttons = document.getElementsByClassName('ws_wc-product-filter-button');

    /**
     * Our active category ID
     */
    let categoryId = -1;

    document.addEventListener("DOMContentLoaded", function() {
        /**
         * Filter products based on search query
         */
        function filterProducts(searchQuery) {
            for (let i = 0; i < products.length; i++) {
                const product = products[i];

                if (product == null)
                    continue;

                const productName = product.querySelector(".ws_wc-title").textContent.toLowerCase();

                if (productName.includes(searchQuery.toLowerCase())) {
                    product.classList.remove("ws_hide");
                } else {
                    product.classList.add("ws_hide");
                }
            }
        }

        /**
         * Add listener to search bar
         */
        if (search != null) {
            search.addEventListener("input", function() {
                const searchQuery = this.value;
                filterProducts(searchQuery);
            });
        }
    });

    /**
     * Filter products by category id.
     */
    function wsFilterByCategoryId(id) {
        categoryId = id;

        /**
         * Hide products based on filter clicked.
         */
        for (let i = 0; i < products.length; i++) {
            const product = products[i];

            if (product == null)
                continue;

            const visible = categoryId == -1 || product.classList.contains('category-id-' + categoryId);
            product.classList.toggle("ws_hide", !visible);
        }

        /**
         * Add active class to clicked button, remove from the rest.
         */
        for (let i = 0; i < buttons.length; i++) {
            const button = buttons[i];

            if (button == null)
                continue;

            const active = button.classList.contains('category-id-' + categoryId);
            button.classList.toggle("ws_active", active);
        }
    }
</script>

<style>
    :root {
        <?php
        $ws_wc_accent_colour = $accent_color_hex;

        echo "--ws_wc-accent-colour: $ws_wc_accent_colour;";
        echo "--ws_wc-accent-colour-heavy: " . $ws_wc_accent_colour . "90;";
        echo "--ws_wc-accent-colour-primary: " . $ws_wc_accent_colour . "75;";
        echo "--ws_wc-accent-colour-secondary: " . $ws_wc_accent_colour . "50;";
        echo "--ws_wc-accent-colour-accent: " . $ws_wc_accent_colour . "25;";
        echo "--ws_wc_accent-font-colour: #" . ws_get_contrast_colour($ws_wc_accent_colour) . ";";
        ?>
    }

    .ws_wc-products {
        display: grid;
        color: var(--ws_wc_accent-font-colour);
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1px;

        & a:hover {
            box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
        }

        &:has(a:hover) a:not(:hover) {
            opacity: 0.65;
            scale: 0.9;
        }
    }

    .ws_wc-product {
        color: var(--ws_wc_accent-font-colour);
        display: block;
        position: relative;
        transition: all 100ms ease;

        &.ws_hide {
            display: none;
        }

        & img {
            object-fit: cover;
            width: 100%;
            height: 100%;
        }
    }

    .ws_wc-product-info {
        min-height: 82px;
        position: absolute;
        bottom: 0;
        width: 100%;
        backdrop-filter: blur(2px);
        background-color: var(--ws_wc-accent-colour-primary);
    }

    .ws_wc-sale:hover:before,
    .ws_wc-sale:hover:after {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        width: calc(100% + 4px);
        height: calc(100% + 4px);
        --border-size: 3px;
        --border-angle: 0turn;
        background-image:
            conic-gradient(from var(--border-angle), var(--ws_wc-accent-colour-secondary), var(--ws_wc-accent-colour-accent), var(--ws_wc-accent-colour-secondary)),
            conic-gradient(from var(--border-angle), transparent 75%, var(--ws_wc-accent-colour-secondary), var(--ws_wc-accent-colour-accent));
        background-size: calc(100% - (var(--border-size) * 2)) calc(100% - (var(--border-size) * 2)), cover;
        background-position: center center;
        background-repeat: no-repeat;
        z-index: -1;
        animation: ws_wc-sale-border-anim 3.5s linear infinite;
    }

    @keyframes ws_wc-sale-border-anim {
        to {
            --border-angle: 1turn;
        }
    }

    @property --border-angle {
        syntax: "<angle>";
        inherits: true;
        initial-value: 0turn;
    }

    .ws_wc-sale .ws_wc-meta .ws_wc-price {
        background-color: var(--ws_wc-accent-colour-primary);
        margin-left: 3px;
        border-radius: 3px;
        padding: 0 0.5rem;
    }

    .ws_wc-meta {
        display: flex;
        justify-content: space-between;
        padding: 0.25rem 0.5rem;

        & .ws_wc-title {
            overflow: hidden;
            font-weight: bold;
        }

        & .ws_wc-price {
            margin-top: 0.25rem;
        }

        & p {
            padding: 0;
            display: flex;
            align-items: center;
        }

        & bdi {
            &:first-child {
                margin-right: 2px;
            }

            &:last-child {
                margin-left: 2px;
            }
        }
    }

    .ws_wc-sale-badge {
        margin: 0.5rem;
        padding: 0.25rem 0.75rem;
        backdrop-filter: blur(2px);
        background-color: var(--ws_wc-accent-colour-heavy);

        position: absolute;
        top: 0;
        right: 0;
    }

    .ws_wc-product-badges {
        padding: 0.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        float: right;
        margin-bottom: 0.25rem;
    }

    .ws_wc-product-badge {
        background-color: var(--ws_wc-accent-colour-accent);
        border: 1px solid var(--ws_wc-accent-colour-secondary);
        padding: 0 0.5rem;
        z-index: 1;
    }

    .ws_wc-product-filter-buttons {
        display: flex;
        margin: 0 auto;
        gap: 1rem;
        padding-bottom: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .ws_wc-product-search-bar-container {
        width: 100%;
        display: flex;
        justify-content: flex-end;

        & input {
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--ws_wc-accent-colour-secondary);
            background-color: var(--ws_wc-accent-colour-primary);
            width: 100%;
        }

        & input::placeholder {
            color: var(--ws_wc_accent-font-colour);
        }

        & input:focus {
            color: var(--ws_wc_accent-font-colour);
            border: 1px solid var(--ws_wc-accent-colour);
            background-color: var(--ws_wc-accent-colour-heavy);
        }
    }

    .ws_wc-product-filter-button {
        color: var(--ws_wc_accent-font-colour);
        border: 0;
        border: 1px solid var(--ws_wc-accent-colour-heavy);
        background-color: var(--ws_wc-accent-colour-primary);
        padding: 0.5rem 1rem;
        transition: 100ms ease-in-out;
        cursor: pointer;
        flex: 1;
        width: fit-content;
        text-shadow: 0px 0px 15px rgba(0, 0, 0, 0.3);
    }

    .ws_wc-product-filter-button:hover {
        background-color: var(--ws_wc-accent-colour-secondary);
    }

    .ws_wc-product-filter-button.ws_active {
        transform: translatey(2px);
        background-color: var(--ws_wc-accent-colour-heavy);
    }
</style>
