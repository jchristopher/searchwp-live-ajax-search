<?php

// class written by https://wordpress.org/plugins/daves-wordpress-live-search/

// Relevanssi "bridge" plugin
class SearchWP_Live_Search_Relevanssi_Bridge {

	function __construct() {
		add_action( 'searchwp_live_search_alter_results', array( 'SearchWP_Live_Search_Relevanssi_Bridge', 'alter_results' ) );
	}

	static function alter_results() {
		global $wp_query;
		relevanssi_do_query( $wp_query );
	}

}

if ( function_exists( 'relevanssi_do_query' ) ) {
	new SearchWP_Live_Search_Relevanssi_Bridge();
}
