<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class SearchWP_Live_Search_Template extends SearchWP_Live_Search {

	function get_template_directory() {
		return trailingslashit( $this->dir ) . 'templates';
	}

	function get_template_part( $slug, $name = null, $load = true ) {

		do_action( 'get_template_part_' . $slug, $slug, $name );

		$templates = array();

		if ( isset( $name ) ) {
			$templates[] = $slug . '-' . $name . '.php';
		}
		$templates[] = $slug . '.php';

		// allow filtration of template parts
		$templates = apply_filters( 'searchwp_live_search_get_template_part', $templates, $slug, $name );

		// return what was found
		return $this->locate_template( $templates, $load, false );
	}

	function locate_template( $template_names, $load = false, $require_once = true ) {

		// default to not found
		$located = false;

		$template_dir = apply_filters( 'searchwp_live_search_template_dir', 'searchwp-live-search' );

		// try to find the template file
		foreach ( (array) $template_names as $template_name ) {
			if ( empty( $template_name ) ) {
				continue;
			}
			$template_name = ltrim( $template_name, '/' );

			// check the child theme first
			$maybe_child_theme = trailingslashit( get_stylesheet_directory() ) . trailingslashit( $template_dir ) . $template_name;
			if ( file_exists( $maybe_child_theme ) ) {
				$located = $maybe_child_theme;
				break;
			}

			if ( ! $located ) {
				// check parent theme
				$maybe_parent_theme = trailingslashit( get_template_directory() ) . trailingslashit( $template_dir ) . $template_name;
				if ( file_exists( $maybe_parent_theme ) ) {
					$located = $maybe_parent_theme;
					break;
				}
			}

			if ( ! $located ) {
				// check theme compat
				$maybe_theme_compat = trailingslashit( $this->get_template_directory() ) . $template_name;
				if ( file_exists( $maybe_theme_compat ) ) {
					$located = $maybe_theme_compat;
					break;
				}
			}
		}

		if ( ( true == $load ) && ! empty( $located ) ) {
			load_template( $located, $require_once );
		}

		return $located;
	}

}
