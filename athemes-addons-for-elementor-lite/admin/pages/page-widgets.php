<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php if ( ! empty( $aafe_widgets ) ) : ?>

<div class="athemes-addons-modules-box">

		<div class="athemes-addons-module-filter">
			<div class="filter-wrapper">
				<div class="module-filter-group">
					<?php $categories = athemes_addons_get_widget_categories(); ?>
					<select class="athemes-addons-modules-filter-button-category">
						<option value="all"><?php esc_html_e( 'Select Category', 'athemes-addons-for-elementor-lite' ); ?></option>
						<?php foreach ( $categories as $category_slug => $category_name ) : ?>
							<option value="<?php echo esc_attr( $category_slug ); ?>"><?php echo esc_html( $category_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>			
				<div class="module-filter-group athemes-addons-module-page-setting-field-radio">
					<div>
						<label for="all-modules-status" data-filter="all">
							<input type="radio" id="all-modules" name="athemes-addons-status-filter" value="all" checked>
							<span><?php esc_html_e( 'All', 'athemes-addons-for-elementor-lite' ); ?></span>
						</label>
						<label for="active-modules" data-filter="active">
							<input type="radio" id="active-modules" name="athemes-addons-status-filter" value="active">
							<span><?php esc_html_e( 'Active', 'athemes-addons-for-elementor-lite' ); ?></span>
						</label>
						<label for="inactive-modules" data-filter="inactive">
							<input type="radio" id="inactive-modules" name="athemes-addons-status-filter" value="inactive">
							<span><?php esc_html_e( 'Inactive', 'athemes-addons-for-elementor-lite' ); ?></span>
						</label>
					</div>
				</div>
				<div class="module-filter-group athemes-addons-module-page-setting-field-radio">
					<div>
						<label for="all-modules-freepro" data-filter="all">
							<input type="radio" id="all-modules" name="athemes-addons-free-pro-filter" value="all" checked>
							<span><?php esc_html_e( 'All', 'athemes-addons-for-elementor-lite' ); ?></span>
						</label>				
						<label for="free-modules" data-filter="free">
							<input type="radio" id="free-modules" name="athemes-addons-free-pro-filter" value="free">
							<span><?php esc_html_e( 'Free', 'athemes-addons-for-elementor-lite' ); ?></span>
						</label>
						<label for="pro-modules" data-filter="pro">
							<input type="radio" id="pro-modules" name="athemes-addons-free-pro-filter" value="pro">
							<span><?php esc_html_e( 'Pro', 'athemes-addons-for-elementor-lite' ); ?></span>
						</label>
					</div>
				</div>
			</div>
			<div class="module-filter-group module-filter-search">
				<span class="dashicons dashicons-search search-icon"></span>
				<input type="search" placeholder="<?php esc_attr_e( 'Search modules &hellip;', 'athemes-addons-for-elementor-lite' ); ?>" class="athemes-addons-modules-search" />
			</div>
		</div>

		<?php
		// Group widgets by category
		$widgets_by_category = array();
		foreach ( $aafe_widgets as $aafe_module_id => $aafe_module ) {
			$category = isset( $aafe_module['category'] ) ? $aafe_module['category'] : 'other';
			if ( ! isset( $widgets_by_category[ $category ] ) ) {
				$widgets_by_category[ $category ] = array();
			}
			$widgets_by_category[ $category ][ $aafe_module_id ] = $aafe_module;
		}

		// Known categories first, in order, then any others
		$category_names = athemes_addons_get_widget_categories();
		foreach ( array_keys( $widgets_by_category ) as $category ) {
			if ( ! isset( $category_names[ $category ] ) ) {
				$category_names[ $category ] = ucfirst( str_replace( '-', ' ', $category ) );
			}
		}

		foreach ( $category_names as $category_slug => $category_name ) :
			// Skip if no widgets in this category
			if ( ! isset( $widgets_by_category[ $category_slug ] ) ) {
				continue;
			}

			// Bulk actions only touch widgets the user can toggle
			$has_toggleable = defined( 'ATHEMES_AFE_PRO_VERSION' ) || array_filter( $widgets_by_category[ $category_slug ], function ( $widget ) {
				return empty( $widget['pro'] );
			} );
		?>
			<div class="athemes-addons-modules-category">
				<div class="athemes-addons-modules-category-header">
					<h3 class="athemes-addons-modules-category-title" data-category="<?php echo esc_attr( $category_slug ); ?>">
						<?php echo esc_html( $category_name ); ?>
					</h3>

					<?php if ( $has_toggleable ) : ?>
						<div class="athemes-addons-modules-bulk">
							<span class="athemes-addons-modules-bulk-feedback" role="status"></span>
							<button type="button" class="athemes-addons-modules-bulk-action" data-bulk="activate"><?php esc_html_e( 'Enable all', 'athemes-addons-for-elementor-lite' ); ?></button>
							<button type="button" class="athemes-addons-modules-bulk-action" data-bulk="deactivate"><?php esc_html_e( 'Disable all', 'athemes-addons-for-elementor-lite' ); ?></button>
						</div>
					<?php endif; ?>
				</div>

				<div class="athemes-addons-modules-list">
					<?php
					foreach ( $widgets_by_category[ $category_slug ] as $aafe_module_id => $aafe_module ) {
						athemes_addons_module_card( $aafe_module_id, $aafe_module, 'widget' );
					}
					?>
				</div>
			</div>
		<?php endforeach; ?>

</div>

<?php endif; ?>