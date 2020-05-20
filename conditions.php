<?php

namespace Elementor;

// Elementor classes
use Elementor\Controls_Manager;
use Elementor\Repeater;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class RCP_Elementor {
	private static $instance = null;

	public static function get_instance() {
		if ( ! self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	public function init() {
		if ( ! defined( 'ELEMENTOR_PATH' ) ) {
			return;
		}

		// Add section in after Custom CSS section
		add_action('elementor/element/common/section_custom_css/after_section_end', [ $this, 'add_section' ] );
		add_action('elementor/element/section/section_custom_css/after_section_end', [ $this, 'add_section' ] );

		// Add items to display in the section
		add_action( 'elementor/element/common/rcpe_section/before_section_end', [ $this, 'register_controls' ], 10, 2 );
		add_action( 'elementor/element/section/rcpe_section/before_section_end', [ $this, 'register_controls' ], 10, 2 );

		// Render content
		add_action( 'elementor/frontend/section/should_render', [ $this, 'section_should_render' ] , 10, 2 );
		add_action( 'elementor/frontend/widget/should_render', [ $this, 'section_should_render' ] , 10, 2 );
		add_action( 'elementor/frontend/repeater/should_render', [ $this, 'section_should_render' ] , 10, 2 );

	}

	public function add_section( $element ) {
		$element->start_controls_section(
			'rcpe_section',
			[
				'tab' 	=> Controls_Manager::TAB_ADVANCED,
				'label' => __( 'RCP Conditions', 'rcp-elementor' ),
			]
		);
		$element->end_controls_section();
	}
	/*

	/**
	 * @param $element \Elementor\Widget_Base
	 * @param $section_id
	 * @param $args
	 */
	public function register_controls( $element, $args ) {

		global $rcp_levels_db;
		
		// RCP Membership types and access levels 
		$levels   = $rcp_levels_db->get_levels( array( 'status' => 'active' ) );
		$memtypes = array();
		if ( ! empty( $levels ) ) {
		    foreach ( $levels as $level ) {
		        $memtypes[$level->id] = $level->name;
		    }
		}

		// Adding Enable Conditions toggle
		$element->add_control(
			'rcpe_conditions_enabled', [
				'label' 		=> __('Enable Conditions', 'rcp-elementor'),
				'type' 			=> Controls_Manager::SWITCHER,
				'default' 		=> '',
				'label_on' 		=> __('Yes', 'rcp-elementor'),
				'label_off' 	=> __('No', 'rcp-elementor'),
				'return_value' 	=> 'yes',
			]
		);

		// Adding Display Options relation ANY, ALL
		$element->add_control(
			'rcpe_conditions_relation',
			[
				'label'						=> __( 'Display on', 'rcp-elementor' ),
				'type'						=> Controls_Manager::SELECT,
				'default'					=> 'all',
				'options'					=> [
					'all' 		=> __( 'All conditions met', 'rcp-elementor' ),
					'any' 		=> __( 'Any condition met', 'rcp-elementor' ),
				],
				'condition'					=> [
					'rcpe_conditions_enabled' => 'yes',
				],
			]
		);

		// Adding repeatable controls
		$repeater = new Repeater();
		
		// Condition parameters menu
		$repeater->add_control(
			'rcpe_condition_key',
			[
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> 'authentication',
				'label_block'	=> true,
				'groups'		=> [
					[
						'label'					=> __( 'User', 'rcp-elementor' ),
						'options'				=> [
							'authentication' 	=> __( 'Login Status', 'rcp-elementor' ),
							'role' 				=> __( 'User Role', 'rcp-elementor' ),
						],
					],
					[
						'label'					=> __( 'Singular', 'rcp-elementor' ),
						'options' 				=> [
							'page' 				=> __( 'Page', 'rcp-elementor' ),
							'post' 				=> __( 'Post', 'rcp-elementor' ),
							'post_type' 		=> __( 'Post Type', 'rcp-elementor' ),
						],
					],
					[
						'label'					=> __( 'RCP Membership', 'rcp-elementor' ),
						'options'				=> [
							'accesslevel' 		=> __( 'Access Level', 'rcp-elementor' ),
							'memtype' 			=> __( 'Membership Type', 'rcp-elementor' )
						],
					],
				],
			]
		);

		// Condition operators
		$repeater->add_control(
			'rcpe_condition_operator',
			[
				'type' 				=> Controls_Manager::SELECT,
				'default' 			=> 'is',
				'label_block' 		=> true,
				'options' 			=> [
					'is' 		=> __( 'Is', 'rcp-elementor' ),
					'not' 		=> __( 'Is not', 'rcp-elementor' ),
				],
			]
		);

		// Options for authentication
		$repeater->add_control(
			'rcpe_condition_authentication_value',
			[
				'type'				=> Controls_Manager::SELECT,
				'default'			=> 'authenticated',
				'label_block'		=> true,
				'options'			=> [
					'authenticated' => __( 'Logged in', 'rcp-elementor' ),
				],
				'condition'			=> [
					'rcpe_condition_key' => 'authentication',
				],
			]
		);

		// Options for user role
		$repeater->add_control(
			'rcpe_condition_role_value',
			[
				'type'				=> Controls_Manager::SELECT,
				'description'		=> __( 'Warning: This condition applies only to logged in visitors.', 'rcp-elementor' ),
				'default'			=> 'subscriber',
				'label_block'		=> true,
				'options'			=> $this->get_roles(),
				'condition'			=> [
					'rcpe_condition_key' => 'role',
				],
			]
		);

		// Options for Pages
		$repeater->add_control(
			'rcpe_condition_page_value',
			[
				'type'				=> Controls_Manager::SELECT2,
				'default'			=> '',
				'multiple'			=> true,
				'label_block'		=> true,
				'placeholder'		=> __( 'Any', 'rcp-elementor' ),
				'description'		=> __( 'Leave blank for any page.', 'rcp-elementor' ),
				'options' 			=> $this->get_all_pages(),
				'condition'			=> [
					'rcpe_condition_key' => 'page',
				],
			]
		);

		// Options for Posts
		$repeater->add_control(
			'rcpe_condition_post_value',
			[
				'type'				=> Controls_Manager::SELECT2,
				'default'			=> '',
				'multiple'			=> true,
				'label_block'		=> true,
				'placeholder'		=> __( 'Any', 'rcp-elementor' ),
				'description'		=> __( 'Leave blank for any post.', 'rcp-elementor' ),
				'options' 			=> $this->get_all_posts(),
				'condition'			=> [
					'rcpe_condition_key' => 'post',
				],
			]
		);

		// Options for Custom Post Types
		$repeater->add_control(
			'rcpe_condition_post_type_value',
			[
				'type' 			=> Controls_Manager::SELECT2,
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'rcp-elementor' ),
				'description'	=> __( 'Leave blank or select all for any post type.', 'rcp-elementor' ),
				'label_block' 	=> true,
				'multiple'		=> true,
				'options' 		=> $this->get_custom_post_types(),
				'condition' 	=> [
					'rcpe_condition_key' => 'post_type',
				],
			]
		);

		// RCP Membership type filter values
		$repeater->add_control(
			'rcpe_condition_memtype_value',
			[
				'type'				=> Controls_Manager::SELECT2,
				'description'		=> __( 'Warning: This condition applies only to logged in visitors.', 'rcp-elementor' ),
				'default'			=> '',
				'label_block'		=> true,
				'options'			=> $memtypes,
				'multiple' 			=> true,
				'condition'			=> [
					'rcpe_condition_key' => 'memtype',
				],
			]
		);
		
		// RCP Access level filter values
		$repeater->add_control(
			'rcpe_condition_accesslevel_value',
			[
				'type'				=> Controls_Manager::SELECT2,
				'description'		=> __( 'Warning: This condition applies only to logged in visitors.', 'rcp-elementor' ),
				'default'			=> '',
				'label_block'		=> true,
				'options'			=> array(1,2,3,4,5,6,7,8,9,10),
				'multiple' 			=> true,
				'condition'			=> [
					'rcpe_condition_key' => 'accesslevel',
				],
			]
		);

		$element->add_control(
			'rcpe_display_conditions',
			[
				'label' 	=> __( 'Conditions', 'rcp-elementor' ),
				'type' 		=> Controls_Manager::REPEATER,
				'default' 	=> [
					[
						'rcpe_condition_key' 					=> 'authentication',
						'rcpe_condition_operator' 				=> 'is',
						'rcpe_condition_authentication_value' 	=> 'authenticated',
					],
				],
				'condition'		=> [
					'rcpe__conditions_enabled' => 'yes',
				],
				'fields' 		=> $repeater->get_controls(),
				'title_field' 	=> 'Condition',
			]
		);
	}

	private function get_roles() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}
		$all_roles = $wp_roles->roles;
		$editable_roles = apply_filters('editable_roles', $all_roles);

		$data = [ 'rcpe-guest' => 'Guests', 'rcpe-user' => 'Logged in users' ];

		foreach ( $editable_roles as $k => $role ) {
			$data[$k] = $role['name'];
		}

		return $data;
	}

	private function get_custom_post_types() {

		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		$options = array();

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->label;
		}

		return $options;
	}

	private function get_all_posts() {

		$post_list = get_posts( array(
			'post_type'         => 'post',
			'orderby'           => 'date',
			'order'             => 'DESC',
			'posts_per_page'    => -1,
		) );

		$posts = array();

		if ( ! empty( $post_list ) && ! is_wp_error( $post_list ) ) {
			foreach ( $post_list as $post ) {
			$posts[ $post->ID ] = $post->post_title;
			}
		}

		return $posts;
	}

	private function get_all_pages() {

		$page_list = get_pages( array(
			'post_type'         => 'page',
			'orderby'           => 'date',
			'order'             => 'DESC',
			'post_status'		=> 'publish',
			'posts_per_page'    => -1,
		) );

		$pages = array();

		if ( ! empty( $page_list ) && ! is_wp_error( $page_list ) ) {
			foreach ( $page_list as $page ) {
			$pages[ $page->ID ] = $page->page_title;
			}
		}

		return $pages;
	}

	public function section_should_render( $should_render, $section ) {
		
		// Get the settings
		$settings = $section->get_settings();

		if ( 'yes' === $settings[ 'rcpe_conditions_enabled' ] ) {

			// Set the conditions
			$this->set_conditions( $element->get_id(), $settings['rcpe_display_conditions'] );

			// Check the conditions
			if ( ! $this->is_visible( $element->get_id(), $settings['rcpe_display_conditions_relation'] ) ) { 
				$element->add_render_attribute( '_wrapper', 'class', 'pp-visibility-hidden' );
				// pp-visibility-hidden is a CSS class
			}
		}

	}

	/**
	 * Set conditions.
	 *
	 * Sets the conditions property to all conditions comparison values
	 *
	 * @param mixed  $conditions  The conditions from the repeater field control
	 *
	 * @return void
	 */
	private function set_conditions( $id, $conditions = [] ) {
		if ( ! $conditions )
			return;

		foreach ( $conditions as $index => $condition ) {
			$key 		= $condition['rcpe_condition_key'];
			$operator 	= $condition['rcpe_condition_operator'];
			$value 		= $condition['rcpe_condition_' . $key . '_value'];

			if ( method_exists( $this, 'check_' . $key ) ) {
				$check = call_user_func( [ $this, 'check_' . $key ], $value, $operator );
				$this->conditions[ $id ][ $key . '_' . $condition['_id'] ] = $check;
			}
		}
	}

	/**
	 * Check conditions.
	 *
	 * Checks for all or any conditions and returns true or false
	 * depending on wether the content can be shown or not
	 *
	 * @param mixed  $relation  Required conditions relation
	 *
	 * @return bool
	 */
	protected function is_visible( $id, $relation ) {

		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			if ( 'any' === $relation ) {
				if ( ! in_array( true, $this->conditions[ $id ] ) )
					return false;
			} else {
				if ( in_array( false, $this->conditions[ $id ] ) )
					return false;
			}
		}

		return true;
	}

}
RCP_Elementor::get_instance()->init();
