<?php

/**
 * BEGIN Sourced from ~/wp-admin/admin-ajax.php
 */
define( 'DOING_AJAX', true );
if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

/** Load WordPress Bootstrap */
$wp_load = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! file_exists( $wp_load ) ) {
	die( '0' );
}

require_once $wp_load;

/** Allow for cross-domain requests (from the front end). */
send_origin_headers();

// Require an action parameter
if ( empty( $_REQUEST['action'] ) ) {
	wp_die( '0', 400 );
}

@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
@header( 'X-Robots-Tag: noindex' );

send_nosniff_header();
nocache_headers();

$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : '';

/**
 * END Sourced from ~/wp-admin/admin-ajax.php
 */

if ( 'searchwp_live_search' !== $action ) {
	wp_die( '0', 400 );
}

searchwp_live_search_request_handler( true );

die();
