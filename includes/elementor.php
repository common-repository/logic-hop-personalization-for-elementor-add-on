<?php

if (!defined('ABSPATH')) die;

/**
 * Elementor functionality.
 *
 * Provides Elementor functionality.
 *
 * @since      1.0.0
 * @package    LogicHop
 */

class LogicHop_Elementor {

	/**
	 * Logic Hop conditions
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $public    Logic Hop Public class
	 */
	private $public = null;

	/**
	 * Logic Hop conditions
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $conditions    Logic Hop Condition titles and slugs
	 */
	private $conditions = array( '_always' => 'Always Display' );

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    	1.0.0
	 * @param       object    $logic	LogicHop_Core functionality & logic.
	 */
	public function __construct () {
		$this->add_hooks_filters();
	}

	/**
	 * Add actions when Storefront theme is enabled
	 *
	 * @since    	1.0.0
	 * @param       object    $logic	LogicHop_Core functionality & logic.
	 */
	public function add_hooks_filters () {
		add_action( 'logichop_after_admin_hooks', array( $this, 'elementor_admin' ), 10, 1 );
		add_action( 'logichop_after_public_hooks', array( $this, 'logichop_public' ), 10, 1 );
		add_action( 'elementor/element/after_section_end', array( $this, 'elementor_add_condition_selector' ), 10, 3 );
		add_action( 'elementor/frontend/widget/before_render', array( $this, 'elementor_before_render' ), 10, 1 );
		add_action( 'elementor/frontend/section/before_render', array( $this, 'elementor_before_render' ), 10, 1 );
		add_action( 'elementor/frontend/widget/after_render', array( $this, 'elementor_after_render' ), 10, 1 );
		add_action( 'elementor/frontend/section/after_render', array( $this, 'elementor_after_render' ), 10, 1 );
		add_action( 'elementor/frontend/the_content', array( $this, 'elementor_content' ), 10, 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function logichop_init () {
	}

	public function logichop_public ( $public ) {
		$this->public = $public;
	}


	public function elementor_content ( $content ) {
		if ( $this->public ) {
			return $this->public->content_filter( $content );
		}
		return $content;
	}

	/**
	 * Enqueue and render Logic Hop tool palette
	 *
	 * @since    1.0.0
	 * @param    object		$element			Elementor section
	 * @param    string		$section_id			Elementor section ID
	 * @param    array		$args			Elementor section args
	 */
	 public function elementor_admin ( $admin ) {
		 add_action( 'elementor/editor/before_enqueue_scripts',
			function () use ( $admin ) {
				$conditions = $admin->conditions_get( true );

	 			if ( $conditions ) {
	 				foreach ( $conditions as $c ) {
	 					$this->conditions [ $c['slug'] ] = $c['name'];
	 				}
	 			}

				$admin->enqueue_styles( 'post.php' );
				$admin->enqueue_scripts( 'post.php' );
				$admin->editor_shortcode_modal( true );
			}
		);
	}

	/**
	 * Adds Logic Hop Condition controls to Elementor widgets
	 *
	 * @since    1.0.0
	 * @param    object		$element			Elementor section
	 * @param    string		$section_id			Elementor section ID
	 * @param    array		$args			Elementor section args
	 */
	 public function elementor_add_condition_selector ( $element, $section_id, $args ) {

		static $sections = [
			'section_custom_css_pro'
		];

		if ( ! in_array( $section_id, $sections ) ) {
			return;
		}

		$element->start_controls_section(
			'section_logichop_hop_condition_controls',
			[
				'label' => __( 'Logic Hop', 'logic-hop-for-elementor' ),
				'tab' => Elementor\Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'logic_hop_condition' ,
			[
				'label'        => __( 'Logic Hop Condition', 'logic-hop-for-elementor' ),
				'type'         => Elementor\Controls_Manager::SELECT,
				'default'      => '_always',
				'options'      => $this->conditions,
				'label_block'  => true,
			]
		);

		$element->add_control(
			'logic_hop_condition_not' ,
			[
				'label'        => __( 'Display When', 'logic-hop-for-elementor' ),
				'type'         => Elementor\Controls_Manager::SELECT,
				'default'      => 'met',
				'options'      => array( 'met' => 'Condition Met', 'not' => 'Condition Not Met' ),
				'label_block'  => true,
			]
		);

		$element->end_controls_section();
	}

	/**
	 * Output Logic Tag before Elementor widget renders
	 *
	 * @since    1.0.0
	 * @param    array		$widget			Elementor widget
	 */
	public function elementor_before_render ( $widget ) {
		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings = $widget->get_settings_for_display();
			if ( key_exists( 'logic_hop_condition', $settings ) && key_exists( 'logic_hop_condition_not', $settings ) ) {
				if ( $settings['logic_hop_condition'] != '_always' ) {
					$not = ( $settings['logic_hop_condition_not'] == 'met' ) ? '' : '!';
					printf( '{%% if condition: %s%s %%}', $not, $settings['logic_hop_condition'] );
				}
			}
		}
	}

	/**
	 * Output Logic Tag after Elementor widget renders
	 *
	 * @since    1.0.0
	 * @param    array		$widget			Elementor widget
	 */
	public function elementor_after_render ( $widget ) {
		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings = $widget->get_settings_for_display();
			if ( key_exists( 'logic_hop_condition', $settings ) && key_exists( 'logic_hop_condition_not', $settings ) ) {
				if ( $settings['logic_hop_condition'] != '_always' ) {
					print( '{% endif %}' );
				}
			}
		}
	}

	/**
	* Enqueue scripts
	*
	* @since    1.0.3
	*/
	public function enqueue_scripts () {
		wp_enqueue_script( 'logichop-elementor', plugin_dir_url( __FILE__ ) . 'elementor.js', array( 'logichop' ) );
	}
}
