<?php
/*
Plugin Name: SearchWP Live Search
Plugin URI: https://searchwp.com/
Description: Enhance your search forms with live search, powered by SearchWP (if installed)
Version: 1.0
Author: Jonathan Christopher
Author URI: https://searchwp.com/

Copyright 2014 Jonathan Christopher

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
if ( ! defined( 'ABSPATH' ) ) exit;

class SearchWP_Live_Search {

	public $dir;
	public $url;
	public $version = '1.0';

	function __construct() {
		$this->dir = dirname( __FILE__ );
		$this->url = plugins_url( 'searchwp-live-search', $this->dir );
	}

}

function searchwp_live_search_init() {
	if ( defined( 'DOING_AJAX' ) ) {
		// use as few resources as possible, short circuit as soon as possible
		include_once( dirname( __FILE__ ) . '/includes/class-client.php' );

		$client = new SearchWP_Live_Search_Client();
		$client->setup();
	}
	else {
		include_once( dirname( __FILE__ ) . '/includes/class-form.php' );

		$form = new SearchWP_Live_Search_Form();
		$form->setup();

		// TODO: Widget class
	}
}

add_action( 'init', 'searchwp_live_search_init' );
