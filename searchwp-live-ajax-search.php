<?php
/*
Plugin Name: SearchWP Live Ajax Search
Plugin URI: https://searchwp.com/
Description: Enhance your search forms with live search, powered by SearchWP (if installed)
Version: 1.6.0
Requires PHP: 5.6
Author: SearchWP, LLC
Author URI: https://searchwp.com/
Text Domain: searchwp-live-ajax-search

Copyright 2014-2021 SearchWP, LLC

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Widget support
include_once( dirname( __FILE__ ) . '/includes/class-widget.php' );

/**
 * Class SearchWP_Live_Search
 *
 * The main SearchWP Live Ajax Search Class properly routes searches and all other requests/utilization
 *
 * @since 1.0
 */
class SearchWP_Live_Search {
	public $dir;
	public $url;
	public $version = '1.6.0';
	public $results = array();

	function __construct() {
		$this->dir = dirname( __FILE__ );
		$this->url = plugins_url( 'searchwp-live-ajax-search', $this->dir );

		$this->upgrade();
	}

	function upgrade() {
		global $wpdb;

		$last_version = get_option( 'searchwp_live_search_version' );

		if ( false === $last_version ) {
			$last_version = 0;
		}

		if ( ! version_compare( $last_version, $this->version, '<' ) ) {
			return;
		}

		update_option( 'searchwp_live_search_last_update', current_time( 'timestamp' ), 'no' );

		update_option( 'searchwp_live_search_version', $this->version, 'no' );
	}
}

function searchwp_live_search_request_handler( $execute_search = false ) {
	include_once dirname( __FILE__ ) . '/includes/class-client.php';
	include_once dirname( __FILE__ ) . '/includes/class-relevanssi-bridge.php';

	$client = new SearchWP_Live_Search_Client();
	$client->setup();

	if ( $execute_search ) {
		$client->search();
	}
}

/**
 * Bootloader
 *
 * @since 1.0
 */
function searchwp_live_search_init() {
	load_plugin_textdomain( 'searchwp-live-ajax-search', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	// if an AJAX request is taking place, it's potentially a search so we'll want to
	// prepare for that else we'll prep the environment for the search form itself
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		searchwp_live_search_request_handler();
	} else {
		include_once dirname( __FILE__ ) . '/includes/class-form.php';
		$form = new SearchWP_Live_Search_Form();
		$form->setup();
	}
}

add_action( 'init', 'searchwp_live_search_init' );

function searchwp_live_search_admin_scripts() {
	if ( ! searchwp_live_search_notice_applicable() ) {
		return;
	}

	wp_enqueue_script( 'jquery' );
}

add_action( 'admin_enqueue_scripts', 'searchwp_live_search_admin_scripts' );

function searchwp_live_search_notice_dismissed() {
	check_ajax_referer( 'searchwp_live_search_notice_dismiss_nonce' );

	update_user_meta( get_current_user_id(), 'searchwp_live_search_notice_dismissed', true );

	wp_send_json_success();
}

add_action( 'wp_ajax_searchwp_live_search_notice_dismiss', 'searchwp_live_search_notice_dismissed' );

function searchwp_live_search_notice_applicable() {
	// If SearchWP is installed, bail out.
	if ( is_plugin_active( 'searchwp/searchwp.php' ) || is_plugin_active( 'searchwp/index.php' ) ) {
		return false;
	}

	// If it's been less than 3 days since the last update, bail out.
	$last_update = get_option( 'searchwp_live_search_last_update' );
	if ( empty( $last_update ) || ( current_time( 'timestamp') < absint( $last_update ) + ( DAY_IN_SECONDS * 3 ) ) ) {
		return false;
	}

	// If notice was dismissed, bail out.
	$dismissed = get_user_meta( get_current_user_id(), 'searchwp_live_search_notice_dismissed', true );
	if ( $dismissed ) {
		return false;
	}

	return true;
}

function searchwp_live_search_notice() {
	if ( ! searchwp_live_search_notice_applicable() ) {
		return;
	}

	?>
	<div class="notice notice-info is-dismissible searchwp-live-search-notice-dismiss">
		<p><strong>SearchWP Live Ajax Search</strong><br><a href="https://searchwp.com/?utm_source=wordpressorg&utm_medium=link&utm_content=notice&utm_campaign=liveajaxsearch" target="_blank">Improve your search results</a> and find out <a href="https://searchwp.com/extensions/metrics/?utm_source=wordpressorg&utm_medium=link&utm_content=notice&utm_campaign=liveajaxsearch" target="_blank">what your visitors are searching for</a> at the same time with <a href="https://searchwp.com/?utm_source=wordpressorg&utm_medium=link&utm_content=notice&utm_campaign=liveajaxsearch" target="_blank">SearchWP!</a></p>
		<script>
		(function( $ ) {
			'use strict';
			$( function() {
				$('.searchwp-live-search-notice-dismiss').on( 'click', '.notice-dismiss', function( event, el ) {
					var $notice = $(this).parent('.notice.is-dismissible');
					$.post(ajaxurl, {
						action: 'searchwp_live_search_notice_dismiss',
						_ajax_nonce: '<?php echo esc_js( wp_create_nonce( 'searchwp_live_search_notice_dismiss_nonce' ) ); ?>'
					});
				});
			} );
		})( jQuery );
		</script>
	</div>
	<?php
}

add_action( 'admin_notices', 'searchwp_live_search_notice' );
