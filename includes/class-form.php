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

		$default_config = array(
			// default config
			'default' => array(
				'engine' => 'default',
				'input' => array(
					'delay'     => 500,
					'min_chars' => 3,
				),
				'results' => array(
					'position'  => 'bottom',
					'width'     => 'auto',
					'offset'    => array(
						'x' => 0,
						'y' => 5
					)
				),
				'spinner' => array(
					'lines'         => 10,
					'length'        => 8,
					'width'         => 4,
					'radius'        => 8,
					'corners'       => 1,
					'rotate'        => 0,
					'direction'     => 1,
					'color'         => '#000',
					'speed'         => 1,
					'trail'         => 60,
					'shadow'        => false,
					'hwaccel'       => false,
					'className'     => 'spinner',
					'zIndex'        => 2000000000,
					'top'           => '50%',
					'left'          => '50%',
				),
			),
		);

		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'config' => apply_filters( 'searchwp_live_search_default_config', $default_config ),
			'msg_no_config_found' => __( 'No valid SearchWP Live Search configuration found!', 'searchwp' ),
		);
		$reshuffled_data = array(
			'l10n_print_after' => 'searchwp_live_search_params = ' . json_encode( $params ) . ';'
		);
		wp_localize_script( 'swp-live-search-client', 'searchwp_live_search_params', $reshuffled_data );
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
