# SearchWP Live Ajax Search

SearchWP Live Ajax Search enables AJAX powered live search for your search forms.

Designed to be a developer's best friend, SearchWP Live Ajax Search aims to stay out of your way but at the same time allowing you to customize everything about it. It's set up to work with any WordPress theme and uses a template loader to display results. The template based approach allows you to seamlessly customize your SearchWP Live Search implementation without messing with dozens of cluttered options.

## Works best with [SearchWP](https://searchwp.com/?utm_source=github&utm_medium=link&utm_content=readme&utm_campaign=liveajaxsearch) (but SearchWP is not necessary)

SearchWP Live Ajax Search *is best utilized* in conjunction with [SearchWP](https://searchwp.com/?utm_source=github&utm_medium=link&utm_content=readme&utm_campaign=liveajaxsearch), but **it is not required**. If SearchWP is installed and activated, SearchWP Live Ajax Search can be customized to use any of your search engines *per search form*.

### Customization

You can customize the implementation of SearchWP Live Ajax Search to a great degree, including any number of developer-defined configurations. The results are based on a template loader, allowing SearchWP Live Ajax Search to stay out of your way and letting you write the results template as you would any other WordPress theme file.

*Everything* is powered by adding a single HTML5 data attribute (`data-swplive="true"`) to the input field of your search form. This happens automagically out of the box for any search forms generated from `get_search_form()`.

## Widget support

SearchWP Live Ajax Search ships with a Widget allowing you to insert live search forms wherever you'd like.

## Installation and Activation

1. Download the plugin and extract the files
1. Upload `searchwp-live-search` to your `~/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Optionally customize the configuration: [full documentation](https://searchwp.com/docs/extensions/live-search/)
1. Optionally customize the results template: [full documentation](https://searchwp.com/docs/extensions/live-search/)

## Developer notes

Please see the [documentation](https://searchwp.com/extensions/live-search/?utm_source=github&utm_medium=link&utm_content=readme) to read more about the plugin.

There is a build process for all JavaScript bundles contained within a single command:

`npm run watch`

This will execute all necessary processes that watch for file changes and subsequently generate all necessary code bundles.
