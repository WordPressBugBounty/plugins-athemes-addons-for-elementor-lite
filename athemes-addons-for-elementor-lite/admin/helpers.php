<?php
/**
 * Helper functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Output the switcher button for module activation.
 */
if ( ! function_exists( 'athemes_addons_module_activation_switcher' ) ) {
	function athemes_addons_module_activation_switcher( $module_id, $value, $is_upsell = false ) {
		?>
			<div class="athemes-addons-toggle-switch athemes-addons-module-page-button-action-<?php echo ( $value ) ? 'deactivate' : 'activate'; ?> " data-module="<?php echo esc_attr( $module_id ); ?>">
				<span class="saved-label"><?php echo esc_html__( 'Saved!', 'athemes-addons-for-elementor-lite' ); ?></span>
				<input type="checkbox" id="<?php echo esc_attr( $module_id ); ?>" name="aafe[<?php echo esc_attr( $module_id ); ?>]" value="1" <?php checked( $value, 1, true ); ?>
						class="toggle-switch-checkbox"/>
				<label class="toggle-switch-label" for="<?php echo esc_attr( $module_id ); ?>">
					<span class="toggle-switch-inner"></span>
					<span class="toggle-switch-switch"></span>
				</label>
				<?php if ( ! empty( $settings['label'] ) ) : ?>
					<span><?php echo esc_html( $settings['label'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php
	}
}

/**
 * Output the help icon on the modules list.
 */
if ( ! function_exists( 'athemes_addons_module_help_icon' ) ) {
	function athemes_addons_module_help_icon( $tutorial_url = false ) {
		?>
		<div class="athemes-addons-module-help-link">
			<a href="<?php echo esc_url( $tutorial_url ); ?>" target="_blank">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M14.1663 4.16667H5.83301C5.61199 4.16667 5.40003 4.25446 5.24375 4.41074C5.08747 4.56702 4.99967 4.77899 4.99967 5V15C4.99967 15.221 5.08747 15.433 5.24375 15.5893C5.40003 15.7455 5.61199 15.8333 5.83301 15.8333H14.1663C14.3874 15.8333 14.5993 15.7455 14.7556 15.5893C14.9119 15.433 14.9997 15.221 14.9997 15V5C14.9997 4.77899 14.9119 4.56702 14.7556 4.41074C14.5993 4.25446 14.3874 4.16667 14.1663 4.16667ZM5.83301 2.5C5.16997 2.5 4.53408 2.76339 4.06524 3.23223C3.5964 3.70107 3.33301 4.33696 3.33301 5V15C3.33301 15.663 3.5964 16.2989 4.06524 16.7678C4.53408 17.2366 5.16997 17.5 5.83301 17.5H14.1663C14.8294 17.5 15.4653 17.2366 15.9341 16.7678C16.4029 16.2989 16.6663 15.663 16.6663 15V5C16.6663 4.33696 16.4029 3.70107 15.9341 3.23223C15.4653 2.76339 14.8294 2.5 14.1663 2.5H5.83301Z" fill="#5C5F62"/>
					<path d="M6.66602 5.83325H13.3327V7.49992H6.66602V5.83325ZM6.66602 9.16659H13.3327V10.8333H6.66602V9.16659ZM6.66602 12.4999H10.8327V14.1666H6.66602V12.4999Z" fill="#5C5F62"/>
				</svg>
			</a>
			<span class="athemes-addons-module-tooltip"><?php esc_html_e( 'Help', 'athemes-addons-for-elementor-lite' ); ?></span>
		</div>
		<?php
	}
}

/**
 * Output the preview icon on the modules list.
 */
if ( ! function_exists( 'athemes_addons_module_preview_icon' ) ) {
	function athemes_addons_module_preview_icon( $preview_url = false ) {
		?>
		<div class="athemes-addons-module-help-link">
			<a href="<?php echo esc_url( $preview_url ); ?>" target="_blank">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M1.66699 1.66675H18.3337V3.33341H17.5003V15.0001H12.012L15.3453 18.3334L14.167 19.5117L10.0003 15.3451L5.83366 19.5117L4.65533 18.3334L7.98866 15.0001H2.50033V3.33341H1.66699V1.66675ZM4.16699 3.33341V13.3334H15.8337V3.33341H4.16699ZM8.33366 5.41675L12.2228 8.33341L8.33366 11.2501V5.41675Z" fill="#5C5F62"/>
				</svg>
			</a>
			<span class="athemes-addons-module-tooltip"><?php esc_html_e( 'Preview', 'athemes-addons-for-elementor-lite' ); ?></span>
		</div>
		<?php
	}
}

/**
 * Output a module card on the widgets and extensions tabs.
 *
 * @param string $module_id   The module ID.
 * @param array  $module      The module data.
 * @param string $module_type 'widget' or 'extension'.
 */
if ( ! function_exists( 'athemes_addons_module_card' ) ) {
	function athemes_addons_module_card( $module_id, $module, $module_type = 'widget' ) {
		$is_pro_module = ! empty( $module['pro'] );
		$is_locked     = $is_pro_module && ! defined( 'ATHEMES_AFE_PRO_VERSION' );

		/**
		 * Hook 'athemes_addons_admin_module_{module_id}_list_item_class'
		 *
		 * @since 1.0
		 */
		$item_class = apply_filters( "athemes_addons_admin_module_{$module_id}_list_item_class", 'athemes-addons-modules-list-item' );

		if ( $is_locked ) {
			$item_class .= ' is-locked';
		}

		$is_active = aThemes_Addons_Modules::is_module_active( $module_id );
		?>
		<div class="<?php echo esc_attr( $item_class ); ?>"
			data-title="<?php echo esc_attr( $module['title'] ); ?>"
			data-type="<?php echo $is_pro_module ? 'pro' : 'free'; ?>"
			data-category="<?php echo esc_attr( isset( $module['category'] ) ? $module['category'] : '' ); ?>"
			data-status="<?php echo $is_active ? 'active' : 'inactive'; ?>"
			data-keywords="<?php echo esc_attr( isset( $module['keywords'] ) ? implode( ',', $module['keywords'] ) : '' ); ?>"
		>
			<div class="athemes-addons-modules-list-item-title">
				<?php echo esc_html( $module['title'] ); ?>
			</div>

			<div class="athemes-addons-module-actions">
				<?php
				if ( ! empty( $module['tutorial_url'] ) ) {
					athemes_addons_module_help_icon( $module['tutorial_url'] );
				}

				if ( ! empty( $module['preview_url'] ) ) {
					athemes_addons_module_preview_icon( $module['preview_url'] );
				}

				if ( ! $is_locked ) {
					athemes_addons_module_activation_switcher( $module_id, $is_active ? 1 : 0 );
				}
				?>

				<?php if ( $is_locked ) : ?>
					<?php
					$popup_id = 'athemes-addons-pro-popup-' . $module_type . '-' . $module_id;

					/* translators: %s: widget or extension name */
					$popup_title = 'extension' === $module_type ? __( '%s is a Pro extension', 'athemes-addons-for-elementor-lite' ) : __( '%s is a Pro widget', 'athemes-addons-for-elementor-lite' );
					$popup_title = sprintf( $popup_title, $module['title'] );
					?>
					<button type="button" class="athemes-addons-pro-lock" aria-expanded="false" aria-controls="<?php echo esc_attr( $popup_id ); ?>" aria-label="<?php echo esc_attr( $popup_title ); ?>">
						<span class="athemes-addons-pro-pill">
							<svg width="10" height="10" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M5 5a3 3 0 0 1 6 0v2h.5A1.5 1.5 0 0 1 13 8.5v5a1.5 1.5 0 0 1-1.5 1.5h-7A1.5 1.5 0 0 1 3 13.5v-5A1.5 1.5 0 0 1 4.5 7H5V5Zm4.5 2V5a1.5 1.5 0 0 0-3 0v2h3Z" fill="currentColor"/>
							</svg>
							<?php esc_html_e( 'PRO', 'athemes-addons-for-elementor-lite' ); ?>
						</span>
					</button>
				<?php endif; ?>
			</div>

			<?php if ( $is_locked ) : ?>
				<?php
				$utm_args = array(
					'utm_source'   => 'dashboard_pro_' . $module_type,
					'utm_medium'   => 'button',
					'utm_campaign' => 'Addons',
					'utm_content'  => $module_id,
				);
				?>
				<div class="athemes-addons-pro-popup" id="<?php echo esc_attr( $popup_id ); ?>" role="dialog" aria-labelledby="<?php echo esc_attr( $popup_id ); ?>-title" hidden>
					<p class="athemes-addons-pro-popup-title" id="<?php echo esc_attr( $popup_id ); ?>-title"><?php echo esc_html( $popup_title ); ?></p>
					<p class="athemes-addons-pro-popup-text">
						<?php
						if ( 'extension' === $module_type ) {
							esc_html_e( 'Upgrade to Pro to enable this extension on your site.', 'athemes-addons-for-elementor-lite' );
						} else {
							esc_html_e( 'Upgrade to Pro to enable this widget on your site.', 'athemes-addons-for-elementor-lite' );
						}
						?>
					</p>
					<div class="athemes-addons-pro-popup-actions">
						<?php if ( ! empty( $module['preview_url'] ) ) : ?>
							<a href="<?php echo esc_url( athemes_addons_admin_upgrade_link( $module['preview_url'], $utm_args, 'dashboard-pro-' . $module_type . '-demo' ) ); ?>" class="athemes-addons-pro-popup-demo" target="_blank"><?php esc_html_e( 'View demo', 'athemes-addons-for-elementor-lite' ); ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url( athemes_addons_admin_upgrade_link( 'https://athemes.com/addons', $utm_args, 'dashboard-pro-' . $module_type . '-popup' ) ); ?>" class="athemes-addons-pro-popup-upgrade" target="_blank"><?php esc_html_e( 'Upgrade to Pro', 'athemes-addons-for-elementor-lite' ); ?></a>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

/**
 * Get all widget categories from the modules list.
 */
if ( ! function_exists( 'athemes_addons_get_widget_categories' ) ) {
	function athemes_addons_get_widget_categories() {
		return array(
			'posts'                  => esc_html__( 'Posts', 'athemes-addons-for-elementor-lite' ),
			'content'                => esc_html__( 'Content', 'athemes-addons-for-elementor-lite' ),
			'media'                  => esc_html__( 'Media', 'athemes-addons-for-elementor-lite' ),
			'business-commerce'      => esc_html__( 'Business &amp; Commerce', 'athemes-addons-for-elementor-lite' ),
			'social-communications'  => esc_html__( 'Social &amp; Communications', 'athemes-addons-for-elementor-lite' ),
			'forms'                  => esc_html__( 'Forms', 'athemes-addons-for-elementor-lite' ),
			'utilities'              => esc_html__( 'Utilities', 'athemes-addons-for-elementor-lite' ),
		);
	}
}

/**
 * Get theme builder elements.
 */
if ( ! function_exists( 'athemes_addons_get_theme_builder_elements' ) ) {
	function athemes_addons_get_theme_builder_elements() {
		$tb_elements = array(
			'header' => array(
				'title' => esc_html__( 'Header', 'athemes-addons-for-elementor-lite' ),
			),
			'footer' => array(
				'title' => esc_html__( 'Footer', 'athemes-addons-for-elementor-lite' ),
			),
			'singular' => array(
				'title' => esc_html__( 'Singular', 'athemes-addons-for-elementor-lite' ),
			),
			'archive' => array(
				'title' => esc_html__( 'Archive', 'athemes-addons-for-elementor-lite' ),
			),
			'archive-item' => array(
				'title' => esc_html__( 'Archive Item', 'athemes-addons-for-elementor-lite' ),
			),
			'error404' => array(
				'title' => esc_html__( '404', 'athemes-addons-for-elementor-lite' ),
			),
		);
		
		$woo_elements = array(
			'shop' => array(
				'title' => esc_html__( 'Product Archive', 'athemes-addons-for-elementor-lite' ),
			),
			'product' => array(
				'title' => esc_html__( 'Product', 'athemes-addons-for-elementor-lite' ),
			),
		);
		
		if ( !defined( 'ATHEMES_AFE_PRO_VERSION' ) || defined( 'ATHEMES_AFE_PRO_VERSION') && class_exists( 'Woocommerce' ) ) {
			$tb_elements = array_merge( $tb_elements, $woo_elements );
		}

		return apply_filters( 'athemes_addons_theme_builder_elements', $tb_elements );
	}
}

/**
 * Get all post types.
 */
if ( ! function_exists( 'athemes_addons_get_post_types' ) ) {
	function athemes_addons_get_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$exclude_post_types = array( 'elementor_library', 'attachment' );

		$filtered_post_types = array();

		$filtered_post_types['all'] = esc_html__( 'All', 'athemes-addons-for-elementor-lite' );

		foreach ( $post_types as $post_type ) {
			if ( ! in_array( $post_type->name, $exclude_post_types, true ) ) {
				$filtered_post_types[$post_type->name] = $post_type->labels->name;
			}
		}

		return $filtered_post_types;
	}
}

/**
 * Inline brand mark for the AI clients named on the Settings page.
 *
 * Each logo keeps its own viewBox and colour. Source: wpforms-lite ai-mcp set, via Sydney.
 */
if ( ! function_exists( 'athemes_addons_brand_icon' ) ) {
	function athemes_addons_brand_icon( $name, $size = 12 ) {
		$icons = array(
			'claude'  => array(
				'viewbox' => '0 0 16 16',
				'path'    => 'M3.136 10.64L6.288 8.88L6.336 8.72L6.288 8.64H6.128L5.6 8.608L3.808 8.56L2.24 8.48L0.72 8.4L0.336 8.32L0 7.84L0.032 7.6L0.352 7.392L0.816 7.424L1.824 7.504L3.344 7.6L4.448 7.664L6.08 7.856H6.336L6.368 7.744L6.288 7.68L6.224 7.616L4.64 6.56L2.944 5.44L2.048 4.784L1.568 4.464L1.328 4.144L1.232 3.472L1.664 2.992L2.256 3.04L2.4 3.072L2.992 3.536L4.272 4.512L5.92 5.76L6.16 5.952L6.256 5.888L6.272 5.84L6.16 5.664L5.28 4L4.32 2.336L3.888 1.648L3.776 1.232C3.728 1.072 3.712 0.912 3.712 0.752L4.192 0.08L4.48 0L5.152 0.096L5.408 0.32L5.824 1.28L6.48 2.768L7.52 4.784L7.84 5.392L8 5.936L8.048 6.096H8.16V6.016L8.24 4.864L8.4 3.472L8.56 1.68L8.608 1.168L8.864 0.56L9.344 0.24L9.76 0.416L10.08 0.88L10.032 1.168L9.856 2.4L9.44 4.336L9.2 5.648H9.344L9.504 5.472L10.16 4.608L11.264 3.232L11.744 2.672L12.32 2.08L12.688 1.792H13.376L13.872 2.544L13.648 3.328L12.944 4.224L12.352 4.976L11.504 6.112L10.992 7.024L11.04 7.088H11.152L13.072 6.672L14.096 6.496L15.312 6.288L15.872 6.544L15.936 6.8L15.712 7.344L14.4 7.664L12.864 7.984L10.576 8.512L10.544 8.528L10.576 8.576L11.6 8.672L12.048 8.704H13.136L15.152 8.864L15.68 9.184L15.984 9.616L15.936 9.936L15.12 10.352L14.032 10.096L11.472 9.488L10.608 9.28H10.48V9.344L11.216 10.064L12.544 11.264L14.24 12.816L14.32 13.2L14.112 13.52L13.888 13.488L12.416 12.368L11.84 11.888L10.56 10.8H10.48V10.912L10.768 11.344L12.336 13.696L12.416 14.416L12.304 14.64L11.888 14.8L11.456 14.704L10.528 13.424L9.568 11.984L8.816 10.672L8.736 10.736L8.272 15.568L8.064 15.808L7.584 16L7.184 15.68L6.96 15.2L7.184 14.208L7.44 12.928L7.648 11.904L7.84 10.64L7.952 10.224V10.192H7.84L6.88 11.52L5.44 13.488L4.288 14.704L4.016 14.816L3.536 14.576L3.584 14.128L3.84 13.76L5.44 11.712L6.4 10.448L7.04 9.712L7.024 9.632H6.976L2.752 12.384L2 12.48L1.68 12.16L1.712 11.68L1.872 11.52L3.152 10.64H3.136Z',
				'fill'    => '#D97757',
			),
			'chatgpt' => array(
				'viewbox' => '4 4 16 16',
				'path'    => 'M18.8528 10.5483C18.9879 10.1422 19.0569 9.71708 19.0569 9.28915C19.0569 8.58104 18.8683 7.88571 18.5105 7.27465C17.7915 6.02317 16.4578 5.25053 15.0144 5.25053C14.7304 5.25053 14.446 5.28053 14.1684 5.34012C13.7944 4.91875 13.3353 4.58145 12.8215 4.35045C12.3076 4.11944 11.7506 4 11.1872 3.99998H11.1619C11.1593 4.00007 11.1554 4.00007 11.1524 4.00007C9.40419 4.00007 7.85386 5.12813 7.31639 6.7912C6.19096 7.02129 5.21942 7.72709 4.65139 8.72489C4.2947 9.33966 4.10673 10.0377 4.1065 10.7485C4.10665 11.7473 4.47744 12.7106 5.14709 13.4517C5.01191 13.8578 4.94297 14.2829 4.94291 14.7109C4.94297 15.419 5.13158 16.1143 5.48934 16.7254C6.20822 17.9772 7.54204 18.7494 8.98561 18.7494C9.26983 18.7494 9.55325 18.7194 9.83116 18.6598C10.2052 19.0812 10.6643 19.4185 11.1782 19.6495C11.6921 19.8805 12.2492 19.9999 12.8126 20H12.8379L12.8482 19.9999C14.5974 19.9999 16.1472 18.8719 16.6847 17.2073C17.8101 16.9771 18.7816 16.2713 19.3497 15.2735C19.706 14.6593 19.8936 13.9618 19.8935 13.2517C19.8934 12.2529 19.5226 11.2896 18.853 10.5485L18.8528 10.5483ZM12.8389 18.954H12.8348C12.1349 18.9537 11.4572 18.7082 10.9195 18.2601C10.9515 18.2429 10.983 18.225 11.0142 18.2064L14.1999 16.3662C14.2794 16.321 14.3455 16.2555 14.3915 16.1764C14.4375 16.0974 14.4618 16.0075 14.4618 15.916V11.4217L15.8083 12.1992C15.8227 12.2063 15.8325 12.2202 15.8345 12.2361V15.9556C15.8327 17.6092 14.4927 18.9507 12.8389 18.954ZM6.39672 16.2026C6.13358 15.7475 5.99495 15.2312 5.99478 14.7056C5.99478 14.5342 6.00976 14.3623 6.03892 14.1934C6.06259 14.2076 6.10392 14.2328 6.13356 14.2499L9.31934 16.09C9.39879 16.1363 9.4891 16.1607 9.58106 16.1607C9.67301 16.1607 9.76332 16.1363 9.84275 16.09L13.7322 13.8441V15.3992C13.7327 15.4071 13.7311 15.415 13.7278 15.4222C13.7244 15.4294 13.7193 15.4357 13.713 15.4405L10.4925 17.2999C10.0368 17.5621 9.52027 17.7002 8.99448 17.7004C8.46817 17.7003 7.95113 17.5619 7.49519 17.299C7.03926 17.036 6.66045 16.6579 6.39676 16.2024L6.39672 16.2026ZM5.55864 9.24778C5.90852 8.64001 6.46099 8.17465 7.11938 7.93315C7.11938 7.9606 7.1178 8.00917 7.1178 8.04294V11.7233L7.11775 11.7264C7.11777 11.8177 7.14197 11.9075 7.1879 11.9865C7.23383 12.0655 7.29984 12.1309 7.37924 12.1762L11.2688 14.4217L9.9222 15.1992C9.91556 15.2036 9.90793 15.2062 9.90001 15.2069C9.89208 15.2076 9.88411 15.2064 9.87679 15.2033L6.65601 13.3423C6.20069 13.0785 5.82268 12.6997 5.55988 12.2438C5.29708 11.788 5.15871 11.271 5.15864 10.7448C5.15885 10.2195 5.29683 9.70331 5.55881 9.24791L5.55864 9.24778ZM16.6218 11.8223L12.7323 9.57645L14.0789 8.79925C14.0855 8.79486 14.0931 8.79219 14.101 8.79147C14.109 8.79075 14.117 8.792 14.1243 8.79512L17.3451 10.6545C18.2721 11.1901 18.844 12.1805 18.844 13.2515C18.844 14.5077 18.0602 15.6318 16.8816 16.0656V12.2752C16.8817 12.2738 16.8817 12.2723 16.8817 12.271C16.8817 12.0857 16.7824 11.9144 16.6218 11.8223ZM17.9621 9.80513C17.9384 9.7906 17.8971 9.7657 17.8674 9.74866L14.6816 7.90847C14.6022 7.86217 14.5119 7.83776 14.42 7.83771C14.3281 7.83771 14.2377 7.86222 14.1584 7.90847L10.2688 10.1543V8.59925L10.2688 8.59657C10.2688 8.58907 10.2705 8.58167 10.2739 8.57496C10.2773 8.56825 10.2821 8.56242 10.2881 8.55792L13.5086 6.70007C13.9641 6.43745 14.4807 6.29919 15.0065 6.29915C16.6624 6.29915 18.0053 7.64202 18.0053 9.29789C18.0052 9.46781 17.9907 9.63742 17.9621 9.80491V9.80513ZM9.53678 12.5767L8.18991 11.7992C8.18285 11.7957 8.17676 11.7905 8.17219 11.7841C8.16763 11.7776 8.16472 11.7701 8.16374 11.7623V8.04285C8.16444 6.38782 9.50731 5.0461 11.1625 5.0461C11.8635 5.04625 12.5423 5.29181 13.0812 5.74017C13.0493 5.75744 13.0177 5.77533 12.9865 5.79384L9.80077 7.63398C9.72128 7.67921 9.65519 7.74468 9.60921 7.82373C9.56323 7.90278 9.53899 7.9926 9.53897 8.08405V8.08703L9.53678 12.5767ZM10.2682 10.9996L12.0006 9.99912L13.7328 10.999V12.9994L12.0006 13.9993L10.2682 12.9994V10.9996Z',
				'fill'    => '#000000',
			),
			'cursor'  => array(
				'viewbox' => '0 0 14.0346 16',
				'path'    => 'M13.755 3.787L7.34977 0.0889769C7.14409 -0.0298 6.8903 -0.0298 6.68462 0.0889769L0.279692 3.787C0.106789 3.88683 4.03303e-05 4.07146 4.03303e-05 4.27143V11.7285C4.03303e-05 11.9285 0.106789 12.1131 0.279692 12.2129L6.68492 15.911C6.8906 16.0297 7.1444 16.0297 7.35007 15.911L13.7553 12.2129C13.9282 12.1131 14.035 11.9285 14.035 11.7285V4.27143C14.035 4.07146 13.9282 3.88683 13.7553 3.787H13.755ZM13.3527 4.57032L7.16935 15.2801C7.12756 15.3523 7.0172 15.3228 7.0172 15.2392V8.22655C7.0172 8.08642 6.94232 7.95682 6.82084 7.88645L0.747884 4.38028C0.675715 4.33848 0.705184 4.22813 0.788779 4.22813H13.1554C13.331 4.22813 13.4408 4.41847 13.353 4.57062H13.3527V4.57032Z',
				'fill'    => '#26251E',
			),
		);

		if ( ! isset( $icons[ $name ] ) ) {
			return '';
		}

		return sprintf(
			'<svg class="athemes-addons-brand-icon" width="%1$s" height="%1$s" viewBox="%2$s" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="%3$s" fill="%4$s"/></svg>',
			esc_attr( $size ),
			esc_attr( $icons[ $name ]['viewbox'] ),
			esc_attr( $icons[ $name ]['path'] ),
			esc_attr( $icons[ $name ]['fill'] )
		);
	}
}
