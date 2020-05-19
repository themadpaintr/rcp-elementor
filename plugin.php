<?php
/**
 * Plugin Name: RCP Elementor
 * Description: Restrict any Elementor widget based on RCP (Restrict Content Pro) Groups and Access levels.
 * Version:     1.0.0
 * Author:      Rajiv Kolluru
 * Author URI:  https://themadpaintr.com/
 * Text Domain: rcp-elementor
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager; 

class RCP_Elementor {

	const VERSION = '1.0.0';							// Plugin Version
	const MINIMUM_ELEMENTOR_VERSION = '2.0.0';			// Minimum Elementor Version
	const MINIMUM_PHP_VERSION = '7.0';					// Minimum PHP Version

	private static $_instance = null;					// The single instance of the class.
	public $locations = array(
        array(
            'element' => 'common',
            'action'  => '_section_style',
        ),
        array(
            'element' => 'section',
            'action'  => 'section_advanced',
        )
    );
    public $section_name = 'rcpe_section_visibility_settings';


	// Ensures only one instance of the class is loaded or can be loaded.
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	// Load localization functionality and initiate the plugin
	public function __construct() {

		add_action( 'init', [ $this, 'i18n' ] );
		add_action( 'plugins_loaded', [ $this, 'init' ] );

	}

	// Construtor. Fired by `init` action hook.
	public function i18n() {

		load_plugin_textdomain( 'rcp-elementor' );

	}

	/**
	 * Initialize the plugin
	 *
	 * Load the plugin only after Elementor (and other plugins) are loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed load the files required to run the plugin.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function init() {

		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
			return;
		}

		// Check for required Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );
			return;
		}

		// Check for required PHP version
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
			return;
		}

		// Load Conditions class
		require_once( __DIR__ . '/conditions.php' );

		// Register new section to display restriction controls
		$this->register_sections();
		$this->content_restriction();
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function admin_notice_missing_main_plugin() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'rcp-elementor' ),
			'<strong>' . esc_html__( 'RCP Elementor', 'rcp-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'rcp-elementor' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required Elementor version.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function admin_notice_minimum_elementor_version() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'rcp-elementor' ),
			'<strong>' . esc_html__( 'RCP Elementor', 'rcp-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'rcp-elementor' ) . '</strong>',
			 self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required PHP version.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function admin_notice_minimum_php_version() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'rcp-elementor' ),
			'<strong>' . esc_html__( 'RCP Elementor', 'rcp-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'rcp-elementor' ) . '</strong>',
			 self::MINIMUM_PHP_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}

	private function register_sections() {
        foreach( $this->locations as $where ) {
            add_action( 'elementor/element/'.$where['element'].'/'.'section_custom_css/after_section_end', array( $this, 'add_section' ), 10, 2 );
        }
    }

	public function add_section( $element, $args ) {
        $exists = \Elementor\Plugin::instance()->controls_manager->get_control_from_stack( $element->get_unique_name(), $this->section_name );

        if( !is_wp_error( $exists ) )
            return false;

        $element->start_controls_section(
            $this->section_name, array(
                'tab'   => Controls_Manager::TAB_ADVANCED,
                'label' => __( 'RCP Conditions', 'rcp-elementor' )
            )
        );

        $element->end_controls_section();
    }

    protected function content_restriction(){}
}
// Instantiate Plugin Class
RCP_Elementor::instance();