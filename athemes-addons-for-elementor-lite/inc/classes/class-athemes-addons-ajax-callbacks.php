<?php
/**
 * aThemes_Addons_Ajax_Callbacks Class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'aThemes_Addons_Ajax_Callbacks' ) ) {

	class aThemes_Addons_Ajax_Callbacks {

		/**
		 * Constructor.
		 * 
		 */
		public function __construct() {

			//Product filter.
			add_action( 'wp_ajax_aafe_product_filter', array( $this, 'product_filter' ) );
			add_action( 'wp_ajax_nopriv_aafe_product_filter', array( $this, 'product_filter' ) );

			//Select 2.
			add_action( 'wp_ajax_aafe_posts_filter_autocomplete', [ $this, 'posts_filter_autocomplete' ] );
			add_action( 'wp_ajax_aafe_get_posts_value_titles', [ $this, 'get_posts_value_titles' ] );
		}

		/**
		 * Product filter.
		 */
		public function product_filter() {
			check_ajax_referer( 'aafe-product-filter-nonce', 'nonce' );

			$categories         = ( isset( $_POST['categories'] ) ) ? sanitize_text_field( wp_unslash( $_POST['categories'] ) ) : array();
			$display_mode       = ( isset( $_POST['display_mode'] ) ) ? sanitize_text_field( wp_unslash( $_POST['display_mode'] ) ) : '';
			$filter_term        = ( isset( $_POST['term'] ) ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
			$posts_per_page     = ( isset( $_POST['posts_per_page'] ) ) ? absint( wp_unslash( $_POST['posts_per_page'] ) ) : 3;
			$posts_per_page     = min( max( 1, $posts_per_page ), 100 ); // Clamp; blocks -1 and oversized values.
			$offset             = ( isset( $_POST['offset'] ) ) ? absint( wp_unslash( $_POST['offset'] ) ) : 0;
			$orderby            = ( isset( $_POST['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'date';
			$order              = ( isset( $_POST['order'] ) ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'desc';
			$load_more          = ( isset( $_POST['load_more'] ) ) ? sanitize_text_field( wp_unslash( $_POST['load_more'] ) ) : false;
			$max_pages          = ( isset( $_POST['max_pages'] ) ) ? sanitize_text_field( wp_unslash( $_POST['max_pages'] ) ) : 1;
			$current_page       = ( isset( $_POST['current_page'] ) ) ? sanitize_text_field( wp_unslash( $_POST['current_page'] ) ) : 1;
			$widget_id          = ( isset( $_POST['widget_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['widget_id'] ) ) : '';
			$page_id            = ( isset( $_POST['page_id'] ) ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;

			if ( empty( $widget_id ) || empty( $page_id ) ) {
				wp_send_json_error( __( 'Invalid widget ID or page ID', 'athemes-addons-for-elementor-lite' ) );
			}

			// Restrict which document a caller can load (handler is also nopriv).
			$page_status        = get_post_status( $page_id );
			$page_status_object = $page_status ? get_post_status_object( $page_status ) : null;
			$page_is_public     = $page_status_object && $page_status_object->public;

			if ( ! $page_is_public && ! current_user_can( 'read_post', $page_id ) ) {
				wp_send_json_error( __( 'You are not allowed to access this content', 'athemes-addons-for-elementor-lite' ) );
			}

			// Get widget settings.
			$settings = aThemes_Addons_Helper::get_widget_settings( $page_id, $widget_id );

			if ( empty( $settings ) ) {
				wp_send_json_error( __( 'Invalid widget settings', 'athemes-addons-for-elementor-lite' ) );
			}
			
			$query_args = array(
				'post_type'         => 'product',
				'post_status'       => 'publish',
				'posts_per_page'    => $posts_per_page,
				'offset'            => $offset,
				'orderby'           => $orderby,
				'order'             => $order,
			);

			if ( 'all' === $filter_term ) {
				$terms = explode( ',', $categories );
			} else {
				$terms = $filter_term;
			}

			if ( '' !== $categories ) {
				$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => $terms,
					),
				);
			}

			switch ( $display_mode ) {
				case 'featured':
					$query_args['meta_query'] = WC()->query->get_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					$query_args['post__in'] = wc_get_featured_product_ids();
					break;
				case 'sale':
					$query_args['meta_query'] = WC()->query->get_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					$query_args['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
					break;
			}

			if ( true == $load_more ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
				$query_args['paged'] = $current_page + 1;

				unset( $query_args['offset'] );

				if ( $current_page > $max_pages ) {
					wp_send_json_error( __( 'No more products to load', 'athemes-addons-for-elementor-lite' ) );
				}
			}

			$products = new WP_Query( $query_args );

			$max_pages = $products->max_num_pages;


			$content = '';

			if ( $products->have_posts() ) {
				while ( $products->have_posts() ) {

					$products->the_post();
				
					// Define allowed templates
					$allowed_templates = array( 'style1', 'style2', 'style3', 'style4' );
					
					// Validate against allowlist
					if ( ! in_array( $settings['product_template'], $allowed_templates, true ) ) {
						// Fallback to default template if invalid value provided
						$settings['product_template'] = 'style1';
					}
				
					ob_start();
					include ATHEMES_AFE_DIR . 'inc/modules/widgets/woo-product-grid/templates/product-template-' . $settings['product_template'] . '.php';
					$content .= ob_get_clean();

				}

				$content .= '<span class="maxpages" data-maxpages="' . esc_attr( $max_pages ) . '"></span>';
			}
			
			printf( '%s', $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			wp_reset_postdata();

			wp_die();
		}

		public function posts_filter_autocomplete() {

			check_ajax_referer( 'aafe-posts-widget-nonce', 'nonce' );

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( array(), 403 );
			}

			$post_type   = 'post';
			$source_name = 'post_type';
	
			if ( ! empty( $_POST['post_type'] ) ) {
				$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );
			}
	
			if ( ! empty( $_POST['source_name'] ) ) {
				$source_name = sanitize_text_field( wp_unslash( $_POST['source_name'] ) );
			}
	
			$search  = ! empty( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
			$results = $post_list = [];
			switch ( $source_name ) {
				case 'taxonomy':
					$args = [
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
						'search'     => $search,
						'number'     => '5',
					];
	
					if ( 'all' === $post_type ) {
						$args['taxonomy'] = aThemes_Addons_Helper::get_queryable_taxonomies();
					} elseif ( taxonomy_exists( $post_type ) && is_taxonomy_viewable( $post_type ) ) {
						$args['taxonomy'] = $post_type;
					} else {
						wp_send_json( [ 'results' => [] ] );
					}
	
					$post_list = wp_list_pluck( get_terms( $args ), 'name', 'term_id' );
					break;
				case 'user':
					if ( ! current_user_can( 'list_users' ) ) {
						$post_list = [];
						break;
					}
	
					$users = [];
	
					foreach ( get_users( [ 'search' => "*{$search}*" ] ) as $user ) {
						$user_id           = $user->ID;
						$user_name         = $user->display_name;
						$users[ $user_id ] = $user_name;
					}
	
					$post_list = $users;
					break;
				default:
					if ( ! aThemes_Addons_Helper::is_queryable_post_type( $post_type ) ) {
						wp_send_json( [ 'results' => [] ] );
					}

					$post_info = get_posts( [
						'post_type'      => $post_type,
						's'              => $search,
						'posts_per_page' => 5,
					] );
					$post_list = wp_list_pluck( $post_info, 'post_title', 'ID' );
			}
	
			if ( ! empty( $post_list ) ) {
				foreach ( $post_list as $key => $item ) {
					$results[] = [ 'text' => $item, 'id' => $key ];
				}
			}
	
			wp_send_json( [ 'results' => $results ] );

			wp_die();
		}

		public function get_posts_value_titles() {

			check_ajax_referer( 'aafe-posts-widget-nonce', 'nonce' );

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( array(), 403 );
			}

			if ( empty( $_POST['id'] ) ) {
				wp_send_json_error( [] );
			}
	
			if ( empty( array_filter( wp_unslash( $_POST['id'] ) ) ) ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				wp_send_json_error( [] );
			}
			$ids         = array_map( 'intval', $_POST['id'] );
			$source_name = ! empty( $_POST['source_name'] ) ? sanitize_text_field( wp_unslash( $_POST['source_name'] ) ) : '';
	
			switch ( $source_name ) {
				case 'taxonomy':
					$args = [
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
						'include'    => implode( ',', $ids ),
					];
	
					$taxonomy = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'all';

					if ( 'all' === $taxonomy ) {
						$args['taxonomy'] = aThemes_Addons_Helper::get_queryable_taxonomies();
					} elseif ( taxonomy_exists( $taxonomy ) && is_taxonomy_viewable( $taxonomy ) ) {
						$args['taxonomy'] = $taxonomy;
					} else {
						wp_send_json_error( [] );
					}
	
					$response = wp_list_pluck( get_terms( $args ), 'name', 'term_id' );
					break;
				case 'user':
					if ( ! current_user_can( 'list_users' ) ) {
						$response = [];
						break;
					}

					$users = [];

					foreach ( get_users( [ 'include' => $ids ] ) as $user ) {
						$user_id           = $user->ID;
						$user_name         = $user->display_name;
						$users[ $user_id ] = $user_name;
					}
	
					$response = $users;
					break;
				default:
					$post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'post';

					if ( ! aThemes_Addons_Helper::is_queryable_post_type( $post_type ) ) {
						wp_send_json_error( [] );
					}

					$post_info = get_posts( [
						'post_type' => $post_type,
						'include'   => implode( ',', $ids ),
					] );
					$response  = wp_list_pluck( $post_info, 'post_title', 'ID' );
			}
	
			if ( ! empty( $response ) ) {
				wp_send_json_success( [ 'results' => $response ] );
			} else {
				wp_send_json_error( [] );
			}

			wp_die();
		}       
	}

	new aThemes_Addons_Ajax_Callbacks();

}
