(function($){
	var plugin_name = "searchwp_live_search";

	function SearchwpLiveSearch( element ) {
		this.config = null;

		// internal properties
		this.input_el = element;        		// the input element itself
		this.results_id = null;         		// the id attribute of the results wrapper for this search field
		this.results_el = null;         		// the results wrapper element itself
        this.parent_el = null;          		// allows results wrapper element to be injected into a custom parent element
		this.results_showing = false;   		// whether the results are showing
		this.form_el = null;            		// the search form element itself
		this.timer = false;             		// powers the delay check
		this.last_string = '';          		// the last search string submitted
		this.spinner = null;            		// the spinner
		this.spinner_showing = false;   		// whether the spinner is showing
		this.has_results = false;       		// whether results are showing
		this.current_request = false;	     	// the current request in progress
		this.results_destroy_on_blur = true;	// destroy the results
		this.a11y_keys = [ 27, 40, 13, 38, 9 ]; // list of keyCode used for a11y

		// kick it off
		this.init();
	}

	SearchwpLiveSearch.prototype = {

		// prep the field and form
		init: function(){

			var self = this,
				$input = this.input_el;
			this.form_el = $input.parents('form:eq(0)');
            this.results_id = this.uniqid('searchwp_live_search_results_');

			// establish our config (e.g. allow developers to override the config based on the value of the swpconfig data attribute)
			var valid_config = false;
			var config_template = $input.data('swpconfig');
			if(config_template && typeof config_template !== 'undefined') {
				// loop through all available configs
				for (var config_key in searchwp_live_search_params.config) {
					if( config_template === config_key ) {
						valid_config = true;
						this.config = searchwp_live_search_params.config[config_key];
					}
				}
			}else{
				// use the default
				for (var default_key in searchwp_live_search_params.config) {
					if( 'default' === default_key ) {
						valid_config = true;
						this.config = searchwp_live_search_params.config[default_key];
					}
				}
			}

			// if there wasn't a valid config found, alert() it because everything will break
			if(!valid_config){
				alert(searchwp_live_search_params.msg_no_config_found);
			}else{
				// allow the swpengine data attribute to override the engine set in the config (prevents new configs just to change engine)
				var engine = $input.data('swpengine');
				if( engine ) {
					this.config.engine = engine;
				}

				$input.data('swpengine',this.config.engine);

				// prevent autocomplete
				$input.attr('autocomplete','off');

				// #a11y: ARIA attributes
				var instruction_id = this.results_id  + '_instructions';
				$input.attr( 'aria-describedby', instruction_id );
				$input.attr( 'aria-owns', this.results_id );
				$input.attr( 'aria-expanded', 'false' );
				$input.attr( 'aria-autocomplete', 'both' );
				$input.attr( 'aria-activedescendant', '' );

				$input.after( '<p class="searchwp-live-search-instructions screen-reader-text" id="' + instruction_id + '">' + searchwp_live_search_params.aria_instructions + '</p>' );

				// set up and position the results container
                var results_el_html = '<div class="searchwp-live-search-results" id="' + this.results_id + '" role="listbox" tabindex="0"></div>';

                // if parent_el was specified, inject the results el into it instead of appending it to the body
                var swpparentel = $input.data('swpparentel');
                if (swpparentel) {
                    // specified as a data property on the html input.
                    this.parent_el = $(swpparentel);
                    this.parent_el.html(results_el_html);
                } else if (this.config.parent_el) {
                    // specified by the config set in php
                    this.parent_el = $(this.config.parent_el);
                    this.parent_el.html(results_el_html);

                } else {
                    // no parent, just append to the body
                    $('body').append($(results_el_html));
                }

				this.results_el = $('#'+this.results_id);
				this.position_results();
				$(window).resize(function(){
					self.position_results();
				});

				// prep the spinner
				if(this.config.spinner){
					this.spinner = new Spinner(this.config.spinner);
				}

				if(typeof this.config.abort_on_enter === 'undefined'){
					this.config.abort_on_enter = true;
				}

				// bind to keyup
				$input.keyup(function(e){
					if ( $.inArray( e.keyCode, self.a11y_keys ) > -1 ) {
						return;
					}
					// is there already a request active?
					if( self.current_request && ( self.config.abort_on_enter && e.keyCode === 13 ) ){
						self.current_request.abort();
					}
					if(!$.trim(self.input_el.val()).length) {
						self.destroy_results();
					}
					// if the user typed, show the results wrapper and spinner
					else if(!self.results_showing){
						self.position_results();
						self.results_el.addClass('searchwp-live-search-results-showing');
						self.show_spinner();
						self.results_showing = true;
					}
					// if there are already results on display and the user is changing the search string
					// remove the existing results and show the spinner
					if(self.has_results && !self.spinner_showing && self.last_string !== $.trim(self.input_el.val())){
						self.results_el.empty();
						self.show_spinner();
					}
				}).keyup($.proxy(this.maybe_search, this));

				// destroy the results when input focus is lost
				if(this.config.results_destroy_on_blur||typeof this.config.results_destroy_on_blur === 'undefined'){
					$('html').click(function(){
						self.destroy_results();
					});
				}
				$input.click(function(e){
					e.stopPropagation();
				});
				this.results_el.click(function(e){
					e.stopPropagation();
				});
			}
		},

		keyboard_navigation: function(){
			var self     = this,
				$input   = this.input_el,
				$results = this.results_el,
				focused_class = 'searchwp-live-search-result--focused',
				item_class = '.searchwp-live-search-result',
				a11y_keys = this.a11y_keys;

			$(document).off('keyup.searchwp_a11y').on('keyup.searchwp_a11y', function(e){

				// If results are not displayed, don't bind keypress.
				if ( ! $results.hasClass('searchwp-live-search-results-showing') ) {
					$(document).off('keyup.searchwp_a11y');
					return;
				}

				// If key pressed doesn't match our a11y keys list do nothing.
				if ( $.inArray( e.keyCode, a11y_keys ) === -1 ) {
					return;
				}

				// On `esc` keypress (only when input search is not focused).
				if ( e.keyCode === 27 && ! $input.is(':focus') ) {
					e.preventDefault();

					self.destroy_results();

					// Unbind keypress
					$(document).off('keyup.searchwp_a11y');

					// Get back the focus on input search.
					$input.focus();

					$(document).trigger("searchwp_live_escape_results");

					return;
				}

				// On `down` arrow keypress
				if ( e.keyCode === 40 ) {
					var $current = $( $results[0] ).find( '.' + focused_class );
					if ( $current.length === 1 && $current.next().length === 1 ) {
						$current.removeClass( focused_class ).attr('aria-selected', 'false')
								.next().addClass( focused_class ).attr('aria-selected', 'true')
								.find( 'a' ).focus();
						self.aria_activedescendant( true );
					} else {
						$current.removeClass( focused_class ).attr('aria-selected', 'false');
						$results.find( item_class + ':first' ).addClass( focused_class ).attr('aria-selected', 'true')
								.find( 'a' ).focus();
						if ( $results.find( item_class + ':first' ).length > 0 ) {
							self.aria_activedescendant( true );
						} else {
							self.aria_activedescendant( false );
						}
					}
					$(document).trigger( "searchwp_live_key_arrowdown_pressed" );
				}

				// On `up` arrow keypress
				if ( e.keyCode === 38 ) {
					var $currentItem = $( $results[0] ).find( '.' + focused_class );
					if ( $currentItem.length === 1 && $currentItem.prev().length === 1 ) {
						$currentItem.removeClass( focused_class ).attr('aria-selected', 'false')
								.prev().addClass( focused_class ).attr('aria-selected', 'true')
								.find( 'a' ).focus();
						self.aria_activedescendant( true );
					} else {
						$currentItem.removeClass( focused_class ).attr('aria-selected', 'false');
						$results.find( item_class + ':last' ).addClass( focused_class ).attr('aria-selected', 'true')
								.find( 'a' ).focus();
						if ( $results.find( item_class + ':last' ).length > 0 ) {
							self.aria_activedescendant( true );
						} else {
							self.aria_activedescendant( false );
						}
					}
					$(document).trigger( "searchwp_live_key_arrowup_pressed" );
				}

				// On 'enter' keypress
				if ( e.keyCode === 13 ) {
					$(document).trigger( "searchwp_live_key_enter_pressed" );
				}

				// On 'tab' keypress
				if ( e.keyCode === 9 ) {
					$(document).trigger( "searchwp_live_key_tab_pressed" );
				}

			});

			$(document).trigger( "searchwp_live_keyboad_navigation" );
		},

		aria_expanded: function( is_expanded ) {
			var $input = this.input_el;

			if ( is_expanded ) {
				$input.attr('aria-expanded', 'true');
			} else {
				$input.attr('aria-expanded', 'false');
				this.aria_activedescendant( false );
			}

			$(document).trigger( "searchwp_live_aria_expanded" );
		},

		aria_activedescendant: function( is_selected ) {
			var $input = this.input_el;

			if ( is_selected ) {
				$input.attr('aria-activedescendant', 'selectedOption');
			} else {
				$input.attr('aria-activedescendant', '');
			}

			$(document).trigger( "searchwp_live_aria_activedescendant" );
		},

		position_results: function(){
			var $input = this.input_el,
				input_offset = $input.offset(),
				$results = this.results_el,
				results_top_offset = 0;

            // don't try to position a results element when the input field is hidden
            if ($input.is(":hidden")) {
                return;
            }

			// check for an offset
			input_offset.left += parseInt(this.config.results.offset.x,10);
			input_offset.top += parseInt(this.config.results.offset.y,10);

			// position the results container
			switch(this.config.results.position){
				case 'top':
					results_top_offset = 0 - $results.height();
					break;
				default:
					results_top_offset = $input.outerHeight();
			}

			// apply the offset and finalize the position
			$results.css('left',input_offset.left);
			$results.css('top', ( input_offset.top + results_top_offset ) + 'px');
			if('auto'===this.config.results.width){
				$results.width($input.outerWidth()-parseInt($results.css('paddingRight').replace('px',''),10)-parseInt($results.css('paddingLeft').replace('px',''),10));
			}

			$(document).trigger( "searchwp_live_position_results", [ $results.css('left'), $results.css('top'), $results.width() ] );
		},

		destroy_results: function(e){
			this.hide_spinner();
			this.aria_expanded( false );
			this.results_el.empty().removeClass('searchwp-live-search-results-showing');
			this.results_showing = false;
			this.has_results = false;

			$(document).trigger( "searchwp_live_destroy_results" );
		},

		// if the search value changed, we've waited long enough, and we have at least the minimum characters: search!
		maybe_search: function(e){
			// If key pressed doesn't match our a11y keys list do nothing.
			if ( $.inArray( e.keyCode, this.a11y_keys ) > -1 ) {
				return;
			}

			clearTimeout(this.timer);
			if(e.currentTarget.value.length >= this.config.input.min_chars){
				this.timer = setTimeout(
					$.proxy(this.search, this, e),
					this.config.input.delay
				);
			}
		},

		show_spinner: function(){
			if(this.config.spinner&&!this.spinner_showing){
				this.spinner.spin(document.getElementById(this.results_id));
				this.spinner_showing = true;
				$(document).trigger( "searchwp_live_show_spinner" );
			}
		},

		hide_spinner: function(){
			if(this.config.spinner){
				this.spinner.stop();
				this.spinner_showing = false;
				$(document).trigger( "searchwp_live_hide_spinner" );
			}
		},

		// perform the search
		search: function(e){
			var self = this,
				$form = this.form_el,
				values = $form.serialize(),
				action = $form.attr('action') ? $form.attr('action') : '',
				$input = this.input_el,
				$results = this.results_el;

			$(document).trigger( "searchwp_live_search_start", [ $input, $results, $form, action, values ] );

			this.aria_expanded( false );

			// append our action, engine, and (redundant) query (so as to save the trouble of finding it again server side)
            values += '&action=searchwp_live_search&swpengine=' + $input.data('swpengine') + '&swpquery=' + $input.val();

			if(action.indexOf('?') !== -1){
				action = action.split('?');
				values += '&' + action[1];
			}

			this.last_string = $input.val();
			this.has_results = true;
			// put the request into the current_request var
			this.current_request = $.ajax({
				url: searchwp_live_search_params.ajaxurl,
				type: "POST",
				data: values,
				complete: function(){
					$(document).trigger( "searchwp_live_search_complete", [ $input, $results, $form, action, values ] );
					self.spinner_showing = false;
					self.hide_spinner();
					this.current_request = false;
				},
				success: function(response){
					if(response === 0){
						response = "";
					}
					$(document).trigger( "searchwp_live_search_success", [ $input, $results, $form, action, values ] );
					self.position_results();
					$results.html(response);
					self.aria_expanded( true );
					self.keyboard_navigation();
				}
			});
		},

		uniqid: function(prefix, more_entropy) {
			// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +    revised by: Kankrelune (http://www.webfaktory.info/)
			// %        note 1: Uses an internal counter (in php_js global) to avoid collision
			// *     example 1: uniqid();
			// *     returns 1: 'a30285b160c14'
			// *     example 2: uniqid('foo');
			// *     returns 2: 'fooa30285b1cd361'
			// *     example 3: uniqid('bar', true);
			// *     returns 3: 'bara20285b23dfd1.31879087'
			if (typeof prefix === 'undefined') {
				prefix = "";
			}

			var retId;
			var formatSeed = function (seed, reqWidth) {
				seed = parseInt(seed, 10).toString(16); // to hex str
				if (reqWidth < seed.length) { // so long we split
					return seed.slice(seed.length - reqWidth);
				}
				if (reqWidth > seed.length) { // so short we pad
					return new Array(1 + (reqWidth - seed.length)).join('0') + seed;
				}
				return seed;
			};

			// BEGIN REDUNDANT
			if (!this.php_js) {
				this.php_js = {};
			}
			// END REDUNDANT
			if (!this.php_js.uniqidSeed) { // init seed with big random int
				this.php_js.uniqidSeed = Math.floor(Math.random() * 0x75bcd15);
			}
			this.php_js.uniqidSeed++;

			retId = prefix; // start with prefix, add current milliseconds hex string
			retId += formatSeed(parseInt(new Date().getTime() / 1000, 10), 8);
			retId += formatSeed(this.php_js.uniqidSeed, 5); // add seed hex string
			if (more_entropy) {
				// for more entropy we add a float lower to 10
				retId += (Math.random() * 10).toFixed(8).toString();
			}

			return retId;
		}
	};

	$.fn[plugin_name] = function(options){
		this.each(function(){
			if(!$.data(this, "plugin_" + plugin_name)){
				$.data(this, "plugin_" + plugin_name, new SearchwpLiveSearch($(this), options));
			}
		});

		// chain jQuery functions
		return this;
	};
})(jQuery);

// find all applicable SearchWP Live Search inputs and bind them
jQuery(document).ready(function($){
	$('input[data-swplive="true"]').searchwp_live_search();
});
