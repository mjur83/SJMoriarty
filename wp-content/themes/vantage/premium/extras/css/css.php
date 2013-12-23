<?php

/**
 * Add the custom CSS editor to the admin menu.
 */
function siteorigin_custom_css_admin_menu() {
	add_theme_page( __( 'Custom CSS', 'vantage' ), __( 'Custom CSS', 'vantage' ), 'edit_theme_options', 'siteorigin_custom_css', 'siteorigin_custom_css_page' );

	if ( isset( $_POST['siteorigin_custom_css_save'] ) ) {
		check_admin_referer( 'custom_css', '_sononce' );

		$theme = basename( get_template_directory() );
		update_option( 'siteorigin_custom_css[' . $theme . ']', stripslashes( $_POST['custom_css'] ) );
	}

}
add_action( 'admin_menu', 'siteorigin_custom_css_admin_menu' );


function siteorigin_custom_css_help() {
	$screen = get_current_screen();
	$theme = basename( get_template_directory() );
	$screen->add_help_tab( array(
		'id' => 'custom-css',
		'title' => __( 'Custom CSS', 'vantage' ),
		'content' => '<p>'
			. sprintf( __( "%s adds any custom CSS you enter here into your site's header. ", 'vantage' ), ucfirst( $theme ) )
			. __( "These changes will persist across updates so it's best to make all your changes here. ", 'vantage' )
			. sprintf( __( "Contact <a href='%s' target='_blank'>SiteOrigin Support</a> for help with making edits. ", 'vantage' ), 'http://siteorigin.com/support/contact/' )
			. '</p>'
	) );
}
add_action( 'load-appearance_page_siteorigin_custom_css', 'siteorigin_custom_css_help' );


/**
 *
 * @param $page
 * @return mixed
 */
function siteorigin_custom_css_enqueue( $page ) {
	if ( $page != 'appearance_page_siteorigin_custom_css' ) return;

	wp_enqueue_script( 'codemirror', get_template_directory_uri() . '/premium/extras/css/codemirror/lib/codemirror.min.js', array(), '2.3' );
	wp_enqueue_script( 'codemirror-mode-css', get_template_directory_uri() . '/premium/extras/css/codemirror/mode/css/css.min.js', array(), '2.3' );

	wp_enqueue_style( 'codemirror', get_template_directory_uri() . '/premium/extras/css/codemirror/lib/codemirror.css', array(), '2.3' );
	wp_enqueue_style( 'codemirror-theme-neat', get_template_directory_uri() . '/premium/extras/css/codemirror/theme/neat.css', array(), '2.3' );

	wp_enqueue_script( 'siteorigin-custom-css', get_template_directory_uri() . '/premium/extras/css/css.min.js', array( 'jquery' ) );
}
add_action( 'admin_enqueue_scripts', 'siteorigin_custom_css_enqueue' );


/**
 * Render the custom CSS page
 */
function siteorigin_custom_css_page() {
	$theme = basename( get_template_directory() );
	$custom_css = get_option( 'siteorigin_custom_css[' . $theme . ']', '' );

	?>
	<div class="wrap">
		<div id="icon-themes" class="icon32"><br></div>
		<h2><?php _e( 'Custom CSS', 'vantage' ) ?></h2>
	
		<form action="<?php echo esc_url( add_query_arg( null, null ) ) ?>" method="POST" style="margin-top:25px">
	
			<div id="custom-css-container" style="border:1px solid #DFDFDF; padding: 8px;">
				<textarea name="custom_css" id="custom-css-textarea"><?php echo esc_textarea( $custom_css ) ?></textarea>
				<?php wp_nonce_field( 'custom_css', '_sononce' ) ?>
			</div>
			<p class="description">
				<?php
				$theme = basename( get_template_directory() );
				printf( __( 'Changes apply to %s and its child themes', 'vantage' ), ucfirst( $theme ) );
				?>
			</p>
	
			<p class="submit">
				<input type="submit" name="siteorigin_custom_css_save" class="button-primary" value="<?php esc_attr_e( 'Save CSS', 'vantage' ); ?>" />
			</p>
	
		</form>
	</div>
	<?php
}


/**
 * Display the custom CSS.
 *
 * @return mixed
 */
function siteorigin_custom_css_display() {
	$theme = basename( get_template_directory() );
	$custom_css = get_option( 'siteorigin_custom_css[' . $theme . ']', '' );
	if ( empty( $custom_css ) ) return;

	// We just need to enqueue a dummy style
	echo "<style type='text/css'>\n";
	echo "$custom_css\n";
	echo "</style>\n";
}
add_action( 'wp_head', 'siteorigin_custom_css_display', 15 );