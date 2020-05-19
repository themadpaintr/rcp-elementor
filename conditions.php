<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Elementor classes
use Elementor\Controls_Manager;
use Elementor\Repeater;

// Custom classes
use RCPElementor\Classes\RCPE_Posts_Helper;

class RCP_Elementor_Content_Restriction extends RCP_Elementor {
	/*protected function content_restriction() {

	// Register controls to sections and widgets
	foreach( $this->locations as $where )
		add_action('elementor/element/'.$where['element'].'/section_custom_css/before_section_end', array( $this, 'add_controls' ), 10, 2 );
		echo '1st Function';
			
	}*/

	// Define controls
	public function add_controls( $element, $args ) {
		
		global $wp_roles;
		global $rcp_levels_db;
		
		// RCP Membership types and access levels 
		$levels   = $rcp_levels_db->get_levels( array( 'status' => 'active' ) );
		$memtypes = array();
		if ( ! empty( $levels ) ) {
		    foreach ( $levels as $level ) {
		        $memtypes[$level->id] = $level->name;
		    }
		}

		// Setting default interval values for Date Options
		$default_date_start = date( 'Y-m-d', strtotime( '-3 day' ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		$default_date_end 	= date( 'Y-m-d', strtotime( '+3 day' ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		$default_interval 	= $default_date_start . ' to ' . $default_date_end;

		$element_type = $element->get_type();

		// Adding Display Options toggle
		$element->add_control(
			'rcpe_display_conditions_enable',
			[
				'label'						=> __( 'Display Conditions', 'rcp-elementor' ),
				'type' 						=> Controls_Manager::SWITCHER,
				'default' 					=> '',
				'label_on' 					=> __( 'Yes', 'rcp-elementor' ),
				'label_off' 				=> __( 'No', 'rcp-elementor' ),
				'return_value'				=> 'yes',
				'frontend_available'		=> true,
			]
		);

		if ( 'widget' === $element_type ) {
			$element->add_control(
				'rcpe_display_conditions_output',
				[
					'label'					=> __( 'Output HTML', 'powerpack' ),
					'description'			=> sprintf( __( 'If enabled, the HTML code will exist on the page but the %s will be hidden using CSS.', 'powerpack' ), $element_type ),
					'default'				=> '',
					'type'					=> Controls_Manager::SWITCHER,
					'label_on' 				=> __( 'Yes', 'rcp-elementor' ),
					'label_off' 			=> __( 'No', 'rcp-elementor' ),
					'return_value' 			=> 'yes',
					'frontend_available'	=> true,
					'condition'				=> [
						'rcpe_display_conditions_enable' => 'yes',
					],
				]
			);
		}

		// Adding Display Options relation ANY, ALL
		$element->add_control(
			'rcpe_display_conditions_relation',
			[
				'label'						=> __( 'Display on', 'rcp-elementor' ),
				'type'						=> Controls_Manager::SELECT,
				'default'					=> 'all',
				'options'					=> [
					'all' 		=> __( 'All conditions met', 'rcp-elementor' ),
					'any' 		=> __( 'Any condition met', 'rcp-elementor' ),
				],
				'condition'					=> [
					'rcpe_display_conditions_enable' => 'yes',
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
							'static_page' 		=> __( 'Special (404, Home, Front, Blog)', 'rcp-elementor' ),
							'post_type' 		=> __( 'Post Type', 'rcp-elementor' ),
						],
					],
					[
						'label'					=> __( 'Archive', 'rcp-elementor' ),
						'options' 				=> [
							'taxonomy_archive' 	=> __( 'Taxonomy', 'rcp-elementor' ),
							'post_type_archive'	=> __( 'Post Type', 'rcp-elementor' ),
							'date_archive'		=> __( 'Date', 'rcp-elementor' ),
							'author_archive'	=> __( 'Author', 'rcp-elementor' ),
							'search_results'	=> __( 'Search', 'rcp-elementor' ),
						],
					],
					[
						'label'					=> __( 'Date & Time', 'rcp-elementor' ),
						'options'				=> [
							'date'				=> __( 'Current Date', 'rcp-elementor' ),
							'time'				=> __( 'Time of Day', 'rcp-elementor' ),
							'day'				=> __( 'Day of Week', 'rcp-elementor' ),
						],
					],
					[
						'label'					=> __( 'Membership', 'rcp-elementor' ),
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

		// RCP Membership type filter values
		$repeater->add_control(
			'rcpe_condition_memtype_value',
			[
				'type'				=> Controls_Manager::SELECT,
				'description'		=> __( 'Warning: This condition applies only to logged in visitors.', 'rcp-elementor' ),
				'default'			=> '',
				'label_block'		=> true,
				'options'			=> $memtypes,
				'condition'			=> [
					'rcpe_condition_key' => 'memtype',
				],
			]
		);
		
		// RCP Access level filter values
		$repeater->add_control(
			'rcpe_condition_accesslevel_value',
			[
				'type'				=> Controls_Manager::SELECT,
				'description'		=> __( 'Warning: This condition applies only to logged in visitors.', 'rcp-elementor' ),
				'default'			=> '',
				'label_block'		=> true,
				'options'			=> array(1,2,3,4,5,6,7,8,9,10),
				'condition'			=> [
					'rcpe_condition_key' => 'accesslevel',
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
				'options'			=> $wp_roles->get_names(),
				'condition'			=> [
					'rcpe_condition_key' => 'role',
				],
			]
		);

		// Options for date
		$repeater->add_control(
			'rcpe_condition_date_value',
			[
				'label'				=> __( 'In interval', 'rcp-elementor' ),
				'type'				=> \Elementor\Controls_Manager::DATE_TIME,
				'picker_options'	=> [
					'enableTime'	=> false,
					'mode' 			=> 'range',
				],
				'label_block'		=> true,
				'default'			=> $default_interval,
				'condition'			=> [
					'rcpe_condition_key' => 'date',
				],
			]
		);

		// Options for day
		$repeater->add_control(
			'rcpe_condition_day_value',
			[
				'label'				=> __( 'Day(s)', 'rcp-elementor' ),
				'type'				=> \Elementor\Controls_Manager::SELECT2,
				'options' => [
					'1' => __( 'Monday', 'rcp-elementor' ),
					'2' => __( 'Tuesday', 'rcp-elementor' ),
					'3' => __( 'Wednesday', 'rcp-elementor' ),
					'4' => __( 'Thursday', 'rcp-elementor' ),
					'5' => __( 'Friday', 'rcp-elementor' ),
					'6' => __( 'Saturday', 'rcp-elementor' ),
					'7' => __( 'Sunday', 'rcp-elementor' ),
				],
				'multiple'			=> true,
				'label_block'		=> true,
				'default' 			=> '',
				'condition' 		=> [
					'rcpe_condition_key' => 'day',
				],
			]
		);

		// Options for date and time
		$repeater->add_control(
			'rcpe_condition_time_value',
			[
				'label'				=> __( 'Before', 'rcp-elementor' ),
				'type'				=> \Elementor\Controls_Manager::DATE_TIME,
				'picker_options'	=> [
					'dateFormat' 	=> "H:i",
					'enableTime' 	=> true,
					'noCalendar' 	=> true,
				],
				'label_block'		=> true,
				'default' 			=> '',
				'condition' 		=> [
					'rcpe_condition_key' => 'time',
				],
			]
		);

		$os_options = $this->get_os_options();

		// Options for OS
		$repeater->add_control(
			'rcpe_condition_os_value',
			[
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> array_keys( $os_options )[0],
				'label_block' 	=> true,
				'options' 		=> $os_options,
				'condition' 	=> [
					'rcpe_condition_key' => 'os',
				],
			]
		);

		$browser_options = $this->get_browser_options();

		// Options for Browsers
		$repeater->add_control(
			'rcpe_condition_browser_value',
			[
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> array_keys( $browser_options )[0],
				'label_block' 	=> true,
				'options' 		=> $browser_options,
				'condition' 	=> [
					'rcpe_condition_key' => 'browser',
				],
			]
		);

		// Options for Search Bots
		$repeater->add_control(
			'rcpe_condition_search_bot_value',
			[
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> 'all_search_bots',
				'label_block' 	=> true,
				'options' 		=> ['all_search_bots' => 'All'],
				'condition' 	=> [
					'rcpe_condition_key' => 'search_bot',
				],
			]
		);

		// Options for Pages
		$repeater->add_control(
			'rcpe_condition_page_value',
			[
				'type'				=> 'pp-query',
				'default'			=> '',
				'multiple'			=> true,
				'label_block'		=> true,
				'placeholder'		=> __( 'Any', 'rcp-elementor' ),
				'description'		=> __( 'Leave blank for any page.', 'rcp-elementor' ),
				'query_type'		=> 'posts',
				'object_type'		=> 'page',
				'condition'			=> [
					'rcpe_condition_key' => 'page',
				],
			]
		);
		
		// Options for Posts
		$repeater->add_control(
			'rcpe_condition_post_value',
			[
				'type'				=> 'pp-query',
				'default'			=> '',
				'multiple'			=> true,
				'label_block'		=> true,
				'placeholder'		=> __( 'Any', 'rcp-elementor' ),
				'description'		=> __( 'Leave blank for any post.', 'rcp-elementor' ),
				'query_type'		=> 'posts',
				'object_type'		=> 'post',
				'condition'			=> [
					'rcpe_condition_key' => 'post',
				],
			]
		);

		// Options for Special Pages
		$repeater->add_control(
			'rcpe_condition_static_page_value',
			[
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> 'home',
				'label_block' 	=> true,
				'options' 		=> [
					'home'		=> __( 'Homepage', 'rcp-elementor' ),
					'static'	=> __( 'Front Page', 'rcp-elementor' ),
					'blog'		=> __( 'Blog', 'rcp-elementor' ),
					'404'		=> __( '404 Page', 'rcp-elementor' ),
				],
				'condition' 	=> [
					'rcpe_condition_key' => 'static_page',
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
				'options' 		=> RCPE_Posts_Helper::get_post_types(),
				'condition' 	=> [
					'rcpe_condition_key' => 'post_type',
				],
			]
		);

		// Options for Custom Taxonomies
		$repeater->add_control(
			'rcpe_condition_taxonomy_archive_value',
			[
				'type' 			=> Controls_Manager::SELECT2,
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'rcp-elementor' ),
				'description'	=> __( 'Leave blank or select all for any taxonomy.', 'rcp-elementor' ),
				'multiple'		=> true,
				'label_block' 	=> true,
				'options' 		=> RCPE_Posts_Helper::get_taxonomies_options(),
				'condition' 	=> [
					'rcpe_condition_key' => 'taxonomy_archive',
				],
			]
		);

		// Options for Custom Post Type Archives
		$repeater->add_control(
			'rcpe_condition_post_type_archive_value',
			[
				'type' 			=> Controls_Manager::SELECT2,
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'rcp-elementor' ),
				'description'	=> __( 'Leave blank or select all for any post type.', 'rcp-elementor' ),
				'multiple'		=> true,
				'label_block' 	=> true,
				'options' 		=> RCPE_Posts_Helper::get_post_types(),
				'condition' 	=> [
					'rcpe_condition_key' => 'post_type_archive',
				],
			]
		);

		// Options for Date Archives
		$repeater->add_control(
			'rcpe_condition_date_archive_value',
			[
				'type' 			=> Controls_Manager::SELECT2,
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'rcp-elementor' ),
				'description'	=> __( 'Leave blank or select all for any date based archive.', 'rcp-elementor' ),
				'multiple'		=> true,
				'label_block' 	=> true,
				'options' 		=> [
					'day'		=> __( 'Day', 'rcp-elementor' ),
					'month'		=> __( 'Month', 'rcp-elementor' ),
					'year'		=> __( 'Year', 'rcp-elementor' ),
				],
				'condition' 	=> [
					'rcpe_condition_key' => 'date_archive',
				],
			]
		);

		// Options for Author Archive
		$repeater->add_control(
            'rcpe_condition_author_archive_value',
            [
				'type'					=> 'rcpe-query',
				'placeholder'			=> __( 'Any', 'rcp-elementor' ),
				'description'			=> __( 'Leave blank for all authors.', 'rcp-elementor' ),
				'label_block'			=> true,
				'multiple'				=> true,
				'query_type'			=> 'authors',
				'condition'				=> [
					'rcpe_condition_key' 	=> 'author_archive',
				],
            ]
        );

		// Options for Search Results
		$repeater->add_control(
			'rcpe_condition_search_results_value',
			[
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> '',
				'placeholder'	=> __( 'Keywords', 'rcp-elementor' ),
				'description'	=> __( 'Enter keywords, separated by commas, to condition the display on specific keywords and leave blank for any.', 'rcp-elementor' ),
				'label_block' 	=> true,
				'condition' 	=> [
					'rcpe_condition_key' => 'search_results',
				],
			]
		);

		// Option defaults
		$element->add_control(
			'rcpe_display_Options',
			[
				'label' 	=> __( 'Options', 'rcp-elementor' ),
				'type' 		=> Controls_Manager::REPEATER,
				'default' 	=> [
					[
						'rcpe_condition_key' 					=> 'authentication',
						'rcpe_condition_operator' 				=> 'is',
						'rcpe_condition_authentication_value' 	=> 'authenticated',
					],
				],
				'condition'		=> [
					'rcpe_display_Options_enable' => 'yes',
				],
				'fields' 		=> $repeater->get_controls(),
				'title_field' 	=> 'Condition',
			]
		);

	}

	/**
	 * Get OS options for control
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 */
	protected function get_os_options() {
		return [
			'iphone' 		=> 'iPhone',
			'android' 		=> 'Android',
			'windows' 		=> 'Windows',
			'open_bsd'		=> 'OpenBSD',
			'sun_os'    	=> 'SunOS',
			'linux'     	=> 'Linux',
			'mac_os'    	=> 'Mac OS',
		];
	}

	/**
	 * Get browser options for control
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 */
	protected function get_browser_options() {
		return [
			'ie'			=> 'Internet Explorer',
			'firefox'		=> 'Mozilla Firefox',
			'chrome'		=> 'Google Chrome',
			'opera_mini'	=> 'Opera Mini',
			'opera'			=> 'Opera',
			'safari'		=> 'Safari',
			'edge'			=> 'Microsoft Edge',
		];
	}

	/**
	 * Add Actions
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 */
	protected function add_actions() {

		// Conditions for widgets
		add_action( 'elementor/widget/render_content', function( $widget_content, $element ) {

			$settings = $element->get_settings();

			if ( 'yes' === $settings[ 'rcpe_display_conditions_enable' ] ) {

				// Set the conditions
				$this->set_conditions( $element->get_id(), $settings['rcpe_display_conditions'] );

				// if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				// 	ob_start();
				// 	$this->render_editor_notice( $settings );
				// 	$widget_content .= ob_get_clean();
				// }

				if ( ! $this->is_visible( $element->get_id(), $settings['rcpe_display_conditions_relation'] ) ) { // Check the conditions
					if ( 'yes' !== $settings['rcpe_display_conditions_output'] ) {
						return; // And on frontend we stop the rendering of the widget
					}
				}
			}
   
			return $widget_content;
		
		}, 10, 2 );

		// Conditions for widgets
		add_action( 'elementor/frontend/widget/before_render', function( $element ) {
			
			$settings = $element->get_settings();

			if ( 'yes' === $settings[ 'rcpe_display_conditions_enable' ] ) {

				// Set the conditions
				$this->set_conditions( $element->get_id(), $settings['rcpe_display_conditions'] );

				if ( ! $this->is_visible( $element->get_id(), $settings['rcpe_display_conditions_relation'] ) ) { // Check the conditions
					$element->add_render_attribute( '_wrapper', 'class', 'pp-visibility-hidden' );
				}
			}

		}, 10, 1 );

		// Conditions for sections
		add_action( 'elementor/frontend/section/before_render', function( $element ) {
			
			$settings = $element->get_settings();

			if ( 'yes' === $settings[ 'rcpe_display_conditions_enable' ] ) {

				// Set the conditions
				$this->set_conditions( $element->get_id(), $settings['rcpe_display_conditions'] );

				if ( ! $this->is_visible( $element->get_id(), $settings['rcpe_display_conditions_relation'] ) ) { // Check the conditions
					$element->add_render_attribute( '_wrapper', 'class', 'pp-visibility-hidden' );
				}
			}

		}, 10, 1 );

	}

	protected function render_editor_notice( $settings ) {
		?><span><?php _e( 'This widget is displayed conditionally.', 'rcp-elementor' ); ?></span>
		<?php
	}

	/**
	 * Set conditions.
	 *
	 * Sets the conditions property to all conditions comparison values
	 *
	 * @since 1.4.7
	 * @access protected
	 * @static
	 *
	 * @param mixed  $conditions  The conditions from the repeater field control
	 *
	 * @return void
	 */
	protected function set_conditions( $id, $conditions = [] ) {
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
	 * @since 1.4.7
	 * @access protected
	 * @static
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

	/**
	 * Compare conditions.
	 *
	 * Checks two values against an operator
	 *
	 * @since 1.4.7
	 * @access protected
	 * @static
	 *
	 * @param mixed  $left_value  First value to compare.
	 * @param mixed  $right_value Second value to compare.
	 * @param string $operator    Comparison operator.
	 *
	 * @return bool
	 */
	protected static function compare( $left_value, $right_value, $operator ) {
		switch ( $operator ) {
			case 'is':
				return $left_value == $right_value;
			case 'not':
				return $left_value != $right_value;
			default:
				return $left_value === $right_value;
		}
	}

	/**
	 * Check user login status
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_authentication( $value, $operator ) {
		return self::compare( is_user_logged_in(), true, $operator );
	}

	/**
	 * Check user role
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_role( $value, $operator ) {

		$user = wp_get_current_user();
		return self::compare( is_user_logged_in() && in_array( $value, $user->roles ), true, $operator );
	}
	 
	/**
	 * Check RCP Membership Type
	 *
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_memtype( $value, $operator ) {

		$user = wp_get_current_user();
		return self::compare( is_user_logged_in() && rcp_user_has_active_membership() && $value == rcp_get_subscription_id(get_current_user_id()), true, $operator );
	}
	 
	/**
	 * Check RCP Access Level
	 *
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_accesslevel( $value, $operator ) {

		$user = wp_get_current_user();
		return self::compare( is_user_logged_in() && rcp_user_has_access(get_current_user_id(), $value ), true, $operator );
	}

	/**
	 * Check date interval
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_date( $value, $operator ) {

		// Split control valur into two dates
		$intervals = explode( 'to' , preg_replace('/\s+/', '', $value ) );

		// Make sure the explode return an array with exactly 2 indexes
		if ( ! is_array( $intervals ) || 2 !== count( $intervals ) ) 
			return;

		// Set start and end dates
		$start 	= $intervals[0];
		$end 	= $intervals[1];
		$today 	= date('Y-m-d');

		// Default returned bool to false
		$show 	= false;

		// Check vars
		if ( \DateTime::createFromFormat( 'Y-m-d', $start ) === false || // Make sure it's a date
			 \DateTime::createFromFormat( 'Y-m-d', $end ) === false ) // Make sure it's a date
			return;

		// Convert to timestamp
		$start_ts 	= strtotime( $start ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		$end_ts 	= strtotime( $end ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		$today_ts 	= strtotime( $today ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		// Check that user date is between start & end
		$show = ( ($today_ts >= $start_ts ) && ( $today_ts <= $end_ts ) );

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check time of day interval
	 *
	 * Checks wether current time is in given interval
	 * in order to display element
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_time( $value, $operator ) {

		// Split control valur into two dates
		$time 	= date( 'H:i', strtotime( preg_replace('/\s+/', '', $value ) ) );
		$now 	= date( 'H:i', strtotime("now") + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );

		// Default returned bool to false
		$show 	= false;

		// Check vars
		if ( \DateTime::createFromFormat( 'H:i', $time ) === false ) // Make sure it's a valid DateTime format
			return;

		// Convert to timestamp
		$time_ts 	= strtotime( $time );
		$now_ts 	= strtotime( $now );

		// Check that user date is between start & end
		$show = ( $now_ts < $time_ts );

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check day of week
	 *
	 * Checks wether today falls inside a
	 * specified day of the week
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_day( $value, $operator ) {

		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( $_value === date( 'w' ) ) {
					$show = true; break;
				}
			}
		} else { $show = $value === date( 'w' ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check operating system of visitor
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_os( $value, $operator ) {

		$oses = [
			'iphone'            => '(iPhone)',
			'android'            => '(Android)',
			'windows' 			=> 'Win16|(Windows 95)|(Win95)|(Windows_95)|(Windows 98)|(Win98)|(Windows NT 5.0)|(Windows 2000)|(Windows NT 5.1)|(Windows XP)|(Windows NT 5.2)|(Windows NT 6.0)|(Windows Vista)|(Windows NT 6.1)|(Windows 7)|(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)|Windows ME',
			'open_bsd'          => 'OpenBSD',
			'sun_os'            => 'SunOS',
			'linux'             => '(Linux)|(X11)',
			'mac_os'            => '(Mac_PowerPC)|(Macintosh)',
		];

		return self::compare( preg_match('@' . $oses[ $value ] . '@', $_SERVER['HTTP_USER_AGENT'] ), true, $operator );
	}

	/**
	 * Check operating system of visitor
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_search_bot( $value, $operator ) {

		$search_bot = [
			'all_search_bots'        => '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)',
		];

		return self::compare( preg_match('@' . $search_bot[ $value ] . '@', $_SERVER['HTTP_USER_AGENT'] ), true, $operator );
	}

	/**
	 * Check browser of visitor
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_browser( $value, $operator ) {

		$browsers = [
			'ie'			=> [
				'MSIE',
				'Trident',
			],
			'firefox'		=> 'Firefox',
			'chrome'		=> 'Chrome',
			'opera_mini'	=> 'Opera Mini',
			'opera'			=> 'Opera',
			'safari'		=> 'Safari',
			'edge'			=> 'Edge',
		];

		$show = false;

		if ( 'ie' === $value ) {
			if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], $browsers[ $value ][0] ) || false !== strpos( $_SERVER['HTTP_USER_AGENT'], $browsers[ $value ][1] ) ) {
				$show = true;
			}
		} else {
			if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], $browsers[ $value ] ) ) {
				$show = true;

				// Additional check for Chrome that returns Safari
				if ( 'safari' === $value || 'firefox' === $value ) {
					if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'Chrome' ) ) {
						$show = false;
					}
				}
			}
		}
		

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current page
	 *
	 * @since 2.1.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_page( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_page( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_page( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current post
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_post( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_single( $_value ) || is_singular( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_single( $value ) || is_singular( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check static page
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_static_page( $value, $operator ) {

		if ( 'home' === $value ) {
			return self::compare( ( is_front_page() && is_home() ), true, $operator );
		} elseif ( 'static' === $value ) {
			return self::compare( ( is_front_page() && ! is_home() ), true, $operator );
		} elseif ( 'blog' === $value ) {
			return self::compare( ( ! is_front_page() && is_home() ), true, $operator );
		} elseif ( '404' === $value ) {
			return self::compare( is_404(), true, $operator );
		}
	}

	/**
	 * Check current post type
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_post_type( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_singular( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_singular( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current taxonomy archive
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_taxonomy_archive( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {

				$show = self::check_taxonomy_archive_type( $_value );

				if ( $show ) break;
			}
		} else { $show = self::check_taxonomy_archive_type( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Checks a given taxonomy against the current page template
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param string  $taxonomy  The taxonomy to check against
	 */
	protected static function check_taxonomy_archive_type( $taxonomy ) {
		if ( 'category' === $taxonomy ) {
			return is_category();
		} else if ( 'post_tag' === $taxonomy ) {
			return is_tag();
		} else if ( '' === $taxonomy || empty( $taxonomy ) ) {
			return is_tax() || is_category() || is_tag();
		} else {
			return is_tax( $taxonomy );
		}

		return false;
	}

	/**
	 * Check current post type archive
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_post_type_archive( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_post_type_archive( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_post_type_archive( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current date archive
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_date_archive( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( self::check_date_archive_type( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_date( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Checks a given date type against the current page template
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param string  $type  The type of date archive to check against
	 */
	protected static function check_date_archive_type( $type ) {
		if ( 'day' === $type ) { // Day
			return is_day();
		} elseif ( 'month' === $type ) { // Month
			return is_month();
		} elseif ( 'year' === $type ) { // Year
			return is_year();
		}

		return false;
	}

	/**
	 * Check current author archive
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_author_archive( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_author( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_author( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current search query
	 *
	 * @since 1.4.7
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_search_results( $value, $operator ) {
		$show = false;

		if ( is_search() ) {

			if ( empty( $value ) ) { // We're showing on all search pages

				$show = true;

			} else { // We're showing on specific keywords

				$phrase = get_search_query(); // The user search query

				if ( '' !== $phrase && ! empty( $phrase ) ) { // Only proceed if there is a query

					$keywords = explode( ',', $value ); // Separate keywords

					foreach ( $keywords as $index => $keyword ) {
						if ( self::keyword_exists( trim( $keyword ), $phrase ) ) {
							$show = true; break;
						}
					}
				}
			}
		}

		return self::compare( $show, true, $operator );
	}
}

new RCP_Elementor_Content_Restriction;
