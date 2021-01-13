<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		add_filter( 'option_active_plugins', array( $this, 'control_active_plugins' ) );
		add_filter( 'site_option_active_sitewide_plugins', array( $this, 'control_active_plugins' ) );
	}

	/**
	 * Potential (opt-in) performance tweak: skip any plugin that's not SearchWP-related.
	 */
	function control_active_plugins( $plugins ) {
		$applicable = apply_filters( 'searchwp_live_search_control_plugins_during_search', false );

		if ( ! $applicable || ! is_array( $plugins ) || empty( $plugins ) ) {
			return $plugins;
		}

		if ( ! isset( $_REQUEST['swpquery'] ) || empty( $_REQUEST['swpquery'] ) ) {
			return $plugins;
		}

		// The default plugin whitelist is anything SearchWP-related.
		$plugin_whitelist = array();
		foreach ( $plugins as $plugin_slug ) {
			if ( 0 === strpos( $plugin_slug, 'searchwp') ) {
				$plugin_whitelist[] = $plugin_slug;
			}
		}

		$active_plugins = array_values( (array) apply_filters( 'searchwp_live_search_plugin_whitelist', $plugin_whitelist ) );

		return $active_plugins;
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
		if ( isset( $_REQUEST['swpquery'] ) && ! empty( $_REQUEST['swpquery'] ) ) {

			$query = sanitize_text_field( stripslashes( $_REQUEST['swpquery'] ) );

			if ( class_exists( 'SearchWP' ) ) {
				// SearchWP powered search
				$posts = $this->searchwp( $query );

				// Normally we could use 'any' post type because we've already found our IDs
				// but if you use 'any' WP_Query will still take into consideration exclude_from_search
				// when we eventually run our query_posts() in $this->show_results() so we're
				// going to rebuild our array from the engine configuration post types and use that.
				if ( function_exists( 'SWP' ) ) {
					$post_types = SWP()->get_enabled_post_types_across_all_engines();
				} else if ( class_exists( '\\SearchWP\\Utils' ) ) {
					// SearchWP 4.0+.
					$global_engine_sources = \SearchWP\Utils::get_global_engine_source_names();
					$post_types = [];

					foreach ( $global_engine_sources as $global_engine_source ) {
						$indicator = 'post' . SEARCHWP_SEPARATOR;
						if ( $indicator === substr( $global_engine_source, 0, strlen( $indicator ) ) ) {
							$post_types[] = substr( $global_engine_source, strlen( $indicator ) );
						}
					}
				}

				$args = array(
					'post_type'        => $post_types,
					'post_status'      => 'any', // We're limiting to a pre-set array of post IDs.
					'post__in'         => $posts,
					'orderby'          => 'post__in',
					'suppress_filters' => true,
				);
			} else {
				// native WordPress search
				$args = $_POST;
				$args['s'] = $query;
				if ( ! isset( $_REQUEST['post_status'] ) ) {
					$args['post_status'] = 'publish';
				}
				if ( ! isset( $_REQUEST['post_type'] ) ) {
					$args['post_type'] = get_post_types( array(
						'public'              => true,
						'exclude_from_search' => false,
					) );
				}
			}

			$args['posts_per_page'] = ( isset( $_REQUEST['posts_per_page'] )
				? intval( $_REQUEST['posts_per_page'] )
				: $this->get_posts_per_page() );

			$args = apply_filters( 'searchwp_live_search_query_args', $args );

			$this->show_results( $args );
		}

		// Short circuit to keep the overhead of an admin-ajax.php call to a minimum.
		die();
	}

	/**
	 * Perform a search via SearchWP
	 *
	 * @since 1.0
	 * @param string $query The search query
	 * @return array Search results comprised of Post IDs
	 */
	function searchwp( $query = '' ) {
		$posts = array( 0 );

		if ( defined( 'SEARCHWP_VERSION' ) && version_compare( SEARCHWP_VERSION, '3.99.0', '>=' ) ) {
			// SearchWP 4.0 compatibility.
			$results = new \SWP_Query( array(
				's'              => $query,
				'engine'         => isset( $_REQUEST['swpengine'] ) ? sanitize_text_field( $_REQUEST['swpengine'] ) : 'default',
				'fields'         => 'ids',
				'posts_per_page' => $this->get_posts_per_page(),
			) );

			$this->results = $results->posts;

			if ( ! empty( $results->posts ) ) {
				$posts = $results->posts;
			}
		} else if ( class_exists( 'SearchWP' ) && method_exists( 'SearchWP', 'instance' ) ) {
			$searchwp = SearchWP::instance();

			// Set up custom posts per page.
			add_filter( 'searchwp_posts_per_page', array( $this, 'get_posts_per_page' ) );

			// Prevent loading Post objects, we only want IDs.
			add_filter( 'searchwp_load_posts', '__return_false' );

			$engine = isset( $_REQUEST['swpengine'] ) ? sanitize_text_field( $_REQUEST['swpengine'] ) : 'default';

			// Grab our post IDs.
			$results = $searchwp->search( $engine, $query );
			$this->results = $results;

			// If no results were found we need to force our impossible array.
			if ( ! empty( $results ) ) {
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
		global $wp_query;

		// We're using query_posts() here because we want to prep the entire environment
		// for our template loader, allowing the developer to utilize everything they
		// normally would in a theme template (and reducing support requests).
		query_posts( $args );

		// Ensure a proper found_posts count for $wp_query.
		if ( class_exists( 'SearchWP' ) && ! empty( $this->results ) ) {
			$wp_query->found_posts = count( $this->results );
		}

		do_action( 'searchwp_live_search_alter_results', $args );

		// Optionally pass along the SearchWP engine if applicable.
		$engine = isset( $_REQUEST['swpengine'] ) ? sanitize_text_field( $_REQUEST['swpengine'] ) : '';

		// Output the results using the results template.
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
		// The default is 7 posts, but that can be filtered.
		$per_page = absint( apply_filters( 'searchwp_live_search_posts_per_page', 7 ) );

		return $per_page;
	}

}
