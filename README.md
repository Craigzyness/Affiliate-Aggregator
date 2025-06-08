
# Home Haven Hub - AI Affiliate Website

## Project Overview

Home Haven Hub is a proof-of-concept affiliate website focused on home and garden products. It's designed to demonstrate how AI, specifically OpenAI's GPT models, can be leveraged to generate engaging and informative product review content. The site aims to provide users with valuable insights and recommendations, helping them make informed purchasing decisions while generating affiliate revenue. This project also serves as a comprehensive guide for individuals looking to recreate a similar AI-powered affiliate website.

## Aims and Goals

*   To create an AI-powered affiliate website specializing in home and garden products.
*   To leverage AI (OpenAI) for generating high-quality product review content.
*   To provide users with valuable, informative, and unbiased reviews.
*   To help users make informed purchasing decisions.
*   To generate affiliate revenue through carefully selected product recommendations.
*   To serve as a step-by-step guide for recreating such a site, including plugin development, theme customization, and content strategy.

## Prerequisites

*   A self-hosted WordPress installation (latest version recommended).
*   A domain name.
*   Web hosting (e.g., Bluehost, SiteGround, Kinsta).
*   Astra WordPress Theme (free version).
*   An OpenAI API Key (obtainable from [https://platform.openai.com/signup/](https://platform.openai.com/signup/)).
*   Basic knowledge of WordPress, including theme and plugin installation.
*   (Optional but Recommended) Familiarity with PHP, CSS, and HTML for deeper customization.

## Step-by-Step Setup Guide

This guide will walk you through setting up the Home Haven Hub website, from creating the core AI plugin to configuring essential pages.

### Step 3.1: Create the AI Content Generation Plugin

This plugin will handle AI content generation and provide a shortcode for displaying product tabs on the homepage.

1.  Navigate to your WordPress installation's `wp-content/plugins/` directory.
2.  Create a new directory named `ai-content-generator-poc`.
3.  Inside the `ai-content-generator-poc` directory, create a file named `ai-content-generator-poc.php`.
4.  Paste the following PHP code into `ai-content-generator-poc.php`:

    ```php
    <?php
    /**
     * Plugin Name: AI Content Generator PoC
     * Description: A proof-of-concept plugin to generate content using AI and create posts. Includes a homepage product tabs shortcode.
     * Version: 0.5.0
     * Author: AI Agent (Jules)
     */

    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly.
    }

    // Define a placeholder for the API key if not set by user
    if (!defined('OPENAI_API_KEY')) {
        define('OPENAI_API_KEY', 'YOUR_API_KEY_HERE'); // Emphasize this needs to be replaced by you via settings page
    }

    // Admin Menu Page
    add_action('admin_menu', 'ai_poc_admin_menu');
    function ai_poc_admin_menu() {
        add_menu_page(
            'AI Content Generator PoC',
            'AI Content PoC',
            'manage_options',
            'ai-content-poc',
            'ai_poc_render_admin_page',
            'dashicons-robot',
            80
        );
    }

    function ai_poc_render_admin_page() {
        // Handle API Key Saving
        if (isset($_POST['save_api_key_submit']) && isset($_POST['ai_poc_nonce_api_key'])) {
            if (!wp_verify_nonce($_POST['ai_poc_nonce_api_key'], 'ai_poc_save_api_key_action')) {
                wp_die('Nonce verification failed!');
            }
            if (!current_user_can('manage_options')) {
                wp_die('You do not have permission to save API keys.');
            }
            $api_key = sanitize_text_field($_POST['openai_api_key']);
            update_option('ai_poc_openai_api_key', $api_key);
            echo '<div class="notice notice-success is-dismissible"><p>API Key saved.</p></div>';
        }

        // Handle Review Generation
        $generation_result = null;
        if (isset($_POST['generate_review_submit']) && isset($_POST['ai_poc_nonce_generate'])) {
            if (!wp_verify_nonce($_POST['ai_poc_nonce_generate'], 'ai_poc_generate_review_action')) {
                wp_die('Nonce verification failed!');
            }
            if (!current_user_can('manage_options')) {
                wp_die('You do not have permission to generate content.');
            }
            $product_name = sanitize_text_field($_POST['product_name']);
            $product_features = sanitize_textarea_field($_POST['product_features']);
            $product_category = sanitize_text_field($_POST['product_category']);

            if (empty($product_name) || empty($product_features) || empty($product_category)) {
                $generation_result = ['success' => false, 'message' => 'Error: All product fields are required.'];
            } else {
                $generation_result = generate_ai_review_poc($product_name, $product_features, $product_category);
            }
        }

        $current_api_key = get_option('ai_poc_openai_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Content Generator PoC</h1>

            <?php
            // Display generation status message
            if (!empty($generation_result)) {
                $message_class = $generation_result['success'] ? 'notice-success' : 'notice-error';
                $status_message = '<div class="notice ' . $message_class . ' is-dismissible"><p>';
                $status_message .= esc_html($generation_result['message']);
                if ($generation_result['success'] && isset($generation_result['post_id'])) {
                    $edit_link = get_edit_post_link($generation_result['post_id']);
                    if ($edit_link) {
                        $status_message .= ' <a href="' . esc_url($edit_link) . '">Edit draft post (ID: ' . esc_html($generation_result['post_id']) . ').</a>';
                    }
                }
                $status_message .= '</p></div>';
                echo wp_kses_post($status_message);
            }
            ?>

            <h2>Settings</h2>
            <form method="post" action="">
                <?php wp_nonce_field('ai_poc_save_api_key_action', 'ai_poc_nonce_api_key'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="openai_api_key">OpenAI API Key</label></th>
                        <td><input type="text" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr($current_api_key); ?>" style="width: 400px;" /></td>
                    </tr>
                </table>
                <button type="submit" name="save_api_key_submit" class="button button-primary">Save API Key</button>
            </form>

            <hr/>
            <h2>Generate Product Review</h2>
            <form method="post" action="" id="ai-poc-generate-form">
                <?php wp_nonce_field('ai_poc_generate_review_action', 'ai_poc_nonce_generate'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="product_name">Product Name</label></th>
                        <td><input type="text" id="product_name" name="product_name" value="<?php echo isset($_POST['product_name']) ? esc_attr($_POST['product_name']) : ''; ?>" required style="width: 400px;" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="product_features">Key Features (comma-separated)</label></th>
                        <td><textarea id="product_features" name="product_features" rows="3" required style="width: 400px;"><?php echo isset($_POST['product_features']) ? esc_textarea($_POST['product_features']) : ''; ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="product_category">Product Category</label></th>
                        <td><input type="text" id="product_category" name="product_category" value="<?php echo isset($_POST['product_category']) ? esc_attr($_POST['product_category']) : ''; ?>" required style="width: 400px;" /></td>
                    </tr>
                </table>
                <input type="submit" name="generate_review_submit" value="Generate Review" class="button button-primary" id="ai-poc-generate-button">
                <span id="ai-poc-loading-indicator" style="display:none; margin-left:10px;">
                    <img src="/wp-admin/images/loading.gif" alt="Loading..."> Generating...
                </span>
            </form>
        </div>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('ai-poc-generate-form');
                if (form) {
                    form.addEventListener('submit', function() {
                        const button = document.getElementById('ai-poc-generate-button');
                        const loadingIndicator = document.getElementById('ai-poc-loading-indicator');
                        if (button) button.disabled = true;
                        if (loadingIndicator) loadingIndicator.style.display = 'inline-block';
                    });
                }
            });
        </script>
        <?php
    }

    function generate_ai_review_poc($product_name, $product_features, $product_category) {
        $api_key = get_option('ai_poc_openai_api_key', '');
        if (empty($api_key) || $api_key === 'YOUR_API_KEY_HERE' || strpos($api_key, 'sk-') !== 0) {
            // Fallback to simulated response if API key is not set, invalid, or the placeholder
            $simulated_title = "Review: The Amazing " . htmlspecialchars($product_name);
            $simulated_content = "This is a simulated review for the '{$product_name}'.

    It belongs to the '{$product_category}' category.

    Key features include: {$product_features}.

    Overall, this product offers great value and is highly recommended for anyone looking for quality and performance in their home and garden endeavors.";
            $ai_response_text = "Title: " . $simulated_title . "\n\n" . $simulated_content; // \n\n for paragraph break
            $source_message = "Used simulated response as API key is not set or invalid.";
        } else {
            $prompt = "Write a product review titled 'Review: {$product_name}' for the product '{$product_name}'. Category: {$product_category}. Key features: {$product_features}. The review should be objective, around 150-250 words, covering both pros and cons if possible. Ensure the title is clearly marked at the beginning, like 'Title: Your Generated Title'.";
            $system_prompt = "You are an expert product reviewer for a home and garden website. Generate content that is informative, engaging, and around 150-250 words.";

            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'method'    => 'POST',
                'headers'   => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'      => json_encode([
                    'model' => 'gpt-3.5-turbo', // Or your preferred model
                    'messages' => [
                        ['role' => 'system', 'content' => $system_prompt],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 300, // Adjust as needed for review length
                    'temperature' => 0.7, // Adjust for creativity vs. factuality
                ]),
                'timeout'   => 30, // Extended timeout for API response
            ]);

            if (is_wp_error($response)) {
                return ['success' => false, 'message' => 'API request failed: ' . $response->get_error_message()];
            }

            $http_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if ($http_code !== 200) {
                // Try to get a more specific error message from OpenAI
                $api_error_message = isset($data['error']['message']) ? $data['error']['message'] : $body;
                return ['success' => false, 'message' => "API Error ({$http_code}): " . esc_html($api_error_message)];
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'message' => 'Error decoding API response: ' . json_last_error_msg()];
            }

            $ai_response_text = $data['choices'][0]['message']['content'] ?? '';
            if (empty($ai_response_text)) {
                return ['success' => false, 'message' => 'API returned empty content.'];
            }
            $source_message = "Successfully generated by AI.";
        }

        // Extract title and content
        $generated_title = 'Review: ' . htmlspecialchars($product_name); // Default title
        $review_text = $ai_response_text;

        // Check if the AI response includes a "Title:" line
        if (preg_match('/^Title:\s*(.*?)(?:\n\n|$)/im', $ai_response_text, $title_matches)) {
            $generated_title = trim($title_matches[1]);
            // Remove the title line from the main review text
            $review_text = trim(str_replace($title_matches[0], '', $ai_response_text));
        }

        // Create or get category ID
        $category_id = null;
        if (!empty($product_category)) {
            $category = get_term_by('name', $product_category, 'category');
            if ($category && isset($category->term_id)) {
                $category_id = $category->term_id;
            } else {
                // Create the category if it doesn't exist
                $new_cat = wp_insert_term($product_category, 'category');
                if (!is_wp_error($new_cat) && isset($new_cat['term_id'])) {
                    $category_id = $new_cat['term_id'];
                }
            }
        }

        // Create the post
        $post_data = [
            'post_title'    => sanitize_text_field($generated_title),
            'post_content'  => wp_kses_post(nl2br($review_text)), // Use nl2br to preserve line breaks from AI
            'post_status'   => 'draft', // Create as draft for review
            'post_author'   => get_current_user_id() ?: 1, // Assign to current user or admin
            'post_category' => $category_id ? [$category_id] : [], // Assign category
        ];

        $post_id = wp_insert_post($post_data, true); // True to return WP_Error on failure

        if (is_wp_error($post_id)) {
            return ['success' => false, 'message' => 'Error creating post: ' . $post_id->get_error_message()];
        }

        return ['success' => true, 'message' => 'Draft post created successfully! ' . $source_message, 'post_id' => $post_id];
    }

    // Shortcode for Homepage Product Tabs
    add_shortcode('home_product_tabs', 'ai_poc_render_home_product_tabs');
    function ai_poc_render_home_product_tabs() {
        // Define categories for tabs - these should match categories you use for posts
        $tab_categories = [
            'garden-tools'       => 'Garden Tools', // slug => Display Name
            'kitchen-appliances' => 'Kitchen Appliances',
            'smart-home-devices' => 'Smart Home Devices',
            'home-office'        => 'Home Office',
            // Add more categories as needed
        ];

        ob_start(); // Start output buffering
        ?>
        <div class="products-tabs-container">
            <div class="products-tabs">
                <?php $first_tab = true; foreach ($tab_categories as $slug => $name): ?>
                    <button class="tab-btn <?php echo $first_tab ? 'active' : ''; $first_tab = false; ?>" data-category="<?php echo esc_attr($slug); ?>">
                        <?php echo esc_html($name); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php $first_content = true; foreach ($tab_categories as $slug => $name): ?>
                <div id="tab-<?php echo esc_attr($slug); ?>" class="products-grid tab-content <?php echo $first_content ? 'active' : ''; $first_content = false; ?>">
                    <?php
                    $args = [
                        'post_type'      => 'post', // Assuming reviews are standard posts
                        'posts_per_page' => 3,    // Number of products per tab
                        'category_name'  => $slug,  // Fetch posts from this category slug
                        'post_status'    => 'publish', // Only show published posts
                    ];
                    $query = new WP_Query($args);

                    if ($query->have_posts()):
                        while ($query->have_posts()): $query->the_post();
                            ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <!-- Placeholder for product image. Replace with actual image if available. -->
                                    <i class="fas fa-image" style="font-size: 4rem; color: var(--border-color);"></i>
                                </div>
                                <div class="product-content">
                                    <h3 class="product-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <div class="product-description">
                                        <?php
                                        // Display a short excerpt
                                        echo esc_html(wp_trim_words(get_the_excerpt(), 15, '...'));
                                        ?>
                                        </div>
                                    <div class="product-rating" style="margin-top: 10px; margin-bottom:10px;">
                                        <!-- Static placeholder rating. Implement dynamic rating if needed. -->
                                        <span class="stars" style="color: var(--accent-color);"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></span>
                                        <span class="rating-text" style="font-size:0.9em; color: var(--text-light); margin-left:5px;"> (4.0)</span>
                                    </div>
                                    <div class="product-buttons">
                                        <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-small">View Review</a>
                                        <!-- Optional: Add affiliate button here -->
                                    </div>
                                </div>
                            </div>
                            <?php
                        endwhile;
                    else:
                        echo '<p>No reviews found in ' . esc_html($name) . ' yet.</p>';
                    endif;
                    wp_reset_postdata(); // Important: Reset post data after custom query
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const tabContainer = document.querySelector('.products-tabs-container');
            if (!tabContainer) return;

            const tabButtons = tabContainer.querySelectorAll('.products-tabs .tab-btn');
            const tabContents = tabContainer.querySelectorAll('.products-grid.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Deactivate all buttons and content
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Activate clicked button and corresponding content
                    this.classList.add('active');
                    const category = this.getAttribute('data-category');
                    const activeContent = tabContainer.querySelector('#tab-' + category);
                    if (activeContent) {
                        activeContent.classList.add('active');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean(); // Return buffered content
    }
    ?>
    ```
5.  Go to your WordPress Admin > Plugins page and activate the "AI Content Generator PoC" plugin.

### Step 3.2: Apply Custom CSS to Astra Theme

This CSS will style the homepage elements, including the product tabs.

1.  In your WordPress Admin, navigate to `Appearance > Customize`.
2.  Click on `Additional CSS`.
3.  Paste the following CSS code:

    ```css
    /* Global Site Styles */
    :root {
        --primary-color: #4CAF50; /* Green for nature, growth */
        --secondary-color: #FFC107; /* Amber for warmth, positivity */
        --accent-color: #E91E63; /* Pink for vibrancy, attention */
        --background-color: #F5F5F5; /* Light grey for clean background */
        --text-color: #333333;
        --text-light: #555555;
        --border-color: #E0E0E0;
        --white-color: #FFFFFF;
        --font-primary: 'Montserrat', sans-serif;
        --font-secondary: 'Roboto Slab', serif;
    }

    body {
        font-family: var(--font-primary);
        color: var(--text-color);
        background-color: var(--background-color);
        line-height: 1.6;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: var(--font-secondary);
        font-weight: 700;
        color: var(--primary-color);
    }

    a {
        color: var(--accent-color);
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }

    .container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px 0;
    }

    /* Button Styles */
    .btn {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 5px;
        font-size: 1em;
        text-align: center;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
        border: none;
    }
    .btn-primary {
        background-color: var(--primary-color);
        color: var(--white-color);
    }
    .btn-primary:hover {
        background-color: darken(var(--primary-color), 10%);
        transform: translateY(-2px);
        color: var(--white-color);
        text-decoration: none;
    }
    .btn-secondary {
        background-color: var(--secondary-color);
        color: var(--text-color);
    }
    .btn-secondary:hover {
        background-color: darken(var(--secondary-color), 10%);
        transform: translateY(-2px);
        color: var(--text-color);
        text-decoration: none;
    }
    .btn-small {
        padding: 8px 15px;
        font-size: 0.9em;
    }


    /* Homepage Hero Section */
    .homepage-hero {
        background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('your-hero-image-url.jpg') no-repeat center center/cover; /* Replace with your image */
        color: var(--white-color);
        padding: 80px 20px;
        text-align: center;
        border-radius: 8px;
        margin-bottom: 40px;
    }
    .homepage-hero h1 {
        font-size: 3em;
        margin-bottom: 15px;
        color: var(--white-color); /* Ensure hero h1 is white */
    }
    .homepage-hero p {
        font-size: 1.2em;
        margin-bottom: 30px;
    }

    /* Products Tabs Container */
    .products-tabs-container {
        background-color: var(--white-color);
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 40px;
    }
    .products-tabs-container h2 {
        text-align: center;
        margin-bottom: 30px;
        font-size: 2.2em;
    }

    /* Tab Buttons Styling */
    .products-tabs {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
        flex-wrap: wrap; /* Allow tabs to wrap on smaller screens */
    }
    .products-tabs .tab-btn {
        padding: 12px 25px;
        margin: 0 8px 10px 8px; /* Added bottom margin for wrapping */
        border: 1px solid var(--border-color);
        border-radius: 25px; /* Pill shape */
        background-color: transparent;
        color: var(--primary-color);
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .products-tabs .tab-btn:hover {
        background-color: var(--primary-color);
        color: var(--white-color);
        border-color: var(--primary-color);
    }
    .products-tabs .tab-btn.active {
        background-color: var(--primary-color);
        color: var(--white-color);
        border-color: var(--primary-color);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    /* Products Grid (Tab Content) Styling */
    .products-grid {
        display: none; /* Hidden by default */
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }
    .products-grid.active {
        display: grid; /* Show active tab content */
    }

    /* Product Card Styling */
    .product-card {
        background-color: var(--white-color);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    }
    .product-card .product-image {
        width: 100%;
        height: 200px; /* Fixed height for images */
        background-color: #f0f0f0; /* Placeholder background */
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden; /* Ensure images don't break layout */
    }
    .product-card .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Ensure image covers the area */
    }
    .product-card .product-image .fas.fa-image { /* Placeholder icon styling */
        font-size: 4rem;
        color: var(--border-color);
    }
    .product-card .product-content {
        padding: 20px;
        flex-grow: 1; /* Allow content to fill available space */
        display: flex;
        flex-direction: column;
    }
    .product-card .product-title {
        font-size: 1.3em;
        margin-top: 0;
        margin-bottom: 10px;
    }
    .product-card .product-title a {
        color: var(--text-color);
    }
    .product-card .product-title a:hover {
        color: var(--accent-color);
    }
    .product-card .product-description {
        font-size: 0.95em;
        color: var(--text-light);
        margin-bottom: 15px;
        flex-grow: 1; /* Push buttons to the bottom */
    }
    .product-card .product-rating {
        margin-bottom: 15px;
    }
    .product-card .product-rating .stars {
        color: var(--secondary-color); /* Using secondary color for stars */
    }
    .product-card .product-buttons {
        margin-top: auto; /* Push buttons to the very bottom */
    }

    /* Font Awesome Icons (ensure Font Awesome is enqueued or linked) */
    @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .homepage-hero h1 {
            font-size: 2.5em;
        }
        .products-tabs {
            flex-direction: column; /* Stack tabs vertically */
            align-items: center;
        }
        .products-tabs .tab-btn {
            width: 80%; /* Make tabs wider for easier tapping */
            margin: 5px 0;
        }
        .products-grid {
            grid-template-columns: 1fr; /* Single column for product cards */
        }
    }
    ```
4.  Click `Publish`.

### Step 3.3: Set Up the Homepage

1.  Create a new page in WordPress (Admin > Pages > Add New). Title it "Home".
2.  In the page editor (using the Block Editor/Gutenberg):
    *   Add a **Cover Block** for the hero section.
        *   Upload a suitable background image (e.g., a beautiful home garden or modern kitchen).
        *   Set overlay opacity and color if desired.
        *   Inside the Cover Block, add a **Heading Block**: "Welcome to Home Haven Hub"
        *   Add a **Paragraph Block**: "Your ultimate guide to the best home and garden products. Discover expert reviews, tips, and recommendations to create your perfect living space."
        *   Add a **Buttons Block** with a button linking to your main review category or about page: "Explore Reviews"
    *   Below the Cover Block, add a **Shortcode Block**.
    *   Paste the following shortcode into the Shortcode Block: `[home_product_tabs]`
    *   Add an **HTML Block** (or use a Custom HTML block in the block editor) and paste the following HTML for the hero section (this offers more direct control than just Cover block settings, if preferred, otherwise the Cover block setup above is fine). *Note: If you used the Cover Block as described, you might not need this exact HTML, but it's provided for completeness if you prefer manual HTML.*

        ```html
        <div class="homepage-hero">
            <div class="container">
                <h1>Welcome to Home Haven Hub</h1>
                <p>Your ultimate guide to the best home and garden products. Discover expert reviews, tips, and recommendations to create your perfect living space.</p>
                <a href="#products" class="btn btn-primary btn-large">Explore Reviews</a>
            </div>
        </div>

        <div class="container products-tabs-container" id="products">
            <h2>Featured Product Reviews</h2>
            <!-- The [home_product_tabs] shortcode will render product tabs here -->
            [home_product_tabs]
        </div>
        ```
        *If you use this HTML block, ensure you place the `[home_product_tabs]` shortcode inside the second `div.container` if you want the "Featured Product Reviews" title above the tabs, or remove the shortcode from here if you placed it separately.*

3.  Publish the page.
4.  Go to `Settings > Reading` in your WordPress Admin.
5.  Set "Your homepage displays" to "A static page".
6.  Select your newly created "Home" page from the "Homepage" dropdown.
7.  Save changes.

### Step 3.4: Create Pillar Content Page Shells

Create the following pages as drafts. You will populate these with AI-generated or manually written content later.

*   **About Us**:
    *   Initial Content: "Welcome to Home Haven Hub! We are passionate about helping you create the home and garden of your dreams. Our mission is to provide unbiased, in-depth reviews and advice..."
*   **Contact Us**:
    *   Initial Content: "Have questions or suggestions? We'd love to hear from you! Reach out via..." (Consider adding a contact form plugin later, like WPForms or Contact Form 7).
*   **Privacy Policy**:
    *   Initial Content: "Your privacy is important to us. This Privacy Policy outlines how Home Haven Hub collects, uses, and protects your information..." (Use a WordPress privacy policy generator or consult legal advice).
*   **Affiliate Disclosure**:
    *   Initial Content: "Home Haven Hub participates in various affiliate marketing programs, which means we may get paid commissions on editorially chosen products purchased through our links to retailer sites..."
*   **Blog**:
    *   (This page is usually created automatically by WordPress or your theme when you start publishing posts. You can create a placeholder page if you wish, or let WordPress handle it. Ensure it's set as your "Posts page" in `Settings > Reading` if you create it manually and want it separate from the homepage).

Add these pages to your primary navigation menu (`Appearance > Menus`).

### Step 3.5: Configure and Use the AI Plugin

1.  **Set API Key**:
    *   In your WordPress Admin, go to the newly created "AI Content PoC" menu item.
    *   Enter your OpenAI API Key in the "OpenAI API Key" field.
    *   Click "Save API Key".
2.  **Generate Content**:
    *   On the "AI Content PoC" page, find the "Generate Product Review" section.
    *   **Product Name**: Enter the name of the product (e.g., "GreenThumb Smart Sprinkler System").
    *   **Key Features**: List key features, comma-separated (e.g., "Wi-Fi enabled, weather-based scheduling, mobile app control, water-saving technology").
    *   **Product Category**: Enter a relevant category (e.g., "Garden Tools", "Smart Home Devices"). This category will be used for organizing posts. If the category doesn't exist, the plugin will attempt to create it.
    *   Click "Generate Review".
    *   The plugin will contact the OpenAI API. Upon success, a draft post will be created with the AI-generated review. You'll see a success message with a link to edit the draft.
3.  **Review and Publish**:
    *   Click the link to edit the draft post.
    *   **Crucially, review and edit the AI-generated content.** Add your personal insights, ensure accuracy, check for factual correctness, improve readability, and optimize for SEO (e.g., using Yoast SEO).
    *   Add relevant images, affiliate links (using a plugin like ThirstyAffiliates is recommended), and any other necessary details.
    *   Publish the post when you are satisfied.
4.  **Categorize Posts for Tabs**:
    *   Ensure your published reviews are assigned to the categories you defined in the `ai-content-generator-poc.php` plugin file within the `ai_poc_render_home_product_tabs` function (e.g., 'garden-tools', 'kitchen-appliances'). This is how they will appear under the correct tabs on the homepage.

## Essential Supporting Plugins

While the core functionality is provided by our custom plugin, these additional plugins are highly recommended for a fully functional affiliate website:

*   **Yoast SEO**: For optimizing your content for search engines, managing sitemaps, and improving overall site visibility.
*   **W3 Total Cache** (or WP Super Cache / LiteSpeed Cache): For caching your website to improve loading speed and user experience.
*   **ThirstyAffiliates** (or a similar affiliate link management plugin): For cloaking, managing, and tracking your affiliate links effectively.

---

This README provides a foundational guide. Remember that building a successful affiliate website requires ongoing effort in content creation, SEO, user engagement, and adapting to AI advancements. Good luck!
=======
# AI Content Generator PoC & Product Tabs

This WordPress plugin provides a proof-of-concept for AI-powered content generation and a shortcode to display product reviews in a tabbed interface.

## Features

### 1. AI Content Generation (Product Reviews)

*   **OpenAI Integration:** Leverages the OpenAI API (specifically `gpt-3.5-turbo`) to generate product review content.
*   **Admin Interface:** Provides an admin page (`AI Content PoC`) to:
    *   Save your OpenAI API Key.
    *   Input product details: Product Name, Key Features (comma-separated), and Product Category.
    *   Generate a product review based on the provided details.
*   **Automated Post Creation:** Upon successful generation, the plugin automatically creates a new draft post with:
    *   A generated title (e.g., "Review: [Product Name]").
    *   The AI-generated review content.
    *   The specified product category (new categories are created if they don't exist).
*   **API Key Handling:**
    *   Requires a valid OpenAI API key to be set in the admin settings for actual AI generation.
    *   If the API key is not set, is invalid, or the API call fails, the plugin will generate a *simulated* review content as a placeholder, clearly indicating that it's not AI-generated.
*   **User Feedback:** Displays success or error messages after generation attempts, including a link to edit the newly created draft post.

### 2. Product Review Posting

*   This is intrinsically linked to the AI Content Generation feature.
*   Generated reviews are saved as WordPress posts (in 'draft' status by default), allowing users to review and publish them.
*   Posts are assigned to the category specified during generation.

### 3. Product Tab Shortcode `[home_product_tabs]`

*   **Frontend Display:** Use the shortcode `[home_product_tabs]` on any page or post to display a tabbed interface for product reviews.
*   **Predefined Categories:** The tabs are hardcoded to display posts from the following categories:
    *   Garden Tools (`garden-tools`)
    *   Kitchen Appliances (`kitchen-appliances`)
    *   Smart Home Devices (`smart-home-devices`)
    *   Home Office (`home-office`)
*   **Content Display:** Each tab displays up to 3 of the most recent *published* posts from its respective category.
    *   Shows product title (linked to the post), a short excerpt, a placeholder for a product image, a static star rating, and a "View Review" button.
*   **Basic Interactivity:** Includes JavaScript for switching between tabs.

## Setup

1.  **Installation:**
    *   Download the plugin.
    *   Upload the `ai-content-generator-poc` directory to your `/wp-content/plugins/` directory.
    *   Activate the plugin through the 'Plugins' menu in WordPress.
2.  **API Key Configuration:**
    *   Navigate to the "AI Content PoC" menu in your WordPress admin dashboard.
    *   Enter your OpenAI API Key in the "Settings" section and click "Save API Key".
    *   **Important:** The plugin uses a placeholder API key (`YOUR_API_KEY_HERE`) by default. Real AI content generation will only work after you provide a valid `sk-` OpenAI API key.

## How to Use

### Generating a Product Review

1.  Go to "AI Content PoC" in the admin menu.
2.  Ensure your OpenAI API Key is saved.
3.  Fill in the "Product Name", "Key Features", and "Product Category" fields.
4.  Click "Generate Review".
5.  A message will indicate success or failure. If successful, a link to the draft review post will be provided.
6.  Navigate to "Posts" to find your generated draft review, edit it as needed, and publish.

### Displaying Product Tabs

1.  Create or edit a page/post where you want the product tabs to appear.
2.  Add the shortcode `[home_product_tabs]` to the content area.
3.  Publish or update the page/post.
4.  View the page on the frontend to see the tabbed product display. Ensure you have published reviews in the relevant categories (`garden-tools`, `kitchen-appliances`, `smart-home-devices`, `home-office`) for them to appear.

## Functionality Overview

*   **`ai-content-generator-poc.php`**: The main plugin file. Handles:
    *   Plugin metadata and initialization.
    *   Admin menu creation and rendering the admin page.
    *   Saving the OpenAI API key (`update_option`).
    *   Handling the review generation form submission.
    *   Calling the `generate_ai_review_poc()` function.
    *   Registering the `[home_product_tabs]` shortcode and its rendering function `ai_poc_render_home_product_tabs()`.
*   **`generate_ai_review_poc()` function**:
    *   Retrieves the saved API key.
    *   Constructs a prompt for the OpenAI API based on product details.
    *   Makes a `wp_remote_post` call to the OpenAI API (`https://api.openai.com/v1/chat/completions` using `gpt-3.5-turbo`).
    *   Parses the API response.
    *   If API interaction is successful, it extracts the title and content.
    *   If API key is missing/invalid or API fails, it generates a simulated review.
    *   Creates a new post (`wp_insert_post`) with the generated/simulated content, setting it to 'draft' status and assigning the category.
*   **`ai_poc_render_home_product_tabs()` function**:
    *   Defines a list of categories for the tabs.
    *   Generates HTML for the tab buttons and content panes.
    *   For each category, it runs a `WP_Query` to fetch the 3 most recent published posts.
    *   Displays post title, excerpt, placeholder image, static rating, and a link to the review.
    *   Includes inline JavaScript for tab switching logic.

## Limitations & Future Improvements (PoC)

*   **Error Handling:** Basic error handling is in place, but could be more robust for API interactions and user inputs.
*   **Security:** Nonces are used for form submissions. Input sanitization is applied. Further security review might be beneficial for a production plugin.
*   **Styling:** The product tabs have minimal styling. This would typically be enhanced or made more theme-agnostic.
*   **Shortcode Customization:** The categories for the product tabs are hardcoded. Future versions could allow customization via shortcode attributes (e.g., `[home_product_tabs categories="electronics,books"]`).
*   **Image Handling:** The product tabs show a placeholder icon for images. Actual product image integration would be a key improvement.
*   **API Model & Prompts:** The AI model (`gpt-3.5-turbo`) and prompts are fixed. These could be made configurable.
*   **No Batch Generation:** Reviews are generated one by one.
*   **Localization:** The plugin is not localized.

This plugin serves as a proof-of-concept and may require further development for production use.
main
