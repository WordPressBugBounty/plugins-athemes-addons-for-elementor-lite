<?php
/**
 * aThemes Patcher end-of-life notice.
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Asks admins to remove the retired aThemes Patcher plugin while it is installed,
 * and removes it for them in one click.
 *
 * Shown on the Plugins screen and on aThemes Addons screens. A dismissal lasts
 * until the next login.
 *
 * Other aThemes products can check this class name to avoid a second notice.
 */
class aThemes_Addons_Patcher_EOL_Notice {

	const PLUGIN_FILE   = 'athemes-patcher/athemes-patcher.php';
	const SESSION_META  = 'athemes_addons_patcher_eol_dismissed_session';
	const DISMISS_ARG   = 'athemes_addons_patcher_eol_dismiss';
	const DISMISS_NONCE = 'athemes_addons_patcher_eol_nonce';
	const RESULT_ARG    = 'athemes_addons_patcher_removed';
	const REMOVE_ACTION = 'athemes_addons_remove_patcher';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_dismiss' ), 0 );
		add_action( 'admin_post_' . self::REMOVE_ACTION, array( $this, 'remove_patcher' ) );
		add_action( 'admin_notices', array( $this, 'notice_markup' ) );
		add_action( 'network_admin_notices', array( $this, 'notice_markup' ) );
		add_action( 'admin_notices', array( $this, 'result_notice' ) );
		add_action( 'network_admin_notices', array( $this, 'result_notice' ) );
	}

	/**
	 * Show the notice.
	 */
	public function notice_markup() {
		if ( ! $this->should_show() || $this->sibling_owns_notice() ) {
			return;
		}
		?>
		<div class="notice notice-warning is-dismissible athemes-addons-patcher-eol">
			<p>
				<?php
				printf(
					/* translators: %s: plugin name. */
					esc_html__( 'The %s plugin is no longer needed. You can safely remove it from your site.', 'athemes-addons-for-elementor-lite' ),
					'<strong>aThemes Patcher</strong>'
				);
				?>
				<?php if ( $this->can_remove() ) : ?>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::REMOVE_ACTION ), self::REMOVE_ACTION ) ); ?>"><?php esc_html_e( 'Deactivate & Delete', 'athemes-addons-for-elementor-lite' ); ?></a>
				<?php elseif ( is_multisite() ) : ?>
					<?php esc_html_e( 'Ask your network administrator to remove it.', 'athemes-addons-for-elementor-lite' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Plugin deletion is disabled on this site, so it has to be removed manually.', 'athemes-addons-for-elementor-lite' ); ?>
				<?php endif; ?>
			</p>
			<a class="notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( self::DISMISS_ARG, '1' ), self::DISMISS_ARG, self::DISMISS_NONCE ) ); ?>" style="text-decoration:none;">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'athemes-addons-for-elementor-lite' ); ?></span>
			</a>
		</div>
		<?php
	}

	/**
	 * Show the outcome of a removal on the Plugins screen.
	 */
	public function result_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		if ( ! $this->is_plugins_screen() || ! isset( $_GET[ self::RESULT_ARG ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		if ( '1' === $_GET[ self::RESULT_ARG ] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'The aThemes Patcher plugin has been deactivated and deleted.', 'athemes-addons-for-elementor-lite' ); ?></p>
			</div>
			<?php
			return;
		}
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Could not remove the aThemes Patcher plugin. Delete it from the plugins list below.', 'athemes-addons-for-elementor-lite' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Deactivate and delete the plugin, then return to the Plugins screen.
	 */
	public function remove_patcher() {
		check_admin_referer( self::REMOVE_ACTION );

		if ( ! current_user_can( 'delete_plugins' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to delete plugins for this site.', 'athemes-addons-for-elementor-lite' ), 403 );
		}

		$removed = true;

		if ( $this->is_installed() ) {
			if ( ! function_exists( 'delete_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$network_wide = is_multisite() && is_plugin_active_for_network( self::PLUGIN_FILE );

			if ( $network_wide || is_plugin_active( self::PLUGIN_FILE ) ) {
				deactivate_plugins( self::PLUGIN_FILE, false, $network_wide );
			}

			// delete_plugins() returns null, not an error, when it needs filesystem credentials.
			$removed = true === delete_plugins( array( self::PLUGIN_FILE ) ) && ! $this->is_installed();
		}

		if ( $removed ) {
			delete_metadata( 'user', 0, self::SESSION_META, '', true );
		}

		$plugins_url = is_multisite() && current_user_can( 'manage_network_plugins' ) ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );

		wp_safe_redirect( add_query_arg( self::RESULT_ARG, $removed ? '1' : '0', $plugins_url ) );
		exit;
	}

	/**
	 * Remember the dismissal for the current login session.
	 */
	public function maybe_dismiss() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.
		if ( ! isset( $_GET[ self::DISMISS_ARG ] ) || '1' !== $_GET[ self::DISMISS_ARG ] ) {
			return;
		}

		// Own nonce name, so a core _wpnonce already in the URL is left alone.
		$nonce = isset( $_GET[ self::DISMISS_NONCE ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::DISMISS_NONCE ] ) ) : '';

		// A stale tab fails quietly instead of hitting the expired link screen.
		if ( ! wp_verify_nonce( $nonce, self::DISMISS_ARG ) ) {
			return;
		}

		$session = $this->session_hash();

		if ( '' !== $session ) {
			update_user_meta( get_current_user_id(), self::SESSION_META, $session );
		}

		wp_safe_redirect( remove_query_arg( array( self::DISMISS_ARG, self::DISMISS_NONCE ) ) );
		exit;
	}

	/**
	 * Should the notice render on this screen for this user?
	 *
	 * @return bool
	 */
	private function should_show() {
		return ( $this->is_plugins_screen() || $this->is_addons_screen() )
			&& current_user_can( 'activate_plugins' )
			&& $this->is_installed()
			&& ! $this->is_dismissed();
	}

	/**
	 * Does a sibling aThemes product already show its Patcher notice here?
	 *
	 * Sydney Pro shows its notice on every screen, Merchant on both Plugins
	 * screens, Botiga on the site Plugins screen only.
	 *
	 * @return bool
	 */
	private function sibling_owns_notice() {
		$siblings = array( 'Sydney_Patcher_EOL_Notice' );

		if ( $this->is_plugins_screen() ) {
			$siblings[] = 'Merchant_Patcher_EOL_Notice';

			if ( ! is_network_admin() ) {
				$siblings[] = 'Botiga_Patcher_EOL_Notice';
			}
		}

		foreach ( $siblings as $sibling ) {
			if ( class_exists( $sibling, false ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Can the current user remove the plugin from this screen?
	 *
	 * On multisite the files are shared by every site, so removal is only
	 * offered from the network admin.
	 *
	 * @return bool
	 */
	private function can_remove() {
		return current_user_can( 'delete_plugins' ) && ( ! is_multisite() || is_network_admin() );
	}

	/**
	 * Are the Patcher files still on disk, active or not?
	 *
	 * @return bool
	 */
	private function is_installed() {
		return file_exists( WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE );
	}

	/**
	 * Was the notice dismissed in the current login session?
	 *
	 * @return bool
	 */
	private function is_dismissed() {
		$session = $this->session_hash();

		if ( '' === $session ) {
			return false;
		}

		$dismissed = (string) get_user_meta( get_current_user_id(), self::SESSION_META, true );

		return '' !== $dismissed && hash_equals( $dismissed, $session );
	}

	/**
	 * Hash of the current login session token.
	 *
	 * @return string
	 */
	private function session_hash() {
		$token = wp_get_session_token();

		return '' === $token ? '' : wp_hash( $token );
	}

	/**
	 * Is this the Plugins screen?
	 *
	 * @return bool
	 */
	private function is_plugins_screen() {
		$screen = get_current_screen();

		return $screen && in_array( $screen->base, array( 'plugins', 'plugins-network' ), true );
	}

	/**
	 * Is this an aThemes Addons admin screen?
	 *
	 * @return bool
	 */
	private function is_addons_screen() {
		$screen = get_current_screen();

		return $screen && ( 'toplevel_page_athemes-addons' === $screen->id || 0 === strpos( $screen->id, 'athemes-addons_page_' ) );
	}
}

new aThemes_Addons_Patcher_EOL_Notice();
