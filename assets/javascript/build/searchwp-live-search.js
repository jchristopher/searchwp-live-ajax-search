/**
 * Copyright (c) 2011-2014 Felix Gnass
 * Licensed under the MIT license
 * http://spin.js.org/
 *
 * Example:
    var opts = {
      lines: 12             // The number of lines to draw
    , length: 7             // The length of each line
    , width: 5              // The line thickness
    , radius: 10            // The radius of the inner circle
    , scale: 1.0            // Scales overall size of the spinner
    , corners: 1            // Roundness (0..1)
    , color: '#000'         // #rgb or #rrggbb
    , opacity: 1/4          // Opacity of the lines
    , rotate: 0             // Rotation offset
    , direction: 1          // 1: clockwise, -1: counterclockwise
    , speed: 1              // Rounds per second
    , trail: 100            // Afterglow percentage
    , fps: 20               // Frames per second when using setTimeout()
    , zIndex: 2e9           // Use a high z-index by default
    , className: 'spinner'  // CSS class to assign to the element
    , top: '50%'            // center vertically
    , left: '50%'           // center horizontally
    , shadow: false         // Whether to render a shadow
    , hwaccel: false        // Whether to use hardware acceleration (might be buggy)
    , position: 'absolute'  // Element positioning
    }
    var target = document.getElementById('foo')
    var spinner = new Spinner(opts).spin(target)
 */
