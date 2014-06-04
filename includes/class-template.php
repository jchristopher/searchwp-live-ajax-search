<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class SearchWP_Live_Search_Template
 *
 * Template loader class based on Pippin Williamson's guide
 * http://pippinsplugins.com/template-file-loaders-plugins/
 *
 * @since 1.0
 */
class SearchWP_Live_Search_Template extends SearchWP_Live_Search {

	/**
	 * Retrieve the template directory within this plugin
	 *
	 * @since 1.0
	 *
	 * @return string The template directory within this plugin
	 */
	function get_template_directory() {
		return trailingslashit( $this->dir ) . 'templates';
	}

	/**
	 * Set up the proper template part array and locate it
	 *
	 * @sine 1.0
	 *
	 * @param string $slug The template slug (without file extension)
	 * @param null $name The template name (appended to $slug if provided)
	 * @param bool $load Whether to load the template part
	 *
	 * @return bool|string The location of the applicable template file
	 */
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

	/**
	 * Check for the applicable template in the child theme, then parent theme, and in the plugin dir as a last resort
	 * and output it if it was located
	 *
	 * @since 1.0
	 *
	 * @param array $template_names The potential template names in order of precedence
	 * @param bool $load Whether to load the template file
	 * @param bool $require_once Whether to require the template file once
	 *
	 * @return bool|string The location of the applicable template file
	 */
	function locate_template( $template_names, $load = false, $require_once = true ) {

		// default to not found
		$located = false;

		$template_dir = apply_filters( 'searchwp_live_search_template_dir', 'searchwp-live-ajax-search' );

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
