<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class SearchWP_Live_Search_Form extends SearchWP_Live_Search {

	function setup() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'get_search_form', array( $this, 'get_search_form' ) );
		add_action( 'wp_footer', array( $this, 'base_styles' ) );
	}

	function assets() {
		// styles
		wp_enqueue_style( 'searchwp-live-search', $this->url . '/assets/styles/style.css', null, $this->version );

		// scripts
		wp_enqueue_script( 'jquery' );
		wp_register_script( 'swp-live-search-client', $this->url . '/assets/javascript/searchwp-live-search.min.js', array( 'jquery' ), $this->version, false );
		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( 'swp-live-search-client', 'searchwp_live_search_params', $params );
		wp_enqueue_script( 'swp-live-search-client' );
	}

	function get_search_form( $html ) {
		if ( apply_filters( 'searchwp_live_search_hijack_get_search_form', true ) ) {
			// we're going to use 'name="s"' as our anchor
			$html = str_replace( 'name="s"', 'name="s" data-swplive="default"', $html );
		}
		return $html;
	}

	function base_styles() {
		if ( apply_filters( 'searchwp_live_search_base_styles', true ) ) {
			?>
				<style type="text/css">
					.searchwp-live-search-results {
						opacity:0;
						transition:opacity .25s ease-in-out;
						-moz-transition:opacity .25s ease-in-out;
						-webkit-transition:opacity .25s ease-in-out;
						height:0;
						overflow:hidden;
						z-index:9999;
						position:absolute;
					}

					.searchwp-live-search-results-showing {
						opacity:1;
						height:auto;
					}

					.searchwp-live-search-no-results {
						padding:2em;
						text-align:center;
					}
				</style>
			<?php
		}
	}

}
