<?php

namespace Elementor;

// Elementor classes
use Elementor\Controls_Manager;

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
		add_action('elementor/element/common/section_custom_css/after_section_end', [ $this, 'add_rcpe_section' ] );
		add_action('elementor/element/section/section_custom_css/after_section_end', [ $this, 'add_rcpe_section' ] );

		// Add items to display in the section
		add_action( 'elementor/element/common/rcpe_section/before_section_end', [ $this, 'register_rcpe_controls' ], 10, 2 );
		add_action( 'elementor/element/section/rcpe_section/before_section_end', [ $this, 'register_rcpe_controls' ], 10, 2 );

		// Change content value as per user conditions
		add_filter('elementor/widget/render_content', [ $this, 'content_change' ], 999, 2 );
		add_filter('elementor/section/render_content', [ $this, 'content_change' ], 999, 2 );

		// Change display value as per user conditions
		add_filter( 'elementor/frontend/section/should_render', [ $this, 'section_should_render' ] , 10, 2 );
		add_filter( 'elementor/frontend/widget/should_render', [ $this, 'section_should_render' ] , 10, 2 );
		add_filter( 'elementor/frontend/repeater/should_render', [ $this, 'section_should_render' ] , 10, 2 );
	}

	public function add_rcpe_section( $element ) {
		$element->start_controls_section(
			'rcpe_section',
			[
				'tab' 	=> Controls_Manager::TAB_ADVANCED,
				'label' => __( 'RCP Conditions', 'rcp-elementor' ),
			]
		);
		$element->end_controls_section();
	}

	/**
	 * @param $element \Elementor\Widget_Base
	 * @param $section_id
	 * @param $args
	 */
	public function register_rcpe_controls( $element, $args ) {

		// Adding Enable Conditions toggle
		$element->add_control(
			'rcpe_enabled', [
				'label' 		=> __('Enable Conditions', 'rcp-elementor'),
				'type' 			=> Controls_Manager::SWITCHER,
				'default' 		=> '',
				'label_on' 		=> __('Yes', 'rcp-elementor'),
				'label_off' 	=> __('No', 'rcp-elementor'),
				'return_value' 	=> 'yes',
			]
		);

		// Adding Visible for Memebership Level multi select
		$element->add_control(
			'rcpe_role_visible',
			[
				'type' => Controls_Manager::SELECT2,
				'label' => __( 'Visible for:', 'rcp-elementor' ),
				'options' => $this->get_mem_levels(),
				'default' => [],
				'multiple' => true,
				'label_block' => true,
				'condition' => [
					'rcpe_enabled' => 'yes',
					'rcpe_role_hidden' => [],
				],
			]
		);

		// Adding Hidden for Membership Level multi select
		$element->add_control(
			'rcpe_role_hidden',
			[
				'type' => Controls_Manager::SELECT2,
				'label' => __( 'Hidden for:', 'rcp-elementor' ),
				'options' => $this->get_mem_levels(),
				'default' => [],
				'multiple' => true,
				'label_block' => true,
				'condition' => [
					'rcpe_enabled' => 'yes',
					'rcpe_role_visible' => [],
				],
			]
		);
	}


	private function get_mem_levels() {
		
		global $rcp_levels_db;
		
		// RCP Membership types and access levels 
		$levels   = $rcp_levels_db->get_levels( array( 'status' => 'active' ) );
		$memtypes = array();
		if ( ! empty( $levels ) ) {
			foreach ( $levels as $level ) {
				$memtypes[$level->id] = $level->name;
			}
		}

		return $memtypes;
	}

	/**
	 * @param string $content
	 * @param $widget \Elementor\Widget_Base
	 * @return string
	 */
	public function content_change( $content, $widget ) {

		// If in Editing mode, show content
		if (Plugin::$instance->editor->is_edit_mode() ) {
			return $content;
		}

		// Get user settings
		$settings = $widget->get_settings();

		// Check widget matches in user settings
		if ( ! $this->should_render( $settings ) ) {
			return '';
		}

		// return content
		return $content;

	}

	public function section_should_render( $should_render, $section ) {
		// Get user settings
		$settings = $section->get_settings();

		// Check widget matches in user settings
		if ( ! $this->should_render( $settings ) ) {
			return false;
		}

		// return display condition
		return $should_render;

	}

	private function should_render( $entries ) {
		
		$user_state = is_user_logged_in();

		// Check if Toggle is on
		if( $entries['rcpe_enabled'] == 'yes') {

			// If Visible for is not empty
			if( ! empty( $entries['rcpe_role_visible'] ) ) {

				// If user is not Logged in, don't show content
				if ( $user_state == false ) {
					return false;
				}

				$has_mem = false;
				
				// check if the entries in "Visible For" matches current user membership
				foreach ( $entries['rcpe_role_visible'] as $entry ) {
					if ( in_array( $entry, (array) $this->get_mem_levels() ) ) {
						$has_mem = true;
					}
				}

				// If current user isn't in entries, don't show content
				if ( $has_mem === false ) {
					return false;
				}
			}

			// If Hidden for is not empty
			elseif( ! empty( $entries['rcpe_role_hidden'] ) ) {

				// If user is not Logged in, show content
				if ( $user_state == false ) {
					return true;
				}

				// Don't show content, if the selected user roles in Hidden For matches current user
				foreach ( $entries['rcpe_role_hidden'] as $entry ) {
					if ( in_array( $entry, (array) $this->get_mem_levels() ) ) {
						return false;
					}
				}
			}
		}
		// If toggle is off, show content
		return true;
	}
}
RCP_Elementor::get_instance()->init();
