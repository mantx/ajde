;
Application.Navigation = function() {
	
	/** PRIVATE VARIABLES **/
	
	var $html = $('html');
	var $parentWrapper = $('body');
	var activeClass = 'overlay-active';
	
	var childOuter = '#ajax-wrapper';
	var childInner = 'div.overlay';
	
	var initialUrl;
	
	/** PRIVATE METHODS **/
	
	var getAttributeValue = function(element, attribute, source) {
		//var regex = RegExp("<" + element + "?\\w+(?:\\s+(?:" + attribute + "=\"([^\"]*)\")|[^\\s>]+|\\s+)*>","gi");
		//var regex = /<script[\s\S]*?src=['"]?(.+)['"][\s\S]*?>/gi;
		var regex = RegExp("<" + element + "[\\s\\S]*?" + attribute + "=['\\\"]?(.+)['\\\"][\\s\\S]*?>", "gi");
		var ret = new Array();
		var matches;
		while ( matches = regex.exec(source) ) {
			if (matches[1]) {
				ret.push(matches[1]);
			}
		}
		return ret;
	};
	
	var getLinkAttributeValue = function(attribute, source) {
		var regex = RegExp("<link.+?href=\"([^\"]+)\".+?>","gi");
		var ret = new Array();
		var matches;
		while ( matches = regex.exec(source) ) {
			if (matches[1]) {
				ret.push(matches[1]);
			}
		}
		return ret;
	};
	
	var getLinkAttributes = function(source) {
		var regex = RegExp("<link.+?href=\"([^\"]+)\".+?(media=\"([^\"]+)\")?.+?>","gi");
		var ret = new Array();
		var matches;
		while ( matches = regex.exec(source) ) {
			if (matches[1]) {
				var n = {
					href: matches[1], 
					media: ''
				};
				if (matches[3]) {
					n.media = matches[3];
				}
				ret.push(n);
			}
		}
		return ret;
	};
	
	var getScriptAttributeValue = function(attribute, source) {
		return getAttributeValue('script', attribute, source);
	};
	
	return {
		
		/** PUBLIC METHODS **/
		
		init: function() {			
			// Don't use cache
			$.ajaxSetup({cache: false});
			
			// Find all links with rel=ajax set
			Application.Navigation.initClickHandler();
			
			// Find all links with rel=close set
			Application.Navigation.initCloseHandler();
			
			// Find all links with rel=back set
			Application.Navigation.initBackHandler();
			
			// Shortcuts
			if (typeof AC !== 'undefined' && typeof AC.Shortcut !== 'undefined') {
				AC.Shortcut.add('Esc', Application.Navigation.closeHandler );
			}
			
			// Get initial pop event
			initialUrl = location.href;
			var popped = (window.history && window.history.state);			
			
			// Listen to state changes
			$(window).bind('popstate', function(e) {
				// Ignore inital popstate that some browsers fire on page load
				var initialPop = !popped && location.href == initialUrl;
				popped = true;
				if (initialPop) return;
				if (e.originalEvent.state) {}
				Application.Navigation.backHandler();
			});			
		},
		
		initClickHandler : function() {
			$('body').on('click', 'a[rel=ajax]', Application.Navigation.clickHandler );
		},
		
		initCloseHandler : function() {
			$('body').on('click', 'a[rel=close]', Application.Navigation.closeHandler );	
		},
		
		initBackHandler : function() {
			$('body').on('click', 'a[rel=back]', Application.Navigation.backHandler );	
		},
		
		clickHandler : function(e) {						
			// Prevent default action
			e.preventDefault();
			e.stopPropagation();

			// Get url and target element
			var url = $(this).attr('href');			

			// Load url via Ajax in target			
			Application.Navigation.updateHistory(url);
			Application.Navigation.load(url);

			// Prevent default action
			return false;
		},
		
		closeHandler : function(e) {
			// Ignore if we don't have an active overlay
			if (!$html.hasClass(activeClass)) {
				return true;
			}
			
			// Prevent default action
			if (e && e.preventDefault) e.preventDefault();
			if (e && e.stopPropagation) e.stopPropagation();
			
			Application.Navigation.close();
			
			return false;
		},
		
		backHandler : function(e) {
			// Prevent default action
			if (e && e.preventDefault) e.preventDefault();
			if (e && e.stopPropagation) e.stopPropagation();
			
			var url;
			
			if (typeof $(this)[0].nodeName !== 'undefined') {
				if ($(this).attr('href')) {
					url = $(this).attr('href');
				} else {
					window.history.go(-1);
					return false;
				}
			} else {
				url = document.location.href;
			}
			
			if (url == initialUrl) {
				Application.Navigation.close(null, true);
			} else {
				Application.Navigation.load(url);
			}
			
			return false;
		},
		
		load : function(url) {
			// Prepare request
			var data = {};
			$('body').addClass('loading');
			
			// Make request
			$.get(url, data, function(data, textStatus, jqXHR) {
				_gaq.push(['_trackEvent', 'Ajax navigation', 'Overlay success', url]);				
				Application.Navigation.setOverlay(data);
			}).error(function(jqXHR, message, exception) {
				if (exception == 'Unauthorized') {
					// Not logged in response
					_gaq.push(['_trackEvent', 'Ajax navigation', 'Need login', url]);
					Application.Navigation.setOverlay(jqXHR.responseText);
					// Do not redirect, use $responseCodeRoute in config instead
					//Application.Navigation.load('user/logon.html');
				} else if (exception == 'Forbidden') {
					// security measure (i.e. CSRF / session hijack) response
					_gaq.push(['_trackEvent', 'Ajax navigation', 'Forbidden', url]);
					$('body').removeClass('loading');
					AC.Core.Alert.warning(i18n.forbiddenWarning);
				} else {
					_gaq.push(['_trackEvent', 'Ajax navigation', 'Error', url]);
					$('body').removeClass('loading');
					AC.Core.Alert.error(i18n.requestError + ' (' + exception + ')');
				}
			});
		},
		
		setOverlay: function(page) {
			$(childOuter).remove();
			// TODO: look into this
			var $child = $(page).find('*:first').parent().first();
			// Other stuff
			Application.Navigation.addStyle(page, function() {					
				$html.addClass(activeClass);
				$parentWrapper.append($child);	
				Application.Navigation.addScript(page);
				$('body').removeClass('loading');
			});
		},
				
		close: function(e, skipUpdateHistory) {						
			// Prevent default action
			if (e && e.preventDefault) e.preventDefault();
			if (e && e.stopPropagation) e.stopPropagation();
						
			if (!skipUpdateHistory) Application.Navigation.updateHistory(initialUrl);
			$html.removeClass(activeClass);
			$(childOuter).remove();
			
			return false;
		},
		
		addScript: function(page) {
			var scripts = getScriptAttributeValue('src', page);			
			for(var i = 0; i < scripts.length; i++) {
				script = scripts[i];
				
				// Exceptions & errors are thrown using this
				var wf = document.createElement('script');
				wf.src = script;
				wf.type = 'text/javascript';
				wf.async = 'true';
				var s = document.getElementsByTagName('script')[0];
				s.parentNode.insertBefore(wf, s);
			};
		},
		
		addStyle: function(page, callback) {
			var styles = getLinkAttributes(page);
			var loaded = 0;
			var added = 0;
			for(var i = 0; i < styles.length; i++) {
				style = styles[i];
				if (!$('link[href="' + style.href + '"]').length) {
					added++;
					Application.Navigation.loadStyleSheet(style.href, style.media, function(success) {
						loaded++;
						if (loaded >= (added) ) {
							callback();
						}
					});
				}
			}
			if (added == 0) {
				callback();
			}
		},
		
		loadStyleSheet: function (path, media, fn, scope ) {
			var head = document.getElementsByTagName( 'head' )[0], // reference to document.head for appending/ removing link nodes
			link = document.createElement( 'link' );           // create the link node
			link.setAttribute( 'href', path );
			link.setAttribute( 'media', media );
			link.setAttribute( 'rel', 'stylesheet' );
			link.setAttribute( 'type', 'text/css' );

			var sheet, cssRules;
			// get the correct properties to check for depending on the browser
			if ( 'sheet' in link ) {
				sheet = 'sheet';
				cssRules = 'cssRules';
			}
			else {
				sheet = 'styleSheet';
				cssRules = 'rules';
			}

			var interval_id = setInterval( function() {                     // start checking whether the style sheet has successfully loaded
				try {
					if ( link[sheet] && link[sheet][cssRules].length ) { // SUCCESS! our style sheet has loaded
						clearInterval( interval_id );                      // clear the counters
						clearTimeout( timeout_id );
						fn.call( scope || window, true, link );           // fire the callback with success == true
					}
				} catch( e ) {} finally {}
			}, 10 ),                                                   // how often to check if the stylesheet is loaded
			timeout_id = setTimeout( function() {       // start counting down till fail
				clearInterval( interval_id );             // clear the counters
				clearTimeout( timeout_id );
				head.removeChild( link );                // since the style sheet didn't load, remove the link node from the DOM
				fn.call( scope || window, false, link ); // fire the callback with success == false
			}, 15000 );                                 // how long to wait before failing

			head.appendChild( link );  // insert the link node into the DOM and start loading the style sheet

			return link; // return the link node;
		},
		
		updateHistory: function(url, state) {
			if (typeof history.pushState !== 'undefined') {		
				history.pushState(state, document.title, url);
			}
		}
	}
}();

Application.addBootstrap(Application.Navigation.init);