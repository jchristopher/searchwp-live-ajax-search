<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( dirname( __FILE__ ) . '/class-template.php' );

class SearchWP_Live_Search_Client extends SearchWP_Live_Search {

	function setup() {
		add_action( 'wp_ajax_searchwp_live_search', array( $this, 'search' ) );
		add_action( 'wp_ajax_nopriv_searchwp_live_search', array( $this, 'search' ) );
	}

	function search() {
		global $wpdb;

		$show_results = false;

		if( isset( $_REQUEST['swpengine'] ) && isset( $_REQUEST['swpquery'] ) && class_exists( 'SearchWP' ) ) {

			// use SearchWP
			$show_results = true;
			$searchwp = SearchWP::instance();

			// set up custom posts per page
			add_filter( 'searchwp_posts_per_page', array( $this, 'get_posts_per_page' ) );

			// prevent loading Post objects, we only want IDs
			add_filter( 'searchwp_load_posts', '__return_false' );

			// grab our post IDs
			$posts = $searchwp->search( sanitize_text_field( $_REQUEST['swpengine'] ), sanitize_text_field( $_REQUEST['swpquery'] ) );

			// we may need to force zero results
			if( empty( $posts ) ) {
				$posts = array( 0 );
			}

			// set up an environment prepared for a template part
			$args = array(
				'post__in' => $posts,
				'orderby' => 'post__in',
			);

		} elseif( isset( $_REQUEST['swpquery'] ) ) {

			// no SearchWP, let's just fall back to a native search
			$show_results = true;
			$args = array(
				'posts_per_page' => $this->get_posts_per_page(),
				's' => sanitize_text_field( $_REQUEST['swpquery'] )
			);

		}

		if( $show_results && isset( $args ) ) {
			query_posts( $args );

			$engine = isset( $_REQUEST['swpengine'] ) ? sanitize_text_field( $_REQUEST['swpengine'] ) : '';

			// output the results using the results template
			$results = new SearchWP_Live_Search_Template();
			$results->get_template_part( 'search-results', $engine );
		}

		die();
	}

	function get_posts_per_page() {
		$per_page = absint( apply_filters( 'searchwp_live_search_posts_per_page', 10 ) );
		return $per_page;
	}

}
