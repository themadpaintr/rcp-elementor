<?php
/**
 * Plugin Name: RCP Elementor
 * Description: Hide or show Elementor widgets based on RCP (Restrict Content Pro) Groups and Access levels.
 * Version:     1.0.1
 * Author:      Rajiv Kolluru
 * Author URI:  https://themadpaintr.com/
 * Text Domain: rcp-elementor
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'RCP_ELEMENTOR', '1.1.0' );

define( 'RCP_ELEMENTOR_FILE__', __FILE__ );
define( 'RCP_ELEMENTOR_PLUGIN_BASE', plugin_basename( RCP_ELEMENTOR_FILE__ ) );
define( 'RCP_ELEMENTOR_PATH', plugin_dir_path( RCP_ELEMENTOR_FILE__ ) );

add_action( 'plugins_loaded', 'rcpe_load_plugin_textdomain' );

/**
 * Load textdomain.
 *
 * Load gettext translate for Elementor text domain.
 *
 * @return void
 * @since 1.0.0
 *
 */
function rcpe_load_plugin_textdomain() {
	load_plugin_textdomain( 'rcp-elementor' );
}

require_once RCP_ELEMENTOR_PATH . 'conditions.php';
