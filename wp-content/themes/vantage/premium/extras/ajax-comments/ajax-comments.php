<?php

function siteorigin_ajax_comments_activate() {
	add_action( 'wp_enqueue_scripts', 'siteorigin_ajax_comments_enqueue_scripts' );
	add_filter( 'comment_post_redirect', 'siteorigin_ajax_comments_ajax_comment_rerender', 10, 2 );

	add_filter( 'wp_die_handler', 'siteorigin_ajax_comments_comment_ajax_handler' );
	add_filter( 'comments_template', 'siteorigin_ajax_comments_filter_comments_template' );
}

/**
 * Enqueue ajax comments scripts
 *
 * @action wp_enqueue_scripts
 */
function siteorigin_ajax_comments_enqueue_scripts() {
	if ( is_singular() && post_type_supports( get_post_type(), 'comments' ) ) {
		wp_enqueue_script( 'siteorigin-ajax-comments', get_template_directory_uri() . '/premium/extras/ajax-comments/ajax-comments.min.js', array( 'jquery' ), '1.0' );
	}
}

/**
 * Set up the error handler.
 *
 * @filter wp_die_handler
 */
function siteorigin_ajax_comments_comment_ajax_handler( $handler ) {
	global $pagenow;
	if ( $pagenow == 'wp-comments-post.php' && !empty( $_POST['is_ajax'] ) ) {
		$handler = 'siteorigin_ajax_comments_comment_ajax_error_handler';
	}
	return $handler;
}

/**
 * Ajax error handler
 *
 * @param $error
 */
function siteorigin_ajax_comments_comment_ajax_error_handler( $error ) {
	header( 'content-type: application/json', true );
	echo json_encode( array(
		'status' => 'error',
		'error' => $error,
	) );
	exit();
}

/**
 * Render all the ajax comments
 */
function siteorigin_ajax_comments_ajax_comment_rerender( $location, $comment ) {
	if ( empty( $_POST['is_ajax'] ) ) return $location;

	$post_id = isset( $_POST['comment_post_ID'] ) ? intval( $_POST['comment_post_ID'] ) : '';

	// We're going to pretend this is a single
	$query = array( 'post_id' => $post_id );

	if ( get_option( 'page_comments' ) ) {
		$args['per_page'] = get_option( 'comments_per_page' );
		$cpage = get_page_of_comment( $comment->comment_ID, $args );
		$query['cpage'] = $cpage;
	}

	// Prevents a conflict with older versions of Page Builder.
	remove_filter('the_posts', 'siteorigin_panels_prepare_post_content');
	query_posts( $query );

	global $wp_query;
	$wp_query->is_single = true;
	$wp_query->is_singular = true;

	ob_start();
	comments_template();
	$comment_html = ob_get_clean();

	wp_reset_query();

	echo json_encode( array(
		'status' => 'success',
		'html' => $comment_html,
	) );
	
	exit();
}

function siteorigin_ajax_comments_filter_comments_template ($file){
	global $siteorigin_ajax_comments_original_template;
	$siteorigin_ajax_comments_original_template = $file;
	return dirname(__FILE__).'/comments.php';
}