<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( dirname( __FILE__ ) . '/class-template.php' );

/**
 * Class SearchWP_Live_Search_Client
 *
 * The SearchWP Live Ajax Search client that performs searches
 *
 * @since 1.0
 */
class SearchWP_Live_Search_Client extends SearchWP_Live_Search {

	/**
	 * Equivalent of __construct() — implement our hooks
	 *
	 * @since 1.0
	 *
	 * @uses add_action() to utilize WordPress Ajax functionality
	 */
	function setup() {
		add_action( 'wp_ajax_searchwp_live_search', array( $this, 'search' ) );
		add_action( 'wp_ajax_nopriv_searchwp_live_search', array( $this, 'search' ) );
	}

	/**
	 * Perform a search
	 *
	 * @since 1.0
	 *
	 * @uses sanitize_text_field() to sanitize input
	 * @uses SearchWP_Live_Search_Client::get_posts_per_page() to retrieve the number of results to return
	 */
	function search() {
		if( isset( $_REQUEST['swpquery'] ) && ! empty( $_REQUEST['swpquery'] ) ) {
			$query = sanitize_text_field( $_REQUEST['swpquery'] );
			if( class_exists( 'SearchWP' ) ) {
				// SearchWP powered search
				$posts = $this->searchwp( $query );
				$args = array(
					'post_type'     => 'any',           // we're limiting to a pre-set array of post IDs
					'post_status'   => 'any',           // we're limiting to a pre-set array of post IDs
					'post__in'      => $posts,
					'orderby'       => 'post__in',
				);
			} else {
				// native WordPress search
				$args = array(
					's' => $query
				);
			}
			$args['posts_per_page'] = $this->get_posts_per_page();
			$args['suppress_filters'] = true;
			$this->show_results( $args );
		}
		die(); // short circuit to keep the overhead of an admin-ajax.php call to a minimum
	}

	/**
	 * Perform a search via SearchWP
	 *
	 * @since 1.0
	 *
	 * @param string $query The search query
	 *
	 * @uses sanitize_text_field() to sanitize input
	 * @uses add_filter() to hook into SearchWP (if applicable) to allow custom pagination and prevent full Post object loading
	 * @uses SearchWP::instance() to retrieve the SearchWP singleton (if applicable)
	 * @uses SearchWP::search() to perform a SearchWP-powered search (if applicable)
	 *
	 * @return array Search results comprised of Post IDs
	 */
	function searchwp( $query = '' ) {
		$posts = array( 0 );
		if( class_exists( 'SearchWP' ) ) {
			$searchwp = SearchWP::instance();

			// set up custom posts per page
			add_filter( 'searchwp_posts_per_page', array( $this, 'get_posts_per_page' ) );

			// prevent loading Post objects, we only want IDs
			add_filter( 'searchwp_load_posts', '__return_false' );

			$engine = isset( $_REQUEST['swpengine'] ) ? sanitize_text_field( $_REQUEST['swpengine'] ) : 'default';

			// grab our post IDs
			$results = $searchwp->search( $engine, $query );

			// if no results were found we need to force our impossible array
			if( ! empty( $results ) ) {
				$posts = $results;
			}
		}
		return $posts;
	}

	/**
	 * Fire the results query and trigger the template loader
	 *
	 * @since 1.0
	 *
	 * @param array $args WP_Query arguments array
	 *
	 * @uses query_posts() to prep the WordPress environment in it's entirety for the template loader
	 * @uses sanitize_text_field() to sanitize input
	 * @uses SearchWP_Live_Search_Template
	 * @uses SearchWP_Live_Search_Template::get_template_part() to load the proper results template
	 */
	function show_results( $args = array() ) {
		// we're using query_posts() here because we want to prep the entire environment
		// for our template loader, allowing the developer to utilize everything they
		// normally would in a theme template (and reducing support requests)
		query_posts( $args );

		do_action( 'searchwp_live_search_alter_results' );

		// optionally pass along the SearchWP engine if applicable
		$engine = isset( $_REQUEST['swpengine'] ) ? sanitize_text_field( $_REQUEST['swpengine'] ) : '';

		// output the results using the results template
		$results = new SearchWP_Live_Search_Template();
		$results->get_template_part( 'search-results', $engine );
	}

	/**
	 * Retrieve the number of items to display
	 *
	 * @since 1.0
	 *
	 * @uses apply_filters to ensure the posts per page can be filterable via searchwp_live_search_posts_per_page
	 * @uses absint()
	 *
	 * @return int $per_page the number of items to display
	 */
	function get_posts_per_page() {
		// the default is 7 posts, but that can be filtered
		$per_page = absint( apply_filters( 'searchwp_live_search_posts_per_page', 7 ) );
		return $per_page;
	}

}
