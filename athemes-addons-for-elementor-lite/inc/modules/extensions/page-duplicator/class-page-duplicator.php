<?php

namespace aThemes_Addons\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Page_Duplicator {

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
		add_action( 'admin_action_aafe_duplicate', array( $this, 'duplicate' ) );
		add_filter( 'page_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
	}

	/**
	 * Add Page Duplicator Button
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function row_actions( $actions, $post ) {

		global $pagenow;
		global $post;

		if ( current_user_can( 'edit_others_posts' ) && $this->is_allowed_post_type( $post->post_type ) ) {
			$duplicate_url            = admin_url( 'admin.php?action=aafe_duplicate&post=' . $post->ID );
			$duplicate_url            = wp_nonce_url( $duplicate_url, 'aafe_duplicator_' . $post->ID );
			$actions['aafe_duplicate'] = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$duplicate_url,
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Duplicate %s', 'athemes-addons-for-elementor-lite' ), $post->post_title ) ),
				esc_html__( 'aThemes Duplicator', 'athemes-addons-for-elementor-lite' )
			);
		}

		return $actions;
	}

	/**
	 * Whether the duplicator is enabled for a post type.
	 */
	private function is_allowed_post_type( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object || empty( $post_type_object->show_ui ) ) {
			return false;
		}

		$settings   = get_option( 'athemes-addons-settings' );
		$enabled_on = 'all'; // Default to 'all' post types

		if ( ! empty( $settings ) && isset( $settings['aafe_duplicator_post_types'] ) && ! empty( $settings['aafe_duplicator_post_types'] ) ) {
			$enabled_on = $settings['aafe_duplicator_post_types'];
		}

		$enabled_on = explode( ',', $enabled_on );

		return in_array( 'all', $enabled_on, true ) || in_array( $post_type, $enabled_on, true );
	}

	/**
	 * Meta value to copy. Custom JS is dropped when the source author can't post
	 * unfiltered HTML, because the copy is owned by the current user.
	 */
	public static function copyable_meta_value( $meta_key, $meta_value, $source_author_id ) {
		if ( '_elementor_page_settings' !== $meta_key || user_can( (int) $source_author_id, 'unfiltered_html' ) ) {
			return $meta_value;
		}

		$page_settings = maybe_unserialize( $meta_value );

		if ( ! is_array( $page_settings ) || ! isset( $page_settings['aafe_custom_js'] ) ) {
			return $meta_value;
		}

		unset( $page_settings['aafe_custom_js'] );

		return maybe_serialize( $page_settings );
	}

	/**
	 * Duplicate Page
	 */
	public function duplicate() {
		
		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		$post_id = absint( $_GET['post'] );

		if ( ! current_user_can( 'edit_others_posts' ) || ! current_user_can( 'edit_post', $post_id ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'aafe_duplicator_' . $post_id ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post || ! $this->is_allowed_post_type( $post->post_type ) ) {
			return;
		}

		$current_user = wp_get_current_user();

		$new_post_id = wp_insert_post( array(
			'post_title'    => $post->post_title . ' - ' . __( 'Copy', 'athemes-addons-for-elementor-lite' ),
			'post_content'  => $post->post_content,
			'post_status'   => 'draft',
			'post_type'     => $post->post_type,
			'post_excerpt'  => $post->post_excerpt,
			'post_parent'   => $post->post_parent,
			'post_password' => $post->post_password,
			'post_author'   => $current_user->ID,
		) );

		$redirect_url = admin_url( 'edit.php?post_type=' . $post->post_type );

		if ( $new_post_id ) {
			$taxonomies = get_object_taxonomies( $post->post_type );

			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_object_terms( $post_id, $taxonomy );

				foreach ( $terms as $term ) {
					wp_set_object_terms( $new_post_id, $term->term_id, $taxonomy, true );
				}
			}

			global $wpdb;
			$post_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! empty( $post_meta ) && is_array( $post_meta ) ) {

				$new_query = "INSERT INTO $wpdb->postmeta ( post_id, meta_key, meta_value ) VALUES ";
				$insert = '';

				foreach ( $post_meta as $meta_info ) {

					$meta_key   = sanitize_text_field( $meta_info->meta_key );
					$meta_value = self::copyable_meta_value( $meta_key, $meta_info->meta_value, $post->post_author );
					
					$exclude_meta_keys = [ '_wc_average_rating', '_wc_review_count', '_wc_rating_count' ];
					
					if ( in_array($meta_key, $exclude_meta_keys, true ) ){
						continue;
					}

					if ( $meta_key === '_elementor_template_type' ) {
						delete_post_meta( $new_post_id, '_elementor_template_type' );
					}

					if ( ! empty( $insert ) ) {
						$insert .= ', ';
					}

					$insert .= $wpdb->prepare( '(%d, %s, %s)', $new_post_id, $meta_key, $meta_value );
				}

				$wpdb->query( $new_query . $insert ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}
}

Page_Duplicator::get_instance();