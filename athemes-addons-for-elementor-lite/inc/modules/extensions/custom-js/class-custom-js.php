<?php

namespace aThemes_Addons\Extensions;

// Elementor classes
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Custom_JS {

	/**
	 * Instance
	 */     
	private static $instance;

	/**
	 * Initiator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}   

    public final function __construct() {
				
		add_action('elementor/documents/register_controls',[ $this, 'register_controls' ], 10 );
		add_filter( 'elementor/document/save/data', [ $this, 'strip_custom_js_on_save' ], 10, 2 );
		add_action( 'wp_footer', [ $this, 'print_custom_js' ] );
	}

	/**
	 * Print the page's custom JS. Only for authors who can post unfiltered HTML.
	 */
	public function print_custom_js() {
		// On archives the global post is just the last post in the loop.
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();

		if ( ! $post_id || ! class_exists( 'Elementor\Plugin' ) ) {
			return;
		}

		$author_id = (int) get_post_field( 'post_author', $post_id );

		if ( ! $author_id || ! user_can( $author_id, 'unfiltered_html' ) ) {
			return;
		}

		$document  = \Elementor\Plugin::$instance->documents->get_doc_for_frontend( $post_id );
		$custom_js = $document ? $document->get_settings( 'aafe_custom_js' ) : '';

		if ( ! empty( $custom_js ) ) {
			echo '<script id="aafe-custom-js" type="text/javascript">' . wp_kses( $custom_js, array() ) . '</script>';
		}
	}

	/**
	 * Drop custom JS saved by users who can't post unfiltered HTML.
	 */
	public function strip_custom_js_on_save( $data, $document = null ) {
		if ( current_user_can( 'unfiltered_html' ) ) {
			return $data;
		}

		if ( isset( $data['settings']['aafe_custom_js'] ) ) {
			unset( $data['settings']['aafe_custom_js'] );
		}

		return $data;
	}

    public function register_controls( $element ) {

		if ( ! current_user_can( 'unfiltered_html' ) ) {
			return;
		}

		$element->start_controls_section(
			'aafe_custom_js_section',
			[
                'tab'   => Controls_Manager::TAB_ADVANCED,
				'label' => '<i style="top:1px;" class="aafe-ele-svg-logo"></i>' . __( 'Custom JS', 'athemes-addons-for-elementor-lite' ),
			]
        );
        
		$element->add_control(
			'aafe_custom_js',
			[
				'label' => __( 'Custom JS', 'athemes-addons-for-elementor-lite' ),
				'type' => Controls_Manager::CODE,
				'language' => 'javascript',
				'rows' => 30,
				'render_type' => 'ui',
			]
		);

        $element->end_controls_section();
	}
}

Custom_JS::get_instance();