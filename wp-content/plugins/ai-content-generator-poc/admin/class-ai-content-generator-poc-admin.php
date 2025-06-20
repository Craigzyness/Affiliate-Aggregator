<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ai_Content_Generator_Poc
 * @subpackage Ai_Content_Generator_Poc/admin
 * @author     Your Name <email@example.com>
 */
class Ai_Content_Generator_Poc_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    0.1.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    0.1.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.1.0
     * @param    string    $plugin_name     The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    0.1.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ai_Content_Generator_Poc_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ai_Content_Generator_Poc_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ai-content-generator-poc-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    0.1.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ai_Content_Generator_Poc_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ai_Content_Generator_Poc_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ai-content-generator-poc-admin.js', array( 'jquery' ), $this->version, false );

    }

    /**
     * Add the plugin's menu page to the WordPress admin menu.
     *
     * @since    0.1.0
     */
    public function add_plugin_admin_menu() {

        add_menu_page(
            __( 'AI Content Generator PoC', 'ai-content-generator-poc' ),
            __( 'AI Content PoC', 'ai-content-generator-poc' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'display_plugin_admin_page' ),
            'dashicons-admin-generic',
            25
        );

    }

    /**
     * Display the plugin's admin page.
     *
     * @since    0.1.0
     */
    public function display_plugin_admin_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/ai-content-generator-poc-admin-display.php';
    }

}

?>
