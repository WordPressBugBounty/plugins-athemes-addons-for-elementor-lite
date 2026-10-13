<?php
/**
 * aThemes_Addons_Helper Class.
 */

use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'aThemes_Addons_Helper' ) ) {

	class aThemes_Addons_Helper {

		/**
		 * The single class instance.
		 */
		private static $instance = null;    

		/**
		 * Instance.
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Get settings from an Elementor widget.
		 */
		public static function get_widget_settings( $page_id, $widget_id ) {
			$document = Plugin::$instance->documents->get( $page_id );
			$settings = [];
			if ( $document ) {
				$elements    = Plugin::instance()->documents->get( $page_id )->get_elements_data();
				$widget_data = self::find_element_recursive( $elements, $widget_id );
				
				if ( !empty( $widget_data ) && is_array( $widget_data ) ) {
					$widget      = Plugin::instance()->elements_manager->create_element_instance( $widget_data );
				}
				if ( !empty( $widget ) ) {
					$settings    = $widget->get_settings_for_display();
				}
			}

			return $settings;
		}

		/**
		 * Whether a template ID is a published Elementor library template safe to render.
		 */
		public static function is_renderable_template( $template_id ) {
			$template_id = absint( $template_id );

			return $template_id
				&& 'elementor_library' === get_post_type( $template_id )
				&& 'publish' === get_post_status( $template_id );
		}

		/**
		 * Whether a template ID is a published Theme Builder archive-item template (Posts List items).
		 */
		public static function is_renderable_item_template( $template_id ) {
			$template_id = absint( $template_id );

			return $template_id
				&& 'aafe_templates' === get_post_type( $template_id )
				&& 'publish' === get_post_status( $template_id )
				&& 'archive-item' === get_post_meta( $template_id, '_ahf_template_type', true );
		}

		/**
		 * Whether a post type may be listed by the editor query endpoints.
		 * 'any' is allowed: WP_Query already skips types excluded from search.
		 */
		public static function is_queryable_post_type( $post_type ) {
			if ( 'any' === $post_type ) {
				return true;
			}

			return is_string( $post_type ) && post_type_exists( $post_type ) && is_post_type_viewable( $post_type );
		}

		/**
		 * Publicly viewable taxonomies.
		 */
		public static function get_queryable_taxonomies() {
			return array_values( array_filter( get_taxonomies(), 'is_taxonomy_viewable' ) );
		}

		/**
		 * Find an element in the elements data.
		 */
		public static function find_element_recursive( $elements, $element_id ) {
			foreach ( $elements as $element ) {
				if ( $element['id'] === $element_id ) {
					return $element;
				}

				if ( ! empty( $element['elements'] ) ) {
					$found = self::find_element_recursive( $element['elements'], $element_id );
					if ( $found ) {
						return $found;
					}
				}
			}

			return null;
		}
	}
}