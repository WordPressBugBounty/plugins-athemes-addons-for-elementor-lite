<?php
namespace aThemesAddons;

use Elementor\Core\Common\Modules\Ajax\Module as Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Template_Library_Manager {

	protected static $source = null;

	public static function init() {
		add_action( 'elementor/editor/footer', [ __CLASS__, 'print_template_views' ] );
		add_action( 'elementor/ajax/register_actions', [ __CLASS__, 'register_ajax_actions' ] );
		add_action( 'elementor/preview/enqueue_styles', [ __CLASS__, 'enqueue_preview_styles' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'editor_scripts' ] );
	}

	public static function print_template_views() {
		require ATHEMES_AFE_DIR . 'inc/library/templates.php';
	}

	public static function enqueue_preview_styles() {
		wp_enqueue_style( 'athemes-addons-template-preview-style', ATHEMES_AFE_URI . 'inc/library/template-preview.min.css', '1.0.0' );

	}

	public static function editor_scripts() {
        wp_enqueue_script( 'athemes-addons-template-library-script', ATHEMES_AFE_URI . 'inc/library/template-library.min.js', [ 'elementor-editor', 'jquery-hover-intent' ], '1.0.0', true );
		wp_enqueue_style( 'athemes-addons-template-library-style', ATHEMES_AFE_URI . 'inc/library/template-library.min.css', '1.0.0' );

		$localized_data = [
            'aafeProWidgets' => [],
			'isProActive' => false,
			'i18n' => [
				'templatesEmptyTitle' => esc_html__( 'No Templates Found', 'athemes-addons-elementor' ),
				'templatesEmptyMessage' => esc_html__( 'Try different category or sync for new templates.', 'athemes-addons-elementor' ),
				'templatesNoResultsTitle' => esc_html__( 'No Results Found', 'athemes-addons-elementor' ),
				'templatesNoResultsMessage' => esc_html__( 'Please make sure your search is spelled correctly or try a different word.', 'athemes-addons-elementor' ),
			]
	
        ];

		wp_localize_script( 'athemes-addons-template-library-script', 'aafeEditor', $localized_data );
	}

	/**
	 * Undocumented function
	 *
	 * @return Template_Library_Source
	 */
	public static function get_source() {
		if ( is_null( self::$source ) ) {
			self::$source = new Template_Library_Source();
		}

		return self::$source;
	}

	public static function register_ajax_actions( Ajax $ajax ) {
		$ajax->register_ajax_action( 'aafe_get_template_library_data', function( $data ) {
			if ( ! current_user_can( 'edit_posts' ) ) {
				throw new \Exception( 'Access Denied' );
			}

			if ( ! empty( $data['editor_post_id'] ) ) {
				$editor_post_id = absint( $data['editor_post_id'] );

				if ( ! get_post( $editor_post_id ) ) {
					throw new \Exception( __( 'Post not found.', 'athemes-addons-elementor' ) );
				}

				\Elementor\Plugin::instance()->db->switch_to_post( $editor_post_id );
			}

			$result = self::get_library_data( $data );

			return $result;
		} );

		$ajax->register_ajax_action( 'aafe_get_template_item_data', function( $data ) {
			if ( ! current_user_can( 'edit_posts' ) ) {
				throw new \Exception( 'Access Denied' );
			}

			if ( ! empty( $data['editor_post_id'] ) ) {
				$editor_post_id = absint( $data['editor_post_id'] );

				if ( ! get_post( $editor_post_id ) ) {
					throw new \Exception( __( 'Post not found', 'athemes-addons-elementor' ) );
				}

				\Elementor\Plugin::instance()->db->switch_to_post( $editor_post_id );
			}

			if ( empty( $data['template_id'] ) ) {
				throw new \Exception( __( 'Template id missing', 'athemes-addons-elementor' ) );
			}

			$result = self::get_template_data( $data );

			return $result;
		} );
	}

	public static function get_template_data( array $args ) {
		$source = self::get_source();
		$data = $source->get_data( $args );
		return $data;
	}

	public static function get_library_data( array $args ) {
		$source = self::get_source();

		if ( ! empty( $args['sync'] ) ) {
			Template_Library_Source::get_library_data( true );
		}

		return [
			'templates' => $source->get_items(),
			'category' => $source->get_categories(),
			'type_category' => $source->get_type_category(),
		];
	}
}

Template_Library_Manager::init();
