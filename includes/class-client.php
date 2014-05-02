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

		if( isset( $_REQUEST['swpengine'] ) && isset( $_REQUEST['swpquery'] ) && class_exists( 'SearchWP' ) ) {
			$engine = sanitize_text_field( $_REQUEST['swpengine'] );
			$query = sanitize_text_field( $_REQUEST['swpquery'] );

			$searchwp = SearchWP::instance();

			// set up custom posts per page
			function my_searchwp_live_search_posts_per_page() {
				$per_page = absint( apply_filters( 'searchwp_live_search_posts_per_page', 10 ) );
				return $per_page;
			}
			add_filter( 'searchwp_posts_per_page', 'my_searchwp_live_search_posts_per_page' );

			// prevent loading Post objects, we only want IDs
			add_filter( 'searchwp_load_posts', '__return_false' );

			// grab our post IDs
			$posts = $searchwp->search( $engine, $query );

			// set up an environment prepared for a template part
			$args = array(
				'post__in' => $posts,
				'orderby' => 'post__in',
			);
			query_posts( $args );

			// output the results
			$results = new SearchWP_Live_Search_Template();
			$results->get_template_part( 'search-results' );
		}

		die();
	}

}
