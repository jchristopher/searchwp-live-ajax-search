(function($){
	var plugin_name = "searchwp_live_search",
		defaults = {
			delay: 300,		// wait 300ms before actually searching
			min_chars: 3 	// wait for at least three characters
		};

	function SearchwpLiveSearch( element, options ) {
		this.element = element;
		this.settings = $.extend( {}, defaults, options );
		this._defaults = defaults;
		this._name = plugin_name;
		this.timer = false;
		this.last_string = '';
		this.init();
	}

	SearchwpLiveSearch.prototype = {

		// prep the field and form
		init: function(){
			// prevent autocomplete
			this.element.attr('autocomplete','off');

			// set up the results container
			this.element.parents('form:eq(0)').append($('<div class="searchwp-live-search-results"></div>'));

			// bind to keyup
			this.element.keyup(jQuery.proxy(this.maybe_search, this));
		},

		// if the search value changed, we've waited long enough, and we have at least the minimum characters: search!
		maybe_search: function(e){
			clearTimeout(this.timer);
			if(this.last_string !== $.trim(e.currentTarget.value) && e.currentTarget.value.length >= this.settings.min_chars){
				this.timer = setTimeout(
					$.proxy(this.search, this, e),
					this.settings.delay
				);
			}
		},

		show_spinner: function(){
			// TODO: show spinner
			console.log('show spinner');
		},

		destroy_spinner: function(){
			// TODO: destroy spinner
			console.log('destroy spinner');
		},

		// perform the search
		search: function(e){

			var self = this,
				input = e.currentTarget,
				form = self.element.parents('form:eq(0)'),
				results = form.find('.searchwp-live-search-results'),
				action = form.attr('action'),
				values = form.serialize();

			// append our action, engine, and (redundant) query (so as to save the trouble of finding it again server side)
			values += '&action=searchwp_live_search&swpengine=' + $(input).data('swpengine') + '&swpquery=' + $(input).val();

			if(action.indexOf('?') !== -1){
				action = action.split('?');
				values += '&' + action[1];
			}

			// return if there were no results
			// TODO: figure out how to make this work
			if( results.find('.searchwp-live-search-not-found').length && input.value.indexOf(this.last_string) !== -1 ) {
				return;
			}

			this.last_string = input.value;

			$.ajax({
				url: searchwp_live_search_params.ajaxurl,
				type: "POST",
				data: values,
				beforeSend: function(){
					self.show_spinner();
				},
				success: function(response){
					if(response === 0){
						response = "";
					}
					results.html(response);
				},
				complete: function(){
					self.destroy_spinner();
				}
			});
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
