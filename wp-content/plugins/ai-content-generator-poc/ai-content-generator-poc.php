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
        $simulated_title = "Review: The Amazing " . htmlspecialchars($product_name);
        $simulated_content = "This is a simulated review for the '{$product_name}'.

It belongs to the '{$product_category}' category.

Key features include: {$product_features}.

Overall, this product offers great value and is highly recommended for anyone looking for quality and performance in their home and garden endeavors.";
        $ai_response_text = "Title: " . $simulated_title . "\n\n" . $simulated_content;
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
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $system_prompt],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 300,
                'temperature' => 0.7,
            ]),
            'timeout'   => 30,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'API request failed: ' . $response->get_error_message()];
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($http_code !== 200) {
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

    $generated_title = 'Review: ' . htmlspecialchars($product_name);
    $review_text = $ai_response_text;
    if (preg_match('/^Title:\s*(.*?)(?:\n\n|$)/im', $ai_response_text, $title_matches)) {
        $generated_title = trim($title_matches[1]);
        $review_text = trim(str_replace($title_matches[0], '', $ai_response_text));
    }

    $category_id = null;
    if (!empty($product_category)) {
        $category = get_term_by('name', $product_category, 'category');
        if ($category && isset($category->term_id)) {
            $category_id = $category->term_id;
        } else {
            $new_cat = wp_insert_term($product_category, 'category');
            if (!is_wp_error($new_cat) && isset($new_cat['term_id'])) {
                $category_id = $new_cat['term_id'];
            }
        }
    }

    $post_data = [
        'post_title'    => sanitize_text_field($generated_title),
        'post_content'  => wp_kses_post(nl2br($review_text)),
        'post_status'   => 'draft',
        'post_author'   => get_current_user_id() ?: 1,
        'post_category' => $category_id ? [$category_id] : [],
    ];

    $post_id = wp_insert_post($post_data, true);

    if (is_wp_error($post_id)) {
        return ['success' => false, 'message' => 'Error creating post: ' . $post_id->get_error_message()];
    }

    return ['success' => true, 'message' => 'Draft post created successfully! ' . $source_message, 'post_id' => $post_id];
}

add_shortcode('home_product_tabs', 'ai_poc_render_home_product_tabs');
function ai_poc_render_home_product_tabs() {
    $tab_categories = [
        'garden-tools'       => 'Garden Tools',
        'kitchen-appliances' => 'Kitchen Appliances',
        'smart-home-devices' => 'Smart Home Devices',
        'home-office'        => 'Home Office',
    ];

    ob_start();
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
                    'post_type'      => 'post',
                    'posts_per_page' => 3,
                    'category_name'  => $slug,
                    'post_status'    => 'publish',
                ];
                $query = new WP_Query($args);

                if ($query->have_posts()):
                    while ($query->have_posts()): $query->the_post();
                        ?>
                        <div class="product-card">
                            <div class="product-image">
                                <i class="fas fa-image" style="font-size: 4rem; color: var(--border-color);"></i>
                            </div>
                            <div class="product-content">
                                <h3 class="product-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <div class="product-description">
                                    <?php
                                    echo esc_html(wp_trim_words(get_the_excerpt(), 15, '...'));
                                    ?>
                                    </div>
                                <div class="product-rating" style="margin-top: 10px; margin-bottom:10px;">
                                    <span class="stars" style="color: var(--accent-color);"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></span>
                                    <span class="rating-text" style="font-size:0.9em; color: var(--text-light); margin-left:5px;"> (4.0)</span>
                                </div>
                                <div class="product-buttons">
                                    <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-small">View Review</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    endwhile;
                else:
                    echo '<p>No reviews found in ' . esc_html($name) . ' yet.</p>';
                endif;
                wp_reset_postdata();
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
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

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
    return ob_get_clean();
}
?>
