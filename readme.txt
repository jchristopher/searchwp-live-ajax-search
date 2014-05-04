=== SearchWP Live Ajax Search ===
Contributors: jchristopher
Tags: search, live, ajax
Requires at least: 3.9
Tested up to: 3.9
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enhance your search forms with live search, powered by SearchWP (if installed)

== Description ==

**Does not require SearchWP** but will utilize it if available.

SearchWP Live Ajax Search enables AJAX powered live search for your search forms. Designed to be a developer's best friend, SearchWP Live Ajax Search aims to stay out of your way but at the same time allowing you to customize everything about it. The template based approach allows you to seamlessly customize your SearchWP Live Search implementation without messing with dozens of cluttered options.

SearchWP Live Ajax Search *is best utilized* in conjunction with [SearchWP](https://searchwp.com/), but **it is not required**. If SearchWP is installed and activated, SearchWP Live Ajax Search can be customized to use any of your search engines *per search form*.

You can customize the implementation of SearchWP Live Ajax Search to a great degree, including any number of developer-defined configurations. The results are based on a template loader, allowing SearchWP Live Ajax Search to stay out of your way and letting you write the results template as you would any other WordPress theme file.

*Everything* is powered by adding a single HTML5 data attribute (`data-swplive="true"`) to the input field of your search form. This happens automagically out of the box for any search forms generated from `get_search_form()`.

== Installation ==

1. Download the plugin and extract the files
1. Upload `searchwp-live-search` to your `~/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Optionally customize the configuration
1. Optionally customize the results template

== Frequently Asked Questions ==

= How do I add live search to my search form? =

SearchWP Live Ajax Search will **automatically** enable itself on any search forms generated with `get_search_form()`. You can prevent that with the following filter:

`add_filter( 'searchwp_live_search_hijack_get_search_form', '__return_false' );`

If you would like to manually enable SearchWP Live Ajax Search on a custom search form, simply add the following data attribute to the `input` you want to hook: `data-swplive="true"`

= How are search results generated? =

By default, SearchWP Live Ajax Search uses the default SearchWP Search Engine if you are using SearchWP. If you don't have SearchWP, native WordPress search results are provided. If you would like to customize which search engine SearchWP uses, simply add the following attribute to the form `input`: `data-swpengine="supplemental"` replacing 'supplemental' with your desired search engine name.

= How do I customize the results template =

SearchWP Live Ajax Search uses a template loader. In the plugin folder you will find a `templates` folder which includes `search-results.php` — that is what's used out of the box to output search results. To customize that output, simply create a folder called `searchwp-live-search` **in your theme directory** and copy `search-results.php` into that folder. SearchWP Live Ajax Search will then *use that file* instead of the one that shipped with the plugin, and you can customize it as you would other theme template files.

= How do I customize the spinner =

SearchWP Live Ajax Search uses a filter — `searchwp_live_search_configs` — that allows you to fully customize the configuration used. Simply add a new key to the array passed through that filter, customizing the `default` values to whatever you want.

== Screenshots ==

1. SearchWP Live Ajax Search automagically working in Twenty Fourteen instantly after activating
2. SearchWP Live Ajax Search Widget

== Changelog ==

= 1.0 =
*  Initial release!
