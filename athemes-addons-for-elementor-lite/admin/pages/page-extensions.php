<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$aafe_extensions = athemes_addons_get_translated_extensions();
?>

<div class="athemes-addons-modules-box">

	<div class="athemes-addons-modules-list">

	<?php
	foreach ( $aafe_extensions as $aafe_module_id => $aafe_module ) {
		athemes_addons_module_card( $aafe_module_id, $aafe_module, 'extension' );
	}
	?>

	</div>

</div>