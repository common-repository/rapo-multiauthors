<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
echo '<div class="wrap"> <h2>';
echo esc_html( get_admin_page_title() ); 
echo '</h2>';
echo '<form method="post" action="options.php">';
settings_fields( 'rma_settings' );
do_settings_sections( 'rma_settings' );
submit_button();
echo '</form> </div>';