;(function (root, factory) {

  /* CommonJS */
  if (typeof module == 'object' && module.exports) module.exports = factory()

  /* AMD module */
  else if (typeof define == 'function' && define.amd) define(factory)

  /* Browser global */
  else root.Spinner = factory()
}(this, function () {
  "use strict"

  var prefixes = ['webkit', 'Moz', 'ms', 'O'] /* Vendor prefixes */
    , animations = {} /* Animation rules keyed by their name */
    , useCssAnimations /* Whether to use CSS animations or setTimeout */
    , sheet /* A stylesheet to hold the @keyframe or VML rules. */

  /**
   * Utility function to create elements. If no tag name is given,
   * a DIV is created. Optionally properties can be passed.
   */
  function createEl (tag, prop) {
    var el = document.createElement(tag || 'div')
      , n

    for (n in prop) el[n] = prop[n]
    return el
  }

  /**
   * Appends children and returns the parent.
   */
  function ins (parent /* child1, child2, ...*/) {
    for (var i = 1, n = arguments.length; i < n; i++) {
      parent.appendChild(arguments[i])
    }

    return parent
  }

  /**
   * Creates an opacity keyframe animation rule and returns its name.
   * Since most mobile Webkits have timing issues with animation-delay,
   * we create separate rules for each line/segment.
   */
  function addAnimation (alpha, trail, i, lines) {
    var name = ['opacity', trail, ~~(alpha * 100), i, lines].join('-')
      , start = 0.01 + i/lines * 100
      , z = Math.max(1 - (1-alpha) / trail * (100-start), alpha)
      , prefix = useCssAnimations.substring(0, useCssAnimations.indexOf('Animation')).toLowerCase()
      , pre = prefix && '-' + prefix + '-' || ''

    if (!animations[name]) {
      sheet.insertRule(
        '@' + pre + 'keyframes ' + name + '{' +
        '0%{opacity:' + z + '}' +
        start + '%{opacity:' + alpha + '}' +
        (start+0.01) + '%{opacity:1}' +
        (start+trail) % 100 + '%{opacity:' + alpha + '}' +
        '100%{opacity:' + z + '}' +
        '}', sheet.cssRules.length)

      animations[name] = 1
    }

    return name
  }

  /**
   * Tries various vendor prefixes and returns the first supported property.
   */
  function vendor (el, prop) {
    var s = el.style
      , pp
      , i

    prop = prop.charAt(0).toUpperCase() + prop.slice(1)
    if (s[prop] !== undefined) return prop
    for (i = 0; i < prefixes.length; i++) {
      pp = prefixes[i]+prop
      if (s[pp] !== undefined) return pp
    }
  }

  /**
   * Sets multiple style properties at once.
   */
  function css (el, prop) {
    for (var n in prop) {
      el.style[vendor(el, n) || n] = prop[n]
    }

    return el
  }

  /**
   * Fills in default values.
   */
  function merge (obj) {
    for (var i = 1; i < arguments.length; i++) {
      var def = arguments[i]
      for (var n in def) {
        if (obj[n] === undefined) obj[n] = def[n]
      }
    }
    return obj
  }

  /**
   * Returns the line color from the given string or array.
   */
  function getColor (color, idx) {
    return typeof color == 'string' ? color : color[idx % color.length]
  }

  // Built-in defaults

  var defaults = {
    lines: 12             // The number of lines to draw
  , length: 7             // The length of each line
  , width: 5              // The line thickness
  , radius: 10            // The radius of the inner circle
  , scale: 1.0            // Scales overall size of the spinner
  , corners: 1            // Roundness (0..1)
  , color: '#000'         // #rgb or #rrggbb
  , opacity: 1/4          // Opacity of the lines
  , rotate: 0             // Rotation offset
  , direction: 1          // 1: clockwise, -1: counterclockwise
  , speed: 1              // Rounds per second
  , trail: 100            // Afterglow percentage
  , fps: 20               // Frames per second when using setTimeout()
  , zIndex: 2e9           // Use a high z-index by default
  , className: 'spinner'  // CSS class to assign to the element
  , top: '50%'            // center vertically
  , left: '50%'           // center horizontally
  , shadow: false         // Whether to render a shadow
  , hwaccel: false        // Whether to use hardware acceleration (might be buggy)
  , position: 'absolute'  // Element positioning
  }

  /** The constructor */
  function Spinner (o) {
    this.opts = merge(o || {}, Spinner.defaults, defaults)
  }

  // Global defaults that override the built-ins:
  Spinner.defaults = {}

  merge(Spinner.prototype, {
    /**
     * Adds the spinner to the given target element. If this instance is already
     * spinning, it is automatically removed from its previous target b calling
     * stop() internally.
     */
    spin: function (target) {
      this.stop()

      var self = this
        , o = self.opts
        , el = self.el = createEl(null, {className: o.className})

      css(el, {
        position: o.position
      , width: 0
      , zIndex: o.zIndex
      , left: o.left
      , top: o.top
      })

      if (target) {
        target.insertBefore(el, target.firstChild || null)
      }

      el.setAttribute('role', 'progressbar')
      self.lines(el, self.opts)

      if (!useCssAnimations) {
        // No CSS animation support, use setTimeout() instead
        var i = 0
          , start = (o.lines - 1) * (1 - o.direction) / 2
          , alpha
          , fps = o.fps
          , f = fps / o.speed
          , ostep = (1 - o.opacity) / (f * o.trail / 100)
          , astep = f / o.lines

        ;(function anim () {
          i++
          for (var j = 0; j < o.lines; j++) {
            alpha = Math.max(1 - (i + (o.lines - j) * astep) % f * ostep, o.opacity)

            self.opacity(el, j * o.direction + start, alpha, o)
          }
          self.timeout = self.el && setTimeout(anim, ~~(1000 / fps))
        })()
      }
      return self
    }

    /**
     * Stops and removes the Spinner.
     */
  , stop: function () {
      var el = this.el
      if (el) {
        clearTimeout(this.timeout)
        if (el.parentNode) el.parentNode.removeChild(el)
        this.el = undefined
      }
      return this
    }

    /**
     * Internal method that draws the individual lines. Will be overwritten
     * in VML fallback mode below.
     */
  , lines: function (el, o) {
      var i = 0
        , start = (o.lines - 1) * (1 - o.direction) / 2
        , seg

      function fill (color, shadow) {
        return css(createEl(), {
          position: 'absolute'
        , width: o.scale * (o.length + o.width) + 'px'
        , height: o.scale * o.width + 'px'
        , background: color
        , boxShadow: shadow
        , transformOrigin: 'left'
        , transform: 'rotate(' + ~~(360/o.lines*i + o.rotate) + 'deg) translate(' + o.scale*o.radius + 'px' + ',0)'
        , borderRadius: (o.corners * o.scale * o.width >> 1) + 'px'
        })
      }

      for (; i < o.lines; i++) {
        seg = css(createEl(), {
          position: 'absolute'
        , top: 1 + ~(o.scale * o.width / 2) + 'px'
        , transform: o.hwaccel ? 'translate3d(0,0,0)' : ''
        , opacity: o.opacity
        , animation: useCssAnimations && addAnimation(o.opacity, o.trail, start + i * o.direction, o.lines) + ' ' + 1 / o.speed + 's linear infinite'
        })

        if (o.shadow) ins(seg, css(fill('#000', '0 0 4px #000'), {top: '2px'}))
        ins(el, ins(seg, fill(getColor(o.color, i), '0 0 1px rgba(0,0,0,.1)')))
      }
      return el
    }

    /**
     * Internal method that adjusts the opacity of a single line.
     * Will be overwritten in VML fallback mode below.
     */
  , opacity: function (el, i, val) {
      if (i < el.childNodes.length) el.childNodes[i].style.opacity = val
    }

  })


  function initVML () {

    /* Utility function to create a VML tag */
    function vml (tag, attr) {
      return createEl('<' + tag + ' xmlns="urn:schemas-microsoft.com:vml" class="spin-vml">', attr)
    }

    // No CSS transforms but VML support, add a CSS rule for VML elements:
    sheet.addRule('.spin-vml', 'behavior:url(#default#VML)')

    Spinner.prototype.lines = function (el, o) {
      var r = o.scale * (o.length + o.width)
        , s = o.scale * 2 * r

      function grp () {
        return css(
          vml('group', {
            coordsize: s + ' ' + s
          , coordorigin: -r + ' ' + -r
          })
        , { width: s, height: s }
        )
      }

      var margin = -(o.width + o.length) * o.scale * 2 + 'px'
        , g = css(grp(), {position: 'absolute', top: margin, left: margin})
        , i

      function seg (i, dx, filter) {
        ins(
          g
        , ins(
            css(grp(), {rotation: 360 / o.lines * i + 'deg', left: ~~dx})
          , ins(
              css(
                vml('roundrect', {arcsize: o.corners})
              , { width: r
                , height: o.scale * o.width
                , left: o.scale * o.radius
                , top: -o.scale * o.width >> 1
                , filter: filter
                }
              )
            , vml('fill', {color: getColor(o.color, i), opacity: o.opacity})
            , vml('stroke', {opacity: 0}) // transparent stroke to fix color bleeding upon opacity change
            )
          )
        )
      }

      if (o.shadow)
        for (i = 1; i <= o.lines; i++) {
          seg(i, -2, 'progid:DXImageTransform.Microsoft.Blur(pixelradius=2,makeshadow=1,shadowopacity=.3)')
        }

      for (i = 1; i <= o.lines; i++) seg(i)
      return ins(el, g)
    }

    Spinner.prototype.opacity = function (el, i, val, o) {
      var c = el.firstChild
      o = o.shadow && o.lines || 0
      if (c && i + o < c.childNodes.length) {
        c = c.childNodes[i + o]; c = c && c.firstChild; c = c && c.firstChild
        if (c) c.opacity = val
      }
    }
  }

  if (typeof document !== 'undefined') {
    sheet = (function () {
      var el = createEl('style', {type : 'text/css'})
      ins(document.getElementsByTagName('head')[0], el)
      return el.sheet || el.styleSheet
    }())

    var probe = css(createEl('group'), {behavior: 'url(#default#VML)'})

    if (!vendor(probe, 'transform') && probe.adj) initVML()
    else useCssAnimations = vendor(probe, 'animation')
  }

  return Spinner

}));
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
