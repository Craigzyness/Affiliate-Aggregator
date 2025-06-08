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