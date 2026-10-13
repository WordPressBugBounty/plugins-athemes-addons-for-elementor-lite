<?php
/**
 * Admin_Menu Class.
 */
namespace aThemesAddons;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Admin_Menu' ) ) {

	class Admin_Menu {

		/**
		 * Page title.
		 */
		public $page_title = 'aThemes Addons';

		/**
		 * Plugin slug.
		 */
		public $plugin_slug = 'athemes-addons';

		/**
		 * Plugin capability.
		 */
		public $capability = 'manage_options';

		/**
		 * Plugin priority.
		 */
		public $priority = 58;

		/**
		 * Plugin notifications.
		 */
		public $notifications = array();

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
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'wp_ajax_athemes_addons_notifications_read', array( $this, 'ajax_notifications_read' ) );

			add_action( 'admin_head', array( $this, 'admin_menu_icon_style' ) );
			add_filter( 'submenu_file', array( $this, 'highlight_current_submenu' ), 10, 2 );
			add_action('admin_footer', array( $this, 'footer_internal_scripts' ));
		}

		/**
		 * Include required classes.
		 */
		public function add_admin_menu() {

			global $submenu;

			// Dashboard
			add_menu_page(
				$this->page_title,
				$this->page_title,
				$this->capability,
				$this->plugin_slug,
				array( $this, 'page_dashboard' ),
				'none',
				$this->priority
			);

			add_submenu_page(
				$this->plugin_slug,
				esc_html__( 'Widgets', 'athemes-addons-for-elementor-lite' ),
				esc_html__( 'Widgets', 'athemes-addons-for-elementor-lite' ),
				'manage_options',
				$this->plugin_slug,
				'',
				1
			);

			add_submenu_page(
				$this->plugin_slug,
				esc_html__( 'Extensions', 'athemes-addons-for-elementor-lite' ),
				esc_html__( 'Extensions', 'athemes-addons-for-elementor-lite' ),
				'manage_options',
				'admin.php?page=' . $this->plugin_slug . '&section=extensions',
				'',
				2
			);

			add_submenu_page(
				$this->plugin_slug,
				esc_html__( 'Settings', 'athemes-addons-for-elementor-lite' ),
				esc_html__( 'Settings', 'athemes-addons-for-elementor-lite' ),
				'manage_options',
				'admin.php?page=' . $this->plugin_slug . '&section=settings',
				'',
				3
			);

			if ( ! defined( 'ATHEMES_AFE_PRO_VERSION' ) ) {
				add_submenu_page(
					$this->plugin_slug,
					esc_html__('Upgrade to Pro', 'athemes-addons-for-elementor-lite'),
					esc_html__('Upgrade to Pro', 'athemes-addons-for-elementor-lite'), 
					'manage_options',
					athemes_addons_admin_upgrade_link( 'https://athemes.com/addons', array( 'utm_source' => 'theme_submenu_page', 'utm_medium' => 'button', 'utm_campaign' => 'Addons' ), 'dashboard-submenu-upgrade-link' ),
					'',
					4
				);
			}
		}

		/**
		 * Get Notifications
		 */
		public function get_notifications() {
			$notifications = get_transient( 'athemes_addons_notifications' );

			if ( ! empty( $notifications ) ) {
				$this->notifications = $notifications;
			} else {

				/**
				 * Hook: athemes_addons_changelog_api_url
				 * 
				 * @since 1.0
				 */

				$response = wp_remote_get( apply_filters( 'athemes_addons_changelog_api_url', 'https://athemes.com/wp-json/wp/v2/notifications?theme=7105&per_page=3' ) );

				if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
					$this->notifications = json_decode( wp_remote_retrieve_body( $response ) );
					set_transient( 'athemes_addons_notifications', $this->notifications, 24 * HOUR_IN_SECONDS );
				}
			}

			return $this->notifications;
		}

		/**
		 * Check if the latest notification is read
		 */
		public function is_latest_notification_read() {

			if ( ! isset( $this->notifications ) || empty( $this->notifications ) ) {
				return false;
			}
			
			$user_id        = get_current_user_id();
			$user_read_meta = get_user_meta( $user_id, 'athemes_addons_dashboard_notifications_latest_read', true );

			$last_notification_date      = strtotime( is_string( $this->notifications[0]->post_date ) ? $this->notifications[0]->post_date : '' );
			$last_notification_date_ondb = $user_read_meta ? strtotime( $user_read_meta ) : false;

			if ( ! $last_notification_date_ondb ) {
				return false;
			}

			if ( $last_notification_date > $last_notification_date_ondb ) {
				return false;
			}

			return true;
		}

		/**
		 * Ajax notifications.
		 */
		public function ajax_notifications_read() {
			check_ajax_referer( 'athemes-addons-elementor', 'nonce' );

			// Check current user capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'You are not allowed to do this.' );
			}

			$latest_notification_date = ( isset( $_POST[ 'latest_notification_date' ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ 'latest_notification_date' ] ) ) : false;

			update_user_meta( get_current_user_id(), 'athemes_addons_dashboard_notifications_latest_read', $latest_notification_date );

			wp_send_json_success();
		}

		public function page_dashboard() {
			require_once ATHEMES_AFE_DIR . 'admin/pages/page-dashboard.php';
		}

		/**
		 * Dashboard tabs
		 */
		public function dashboard_tabs() {
			$tabs = array(
				'widgets' => array(
					'title' => __( 'Widgets', 'athemes-addons-for-elementor-lite' ),
					'link'  => 'admin.php?page=athemes-addons&section=widgets',
				),
				'theme-builder' => array(
					'title' => __( 'Theme Builder', 'athemes-addons-for-elementor-lite' ),
					'link'  => 'admin.php?page=athemes-addons&section=theme-builder',
				),
				'extensions' => array(
					'title' => __( 'Extensions', 'athemes-addons-for-elementor-lite' ),
					'link'  => 'admin.php?page=athemes-addons&section=extensions',
				),
				
				'settings' => array(
					'title' => __( 'Settings', 'athemes-addons-for-elementor-lite' ),
					'link'  => 'admin.php?page=athemes-addons&section=settings',
				),
				
			);

			if ( \athemes_addons_host_theme_has_template_builder() ) {
				unset( $tabs['theme-builder'] );
			}

			if ( ! defined( 'ATHEMES_AFE_PRO_VERSION' ) ) {
				$tabs['upgrade'] = array(
					'title' => __( 'Free vs Pro', 'athemes-addons-for-elementor-lite' ),
					'link'  => 'admin.php?page=athemes-addons&section=upgrade',
				);
			}
	
			return $tabs;
		}

		/**
		 * Paint the top-level menu icon.
		 *
		 * The icon is registered as 'none' so core emits no <img>, which it dims
		 * (opacity .6) and pins low (padding-top 9px). The logo is a CSS mask over
		 * currentColor so it follows the admin color scheme like core dashicons.
		 * Percent-encoded, not base64, to pass wordpress.org checks. Keep in sync
		 * with assets/images/athemes-addons-logo.svg.
		 *
		 * @return void
		 */
		public function admin_menu_icon_style() {
			$icon = 'data:image/svg+xml,%3Csvg%20width=%2718%27%20height=%2713%27%20viewBox=%270%200%2018%2013%27%20fill=%27none%27%20xmlns=%27http://www.w3.org/2000/svg%27%3E%3Cpath%20d=%27M4.3371%205.33672L0.0488281%2013.0046H2.47652H5.38975L6.69196%209.80144L4.3371%205.33672ZM7.19206%200.00463867L4.85371%204.44024L9.37116%2013.0046H14.0488L7.19206%200.00463867Z%27%20fill=%27%23F0F6FC%27/%3E%3Cpath%20d=%27M17.8002%200.00463867H8.125L10.1119%203.78976H17.8002V0.00463867Z%27%20fill=%27%23F0F6FC%27/%3E%3Cpath%20d=%27M17.8002%209.20229H12.9784L14.9654%2013.0046H17.8002V9.20229Z%27%20fill=%27%23F0F6FC%27/%3E%3Cpath%20d=%27M17.8002%204.60335H10.5608L12.5477%208.3887H17.8002V4.60335Z%27%20fill=%27%23F0F6FC%27/%3E%3C/svg%3E';
			?>
			<style id="athemes-addons-admin-menu-icon">
				#adminmenu .toplevel_page_<?php echo esc_attr( $this->plugin_slug ); ?> div.wp-menu-image::before {
					content: "";
					width: 18px;
					background-color: currentColor;
					-webkit-mask-image: url("<?php echo esc_attr( $icon ); ?>");
					mask-image: url("<?php echo esc_attr( $icon ); ?>");
					-webkit-mask-repeat: no-repeat;
					mask-repeat: no-repeat;
					-webkit-mask-position: center;
					mask-position: center;
					-webkit-mask-size: contain;
					mask-size: contain;
				}
				@media (forced-colors: active) {
					#adminmenu .toplevel_page_<?php echo esc_attr( $this->plugin_slug ); ?> div.wp-menu-image::before {
						background-color: CanvasText;
					}
				}
			</style>
			<?php
		}

		/**
		 * Mark the submenu item for the current dashboard section as current.
		 *
		 * All sections share one page slug, so core would always mark "Widgets".
		 * Core then styles the item with the active admin color scheme.
		 *
		 * @param string|null $submenu_file The submenu file.
		 * @param string      $parent_file  The parent file.
		 * @return string|null
		 */
		public function highlight_current_submenu( $submenu_file, $parent_file ) {
			global $submenu;

			if ( $this->plugin_slug !== $parent_file ) {
				return $submenu_file;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';

			if ( '' === $section || 'widgets' === $section ) {
				return $submenu_file;
			}

			$slug  = 'admin.php?page=' . $this->plugin_slug . '&section=' . $section;
			$slugs = isset( $submenu[ $parent_file ] ) ? wp_list_pluck( $submenu[ $parent_file ], 2 ) : array();

			// Sections without a submenu item (e.g. Theme Builder) highlight nothing.
			return in_array( $slug, $slugs, true ) ? $slug : '';
		}

				/**
		 * Footer internal scripts
		 *
		 * @return void
		 */
		public function footer_internal_scripts() {
			// Generate upgrade link URL
			$upgrade_url = athemes_addons_admin_upgrade_link( 'https://athemes.com/addons', array( 'utm_source' => 'theme_submenu_page', 'utm_medium' => 'button', 'utm_campaign' => 'Addons' ), 'dashboard-submenu-upgrade-link' );
			?>
			<style>
				#adminmenu .toplevel_page_athemes-addons .wp-submenu a[href="<?php echo esc_url( $upgrade_url ); ?>"] {
					color: #05d105;
				}
			</style>
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function () {

					if (typeof URLSearchParams !== 'undefined') {
						const params = new URLSearchParams(window.location.search);
						const section = params.get('section');
						if (section) {
							const tabNavItem = document.querySelector(`.athemes-addons-tab-nav-item[data-tab="${section}"]`);
							if (tabNavItem) {
								const siblings = tabNavItem.parentElement.children;
								for (let sibling of siblings) {
									sibling.classList.remove('active');
								}
								tabNavItem.classList.add('active');
							}
						}
					}

                    const AddonsUpsellMenuItem = document.querySelector('#adminmenu .toplevel_page_athemes-addons .wp-submenu a[href="<?php echo esc_js( $upgrade_url ); ?>"]');

                    if (AddonsUpsellMenuItem) {
                        AddonsUpsellMenuItem.addEventListener('click', function (e) {
                            e.preventDefault();

                            const href = this.getAttribute('href');
                            window.open(href, '_blank');
                        });
                    }
                });
            </script>
			<?php
		}
	}

	Admin_Menu::instance();

}
