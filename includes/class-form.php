<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class SearchWP_Live_Search_Form extends SearchWP_Live_Search {

	function setup() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'get_search_form', array( $this, 'get_search_form' ) );
	}

	function assets() {
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
			$engine = apply_filters( 'searchwp_live_search_get_search_form_engine', 'default' );
			// we're going to use 'name="s"' as our anchor
			$html = str_replace( 'name="s"', 'name="s" data-swplive="true" data-swpengine="' . esc_attr( $engine ) . '"', $html );
		}
		return $html;
	}

}
