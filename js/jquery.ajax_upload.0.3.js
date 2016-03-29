// Copyright (c) 2008 Andris Valums, http://valums.com
// Licensed under the MIT license (http://valums.com/s/license.txt)
(function($){
	// we need jQuery to run

	if ( ! $) return;

	$.ajax_upload = function(el, options){
		el = $(el);
				
		if (el.size() != 1 ){
			console.error('You passed ',this.size(),' elements to ajax_upload at once. (only 1 allowed)');
			return false;
		}
	
		return new Ajax_upload(el, options);		
	};

	/**
	 * @class Creates ajax file upload button
	 * @param el Element that will be converted to upload button
	 * @param User options
	 */
	var Ajax_upload = function(el, options){			
		this.el = el;
		this.wrapper = null;
		
		this.form = null;
		this.input = null;
		this.iframe = null;
		
		this.settings = {
			// Location of the server-side upload script
			action: '',
			// File upload name
			name: 'userfile',
			// Additional data to send
			data: {},
			/**
			 * Function that gets called when user selects file
			 * @param file Name of the selected file 
			 * @param extension of that file
			 * @return You can return false to cancel upload
			 */
			onSubmit: function(file, extension) {},
			/**
			 * Function that gets called when file upload is completed
			 * @param file Name of uploaded file
			 * @param response Server script output
			 */
			onComplete: function(file, response) {},
			/**
			 * Function that gets called when server returns "success" string
			 * @param file Name of uploaded file
			 */
			onSuccess: function(file){},
			/**
			 * Callback function that gets called when server returns something else,
			 * not the "success" string
			 * @param file Name of file that wasn't uploaded 
			 * @param response Server script output
			 */
			onError: function(file, response){}
		};

		// Merge the users options with our defaults
		// And then shorten name		
		var settings = $.extend(this.settings, options);		
		
		this.create_input(el);
		this.create_iframe();		
	}
	// assigning methods to our class
	Ajax_upload.prototype = {
		set_data : function(data){
			this.settings.data = data;
		},
		/**
		 * Creates form, that will contain upload 
		 * button, hidden input elements	
		 */
		create_form : function(){
			// enctype must be specified here
			// because changing this attr on the fly is not allowed
			this.form = 
				$('<form method="post" enctype="multipart/form-data"></form>')
				.appendTo('body')
				.attr({
					"action" : this.settings.action,
					"target" : this.iframe.attr('name')						
				});			

			// Create hidden input element for each param
			for (var i in this.settings.params){	
				$('<input type="hidden" />')
					.appendTo(form)
					.attr({
						'name': i,
						'value': params[i]
					});
			}
		},
		/**
		 * Creates invisible file input above the el 
		 */
		create_input : function(el){
			var self = this;
			
			el.wrap('<div></div>');
			this.wrapper = el.parent().css({
				position: 'relative',
				overflow: 'hidden',
				
				height: el.height(),
				width: el.width()
			});
			
			if (jQuery.browser.msie){
				// Fixing ie transparent background bug
				el.add(el.parents()).each(function(){
					
					var color = $(this).css('backgroundColor');
					var image = $(this).css('backgroundImage');

					if ( color != 'transparent' ||  image != 'none'){
						$(this).css('opacity', 1);
						return false;
					}
				});				
			}
						
			this.input = 
				$('<input type="file" />')
				.attr('name', this.settings.name)				
				.css({
					'position' : 'absolute',
					'margin': 0,
					'padding': 0,
					'width': '220px',
					'heigth': '10px',
										
					'opacity': 0					
				})
				.change(function(){
					// Submit form when value is changed
					self.submit();
					// IMPORTANT
				})
				.appendTo(this.wrapper);

			// Move the input with the mouse, so the user can't misclick it
			this.wrapper.mousemove(function(e){
				self.input.css({
					top: e.pageY-self.wrapper.offset().top-5+'px',
					left: e.pageX-self.wrapper.offset().left-170+'px'
				});
			});
					
		},
		/**
		 * Creates iframe with unique name
		 */
		create_iframe : function(){
			// unique name
			var name = 'iframe_' + new Date().getTime().toString().slice(7);
			// create iframe, so we dont need to refresh page
			this.iframe = 
				$('<iframe name="' + name + '"></iframe>')
				.css('display', 'none')
				.appendTo('body');									
		},
		clone_input : function(){
			var input = this.input, clone, saved_id;

			clone = $('<input type="file" />').insertAfter(input);
			clone.attr('name', input.attr('name'));	
			
			// do not copy id - move it
			saved_id = input.attr('id');
			input.attr('id', '');			
			clone.attr('id', saved_id);
			
			// copy other attributes
			clone.attr('class', input.attr('class'));
			clone.attr('style', input.attr('style'));
					
			return clone;		
		},
		file_from_path : function(file){
			var i = file.lastIndexOf('\\');
			if (i !== -1 ){
				return file.slice(i+1);
			}			
			return file;				
		},
		get_ext : function(file){
			var i = file.lastIndexOf('.');
			
			if (i !== -1 ){
				return file.slice(i+1);				
			}			
			return '';	
		},
		submit : function(){			
			var self = this, settings = this.settings;
			
			// get filename from input
			var file = this.file_from_path(this.input.val());
			
                        // get file upload name
                        var uploadName = this.settings.name;
						
			// execute user event
			if (settings.onSubmit.call(this, file, this.get_ext(file)) === false){
				// Do not continue if user func returns false
				return;
			}			

			this.create_form();
			
			// clone without a value
			var clone = this.clone_input();
			this.input.appendTo(this.form);
			
			this.form.submit();

			// clear
			this.input.remove();
			this.form.remove();		
			
			this.input = clone.change(function(){
				self.submit();
			});
			
			clone = null;
							
			var iframe = this.iframe;
			iframe.load(function(){
				var response = iframe.contents().find('body').html();
				
				settings.onComplete.call(self, file, response);
				if (result = 'success'){
					settings.onSuccess.call(self, file);	
				} else {
					settings.onError.call(self, file, response);	
				}
				
				// clear (wait a bit because of FF2 bug)
				setTimeout(function(){
					iframe.remove();
				}, 1);
				
				
			});
			
			// Create new iframe, so we can have multiple uploads at once
			this.create_iframe();		
		}
	};
})(jQuery);
