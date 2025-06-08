<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://example.com/
 * @since      0.1.0
 *
 * @package    Ai_Content_Generator_Poc
 * @subpackage Ai_Content_Generator_Poc/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      0.1.0
 * @package    Ai_Content_Generator_Poc
 * @subpackage Ai_Content_Generator_Poc/includes
 * @author     Your Name <email@example.com>
 */
class Ai_Content_Generator_Poc_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    0.1.0
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain(
            'ai-content-generator-poc',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );

    }

}

?>
