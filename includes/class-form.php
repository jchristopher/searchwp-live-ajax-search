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

		// this is the default configuration, devs can add their own using the filter below
		// by extending this array: the key is the name of the config, and the values should be
		// duplicated and customized for each configuration set they wish to utilize
		//
		// to use: set the data-swpconfig attribute value of the input to the applicable array key
		$default_config = array(
			'default' => array(                         // 'default' config
				'engine' => 'default',                  // search engine to use (if SearchWP is available)
				'input' => array(
					'delay'     => 500,                 // wait 500ms before triggering a search
					'min_chars' => 3,                   // wait for at least 3 characters before triggering a search
				),
				'results' => array(
					'position'  => 'bottom',            // where to position the results (bottom|top)
					'width'     => 'auto',              // whether the width should automatically match the input (auto|css)
					'offset'    => array(
						'x' => 0,                       // x offset (in pixels)
						'y' => 5                        // y offset (in pixels)
					)
				),
				'spinner' => array(                     // powered by http://fgnass.github.io/spin.js/
					'lines'         => 10,              // number of lines in the spinner
					'length'        => 8,               // length of each line
					'width'         => 4,               // line thickness
					'radius'        => 8,               // radius of inner circle
					'corners'       => 1,               // corner roundness (0..1)
					'rotate'        => 0,               // rotation offset
					'direction'     => 1,               // 1: clockwise, -1: counterclockwise
					'color'         => '#000',          // #rgb or #rrggbb or array of colors
					'speed'         => 1,               // rounds per second
					'trail'         => 60,              // afterglow percentage
					'shadow'        => false,           // whether to render a shadow
					'hwaccel'       => false,           // whether to use hardware acceleration
					'className'     => 'spinner',       // CSS class assigned to spinner
					'zIndex'        => 2000000000,      // z-index of spinner
					'top'           => '50%',           // top position (relative to parent)
					'left'          => '50%',           // left position (relative to parent)
				),
			),
		);

		// set up our parameters
		$params = array(
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'config'                => apply_filters( 'searchwp_live_search_default_config', $default_config ),
			'msg_no_config_found'   => __( 'No valid SearchWP Live Search configuration found!', 'searchwp' ),
		);

		// we need to JSON encode the configs
		$encoded_data = array(
			'l10n_print_after' => 'searchwp_live_search_params = ' . json_encode( $params ) . ';'
		);

		// localize and enqueue the script with all of the variable goodness
		wp_localize_script( 'swp-live-search-client', 'searchwp_live_search_params', $encoded_data );
		wp_enqueue_script( 'swp-live-search-client' );
	}

	function get_search_form( $html ) {
		if ( apply_filters( 'searchwp_live_search_hijack_get_search_form', true ) ) {
			$engine = apply_filters( 'searchwp_live_search_get_search_form_engine', 'default' );
			$config = apply_filters( 'searchwp_live_search_get_search_form_template', 'default' );
			// we're going to use 'name="s"' as our anchor
			$html = str_replace( 'name="s"', 'name="s" data-swplive="true" data-swpengine="' . esc_attr( $engine ) . '" data-swpconfig="' . esc_attr( $config ) . '"', $html );
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
						display:none;
					}

					.searchwp-live-search-results-showing {
						display:block;
						opacity:1;
						height:auto;
						overflow:auto;
					}

					.searchwp-live-search-no-results {
						padding:3em 2em 0;
						text-align:center;
					}
				</style>
			<?php
		}
	}

}
