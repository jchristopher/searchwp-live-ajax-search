=== SearchWP Live Ajax Search ===
Contributors: jchristopher
Tags: search, live, ajax
Requires at least: 3.9
Tested up to: 4.2
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Template powered live search for any WordPress theme. Does not require SearchWP, but will utilize it if available.

== Description ==

**Does not require** [SearchWP](https://searchwp.com/?utm_source=wordpressorg&utm_medium=link&utm_content=readme&utm_campaign=liveajaxsearch), but will utilize it if available. [Full documentation](https://searchwp.com/docs/extensions/live-search/) is available at searchwp.com.

Contributions welcome on GitHub! [https://github.com/jchristopher/searchwp-live-ajax-search/](https://github.com/jchristopher/searchwp-live-ajax-search/)

SearchWP Live Ajax Search enables AJAX powered live search for your search forms. Designed to be a developer's best friend, SearchWP Live Ajax Search aims to stay out of your way but at the same time allowing you to customize everything about it. It's set up to work with any WordPress theme and uses a template loader to display results. The template based approach allows you to seamlessly customize your SearchWP Live Search implementation without messing with dozens of cluttered options.

= Works best with SearchWP (but SearchWP is not necessary) =

SearchWP Live Ajax Search *is best utilized* in conjunction with [SearchWP](https://searchwp.com/?utm_source=wordpressorg&utm_medium=link&utm_content=readme&utm_campaign=liveajaxsearch), but **it is not required**. If SearchWP is installed and activated, SearchWP Live Ajax Search can be customized to use any of your search engines *per search form*.

= Customization =

You can customize the implementation of SearchWP Live Ajax Search to a great degree, including any number of developer-defined configurations. The results are based on a template loader, allowing SearchWP Live Ajax Search to stay out of your way and letting you write the results template as you would any other WordPress theme file.

*Everything* is powered by adding a single HTML5 data attribute (<code>data-swplive="true"</code>) to the input field of your search form. This happens automagically out of the box for any search forms generated from `get_search_form()`.

= Widget support =

SearchWP Live Ajax Search ships with a Widget allowing you to insert live search forms wherever you'd like.

== Installation ==

1. Download the plugin and extract the files
1. Upload `searchwp-live-search` to your `~/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Optionally customize the configuration: [full documentation](https://searchwp.com/docs/extensions/live-search/)
1. Optionally customize the results template: [full documentation](https://searchwp.com/docs/extensions/live-search/)

== Frequently Asked Questions ==

= Documentation? =

Of course! [Full documentation](https://searchwp.com/docs/extensions/live-search/)

= How do I add live search to my search form? =

SearchWP Live Ajax Search will **automatically** enable itself on any search forms generated with `get_search_form()`. You can prevent that with the following filter:

`add_filter( 'searchwp_live_search_hijack_get_search_form', '__return_false' );`

If you would like to manually enable SearchWP Live Ajax Search on a custom search form, simply add the following data attribute to the `input` you want to hook: <code>data-swplive="true"</code>

= How are search results generated? =

By default, SearchWP Live Ajax Search uses the default SearchWP Search Engine if you are using SearchWP. If you don't have SearchWP, native WordPress search results are provided. If you would like to customize which search engine SearchWP uses, simply add the following attribute to the form `input`: `data-swpengine="supplemental"` replacing 'supplemental' with your desired search engine name.

= How do I customize the results template =

SearchWP Live Ajax Search uses a template loader. In the plugin folder you will find a `templates` folder which includes `search-results.php` — that is what's used out of the box to output search results. To customize that output, simply create a folder called `searchwp-live-ajax-search` **in your theme directory** and copy `search-results.php` into that folder. SearchWP Live Ajax Search will then *use that file* instead of the one that shipped with the plugin, and you can customize it as you would other theme template files.

SearchWP Live Ajax Search also outputs two sets of styles. The primary set of styles simply preps the results wrapper to be positioned properly. The second set of styles controls the visual appearance. This abstraction was made to ensure customization is as straighforward as possible. You can disable the default 'theme' by dequeueing the applicable stylesheet, and you can also disable the foundational CSS as well. More information available in [the documentation](https://searchwp.com/docs/extensions/live-search/#customizing-results).

= How do I customize the spinner =

SearchWP Live Ajax Search uses a filter — <a href="https://searchwp.com/docs/extensions/live-search/#searchwp_live_search_configs"><code>searchwp_live_search_configs</code></a> — that allows you to fully customize the configuration used. Simply add a new key to the array passed through that filter, customizing the `default` values to whatever you want.

== Screenshots ==

1. SearchWP Live Ajax Search automagically working in Twenty Fourteen instantly after activating
2. Search results with default 'theme' (can be completely (completely) customized)
3. SearchWP Live Ajax Search Widget
4. SearchWP Live Ajax Search Widget Advanced

== Changelog ==

= 1.1 =
* Define default post statuses when using WordPress native search
* New filter `searchwp_live_search_query_args` to manipulate query args before searches
* Fixed an offset when positioning results on top of the search field

= 1.0.6 =
* PHP Warning cleanup

= 1.0.5 =
* New action: `searchwp_live_search_alter_results`
* Adds Relevanssi support (based on Dave's WordPress Live Search's implementation)

= 1.0.4 =
* Corrected the default results template folder name to be `searchwp-live-ajax-search` as is in the documentation
* Improvement: hide the results box when query is emptied (props Lennard Voogdt)
* Fixed an issue with Media not showing in results when integrated with SearchWP

= 1.0.3 =
* Fixed an issue where a false set of no results would be returned (props Lennard Voogdt)

= 1.0.2 =
* Resolved an issue where hitting Enter/Return prevented the search query from being passed to the results page
* Fixed potential false positive for DOING_AJAX (props justinsainton)
* Removed unnecessary call to get_the_ID() in the default results template (props justinsainton)
* Added escaping to permalink and post type name in the default results template (props justinsainton)
* Utilize a WordPress core translated string instead of a custom one (props justinsainton)
* Increase the priority for the get_search_form filter so as to accommodate existing filters

= 1.0.2 =
* Added Serbo-Croatian translation (props Andrijana Nikolic)

= 1.0.1 =
* Fixed a directory URL issue
* Fixed an indexOf JavaScript error

= 1.0 =
*  Initial release!
