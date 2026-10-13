<?php
/**
 * aThemes_Addons_Custom_JS_Guard Class.
 *
 * Keeps users without unfiltered_html from adding or changing Custom JS through any
 * page-settings write (editor save, page-settings AJAX, REST meta). Loaded whether or
 * not the Custom JS module is active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'aThemes_Addons_Custom_JS_Guard' ) ) {

	class aThemes_Addons_Custom_JS_Guard {

		const META_KEY    = '_elementor_page_settings';
		const SETTING_KEY = 'aafe_custom_js';

		/**
		 * Set while the guard writes the corrected value, so it doesn't guard itself.
		 */
		private static $writing = false;

		public static function init() {
			add_filter( 'add_post_metadata', array( __CLASS__, 'guard' ), 10, 4 );
			add_filter( 'update_post_metadata', array( __CLASS__, 'guard' ), 10, 4 );
			add_filter( 'delete_post_metadata', array( __CLASS__, 'guard_delete' ), 10, 5 );
		}

		/**
		 * Users who may add or change Custom JS.
		 */
		private static function is_trusted_writer() {
			return current_user_can( 'unfiltered_html' ) || ( defined( 'WP_CLI' ) && WP_CLI );
		}

		/**
		 * Write the page settings with the stored Custom JS kept as it was.
		 * Unchanged JS passes straight through, so background saves keep admin JS.
		 */
		public static function guard( $check, $post_id, $meta_key, $meta_value ) {
			if ( self::META_KEY !== $meta_key || self::$writing || null !== $check || ! is_array( $meta_value ) ) {
				return $check;
			}

			if ( self::is_trusted_writer() ) {
				return $check;
			}

			// Revisions and autosaves are compared with their parent's stored JS.
			$parent_id = wp_is_post_revision( $post_id );
			$stored    = get_post_meta( $parent_id ? $parent_id : $post_id, self::META_KEY, true );
			$old       = is_array( $stored ) && isset( $stored[ self::SETTING_KEY ] ) ? $stored[ self::SETTING_KEY ] : null;
			$new       = isset( $meta_value[ self::SETTING_KEY ] ) ? $meta_value[ self::SETTING_KEY ] : null;

			if ( $new === $old ) {
				return $check;
			}

			if ( null === $old ) {
				unset( $meta_value[ self::SETTING_KEY ] );
			} else {
				$meta_value[ self::SETTING_KEY ] = $old;
			}

			self::$writing = true;
			update_post_meta( $post_id, self::META_KEY, wp_slash( $meta_value ) );
			self::$writing = false;

			return true;
		}

		/**
		 * Elementor deletes the page settings when a save leaves none, which would also
		 * drop the stored Custom JS. Keep the JS instead.
		 */
		public static function guard_delete( $check, $post_id, $meta_key, $meta_value, $delete_all ) {
			if ( self::META_KEY !== $meta_key || self::$writing || null !== $check || $delete_all ) {
				return $check;
			}

			if ( self::is_trusted_writer() ) {
				return $check;
			}

			$stored = get_post_meta( $post_id, self::META_KEY, true );

			if ( ! is_array( $stored ) || ! isset( $stored[ self::SETTING_KEY ] ) ) {
				return $check;
			}

			self::$writing = true;
			update_post_meta( $post_id, self::META_KEY, wp_slash( array( self::SETTING_KEY => $stored[ self::SETTING_KEY ] ) ) );
			self::$writing = false;

			return true;
		}
	}

	aThemes_Addons_Custom_JS_Guard::init();
}
