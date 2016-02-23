/**!
 * PJAX- Standalone
 *
 * A standalone implementation of Pushstate AJAX, for non-jQuery web pages.
 * jQuery are recommended to use the original implementation at: http://github.com/defunkt/jquery-pjax
 *
 * @version 0.6.1
 * @author Carl
 * @source https://github.com/thybag/PJAX-Standalone
 * @license MIT
 */
(function(){

	// Object to store private values/methods.
	var internal = {
		// Is this the first usage of PJAX? (Ensure history entry has required values if so.)
		"firstrun": true,
		// Borrowed wholesale from https://github.com/defunkt/jquery-pjax
		// Attempt to check that a device supports pushstate before attempting to use it.
		"is_supported": window.history && window.history.pushState && window.history.replaceState && !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]|WebApps\/.+CFNetwork)/),
		// Track which scripts have been included in to the page. (used if e)
		"loaded_scripts": []
	};

	// If PJAX isn't supported we can skip setting up the library all together
	// So as not to break any code expecting PJAX to be there, return a shell object containing
	// IE7 + compatible versions of connect (which needs to do nothing) and invoke ( which just changes the page)
	if(!internal.is_supported) {
		// PJAX shell, so any code expecting PJAX will work
		var pjax_shell = {
			"connect": function() { return; },
			"invoke": function() {
				var url = (arguments.length === 2) ? arguments[0] : arguments.url;
				document.location = url;
				return;
			}
		};
		// AMD support
		if (typeof define === 'function' && define.amd) {
			define( function() { return pjax_shell; });
		} else {
			window.pjax = pjax_shell;
		}
		return;
	}

	/**
	 * AddEvent
	 *
	 * @scope private
	 * @param obj Object to listen on
	 * @param event Event to listen for.
	 * @param callback Method to run when event is detected.
	 */
	internal.addEvent = function(obj, event, callback) {
		obj.addEventListener(event, callback, false);
	};

	/**
	 * Clone
	 * Util method to create copies of the options object (so they do not share references)
	 * This allows custom settings on different links.
	 *
	 * @scope private
	 * @param obj
	 * @return obj
	 */
	internal.clone = function(obj) {
		var object = {};
		// For every option in object, create it in the duplicate.
		for (var i in obj) {
			object[i] = obj[i];
		}
		return object;
	};

	/**
	 * triggerEvent
	 * Fire an event on a given object (used for callbacks)
	 *
	 * @scope private
	 * @param node. Objects to fire event on
	 * @return event_name. type of event
	 */
	internal.triggerEvent = function(node, event_name, data) {
		// Good browsers
		var evt = document.createEvent("HTMLEvents");
		evt.initEvent(event_name, true, true);
		// If additional data was provided, add it to event
		if(typeof data !== 'undefined') evt.data = data;
		node.dispatchEvent(evt);
	};

	/**
	 * popstate listener
	 * Listens for back/forward button events and updates page accordingly.
	 */
	internal.addEvent(window, 'popstate', function(st) {
		if(st.state !== null) {

			var opt = {
				'url': st.state.url,
				'container': st.state.container,
				'title' : st.state.title,
				'history': false
			};

			// Merge original in original connect options
			if(typeof internal.options !== 'undefined'){
				for(var a in internal.options){
					if(typeof opt[a] === 'undefined') opt[a] = internal.options[a];
				}
			}

			// Convert state data to PJAX options
			var options = internal.parseOptions(opt);
			// If something went wrong, return.
			if(options === false) return;
			// If there is a state object, handle it as a page load.
			internal.handle(options);
		}
	});

	/**
	 * attach
	 * Attach PJAX listeners to a link.
	 * @scope private
	 * @param link_node. link that will be clicked.
	 * @param content_node.
	 */
	internal.attach = function(node, options) {

		// Prepare event type to attach ('click' for links, 'submit' for forms)
		var eventType;

		// If we are dealing with links
		if (node.nodeName === 'A') {
			// Ignore external links.
			if ( node.protocol !== document.location.protocol ||
				node.host !== document.location.host ) {
				return;
			}

			// Ignore anchors on the same page
			if(node.pathname === location.pathname && node.hash.length > 0) {
				return;
			}

			// Ignore common non-PJAX loadable media types (pdf/doc/zips & images) unless user provides alternate array
			var ignoreFileTypes = ['pdf','doc','docx','zip','rar','7z','gif','jpeg','jpg','png'];
			if(typeof options.ignoreFileTypes === 'undefined') options.ignoreFileTypes = ignoreFileTypes;
			// Skip link if file type is within ignored types array
			if(options.ignoreFileTypes.indexOf( node.pathname.split('.').pop().toLowerCase() ) !== -1){
				return;
			}

			// Add link HREF to object
			options.url = node.href;
			// Attach 'click' event to link
			eventType = 'click';
		}
		// Else, we are dealing with forms
		else if (node.nodeName === 'FORM') {
			// Add form ACTION to object
			options.url = node.getAttribute('action');
			options.httpVerb = node.getAttribute('method').toUpperCase();
			// Attach 'submit' event to form
			eventType = 'submit';
		} else return;

		// If PJAX data is specified, use as container
		if(node.getAttribute('data-pjax')) {
			options.container = node.getAttribute('data-pjax');
		}

		// If data-title is specified, use as title.
		if(node.getAttribute('data-title')) {
			options.title = node.getAttribute('data-title');
		}

		// Check options are valid.
		options = internal.parseOptions(options);
		if(options === false) return;

		// Attach event.
		internal.addEvent(node, eventType, function(event) {
			// Allow middle click (pages in new windows)
			if ( event.which > 1 || event.metaKey || event.ctrlKey ) return;
			// Don't fire normal event
			if(event.preventDefault){ event.preventDefault(); }else{ event.returnValue = false; }
			// Take no action if we are already on said page?
			if(document.location.href === options.url && eventType !== 'submit') return false;
			// Serialize form data if needed
			if (eventType === 'submit') {
				// GET form, append query params
				if (options.httpVerb == 'GET') {options.url += '?'+internal.serialize(node);}
				// POST form, populate formData to send in xmlHttpRequest
				else {options.formData = internal.serialize(node);}
			}
			// handle the load.
			internal.handle(options);
		});
	};

	/**
	 * parseLinks
	 * Parse all links within a DOM node, using settings provided in options.
	 * @scope private
	 * @param dom_obj. Dom node to parse for links.
	 * @param options. Valid Options object.
	 */
	internal.parseLinks = function(dom_obj, options) {

		var nodes = [],
			link_nodes,
			form_nodes;

		if(typeof options.useClass !== 'undefined'){
			// Get all nodes with the provided class name.
			nodes = dom_obj.getElementsByClassName(options.useClass);
		}else{
			// If no class was provided, just get all the merged links and forms
			link_nodes = dom_obj.getElementsByTagName('a');
			form_nodes = dom_obj.getElementsByTagName('form');
			nodes.push.apply(nodes, link_nodes);
			nodes.push.apply(nodes, form_nodes);
		}

		// For all returned nodes
		for(var i=0,tmp_opt; i < nodes.length; i++) {
			var node = nodes[i];
			if(typeof options.excludeClass !== 'undefined') {
				if(node.className.indexOf(options.excludeClass) !== -1) continue;
			}
			// Override options history to true, else link parsing could be triggered by back button (which runs in no-history mode)
			tmp_opt = internal.clone(options);
			tmp_opt.history = true;
			internal.attach(node, tmp_opt);
		}

		if(internal.firstrun) {
			// Store array or all currently included script src's to avoid PJAX accidentally reloading existing libraries
			var scripts = document.getElementsByTagName('script');
			for(var c=0; c < scripts.length; c++) {
				if(scripts[c].src && internal.loaded_scripts.indexOf(scripts[c].src) === -1){
					internal.loaded_scripts.push(scripts[c].src);
				}
			}

			// Fire ready event once all links are connected
			internal.triggerEvent(internal.get_container_node(options.container), 'ready');

		}
	};

	/**
	 * SmartLoad
	 * Smartload checks the returned HTML to ensure PJAX ready content has been provided rather than
	 * a full HTML page. If a full HTML has been returned, it will attempt to scan the page and extract
	 * the correct HTML to update our container with in order to ensure PJAX still functions as expected.
	 *
	 * @scope private
	 * @param HTML (HTML returned from AJAX)
	 * @param options (Options object used to request page)
	 * @return HTML to append to our page.
	 */
	internal.smartLoad = function(html, options) {
		// Grab the title if there is one
		var title = html.getElementsByTagName('title')[0];

		if(title) {
            // document.title = title.innerHTML; // Original code, but title doesn't update everywhen
            options.title = title.innerHTML;
        }

		// Going by caniuse all browsers that support the pushstate API also support querySelector's
		// see: http://caniuse.com/#search=push
		// see: http://caniuse.com/#search=querySelector
		var container = html.querySelector("#" + options.container.id);
		if(container !== null) return container;

		// If our container was not found, HTML will be returned as is.
		return html;
	};

	/**
	 * Update Content
	 * Updates DOM with content loaded via PJAX
	 *
	 * @param html DOM fragment of loaded container
	 * @param options PJAX configuration options
	 * return options
	 */
	internal.updateContent = function(html, options){
		// Create in memory DOM node, to make parsing returned data easier
		var tmp = document.createElement('div');
		tmp.innerHTML = html;

		// Ensure we have the correct HTML to apply to our container.
		if(options.smartLoad) tmp = internal.smartLoad(tmp, options);

		// If no title was provided, extract it
		if(typeof options.title === 'undefined'){
			// Use current doc title (this will be updated via smart load if its enabled)
			options.title = document.title;

			// Attempt to grab title from non-smart loaded page contents
			if(!options.smartLoad){
				var tmpTitle = tmp.getElementsByTagName('title');
				if(tmpTitle.length !== 0) options.title = tmpTitle[0].innerHTML;
			}
		}

		// Update the DOM with the new content
		options.container.innerHTML = tmp.innerHTML;

		// Run included JS?
		if(options.parseJS) internal.runScripts(tmp);
		if(options.parseCSS) internal.addStyles(tmp);

		// Send data back to handle
		return options;
	};

	/**
	 * runScripts
	 * Execute JavaScript on pages loaded via PJAX
	 *
	 * Note: In-line JavaScript is run each time a page is hit, while external JavaScript
	 *		is only loaded once (Although remains loaded while the user continues browsing)
	 *
	 * @param html DOM fragment of loaded container
	 * return void
	 */
	internal.runScripts = function(html){
		// Extract JavaScript & eval it (if enabled)
		var scripts = html.getElementsByTagName('script');
		for(var sc=0; sc < scripts.length;sc++) {
			// If has an src & src isn't in "loaded_scripts", load the script.
			if(scripts[sc].src && internal.loaded_scripts.indexOf(scripts[sc].src) === -1){
				// Append to head to include
				var s = document.createElement("script");
				s.src = scripts[sc].src;
				document.head.appendChild(s);
				// Add to loaded list
				internal.loaded_scripts.push(scripts[sc].src);
			}else{
				// If raw JS, eval it.
				eval(scripts[sc].innerHTML);
			}
		}
	};

	/**
	 * addStyles
	 * Add StyleSheets on pages loaded via PJAX
	 *
	 * Note: In-line JavaScript is run each time a page is hit, while external JavaScript
	 *		is only loaded once (Although remains loaded while the user continues browsing)
	 *
	 * @param html DOM fragment of loaded container
	 * return void
	 */
	internal.addStyles = function(html){
		return;
        // console.log(document.styleSheets[0]);
		// Extract JavaScript & eval it (if enabled)
		// var stylesheets = html.getElementsByTagName('link').filter(function(link){
        //     return link.getAttribute('rel') == 'stylesheet';
        // });
        // console.log(stylesheets);
		// for(var sc=0; sc < scripts.length;sc++) {
		// 	// If has an src & src isn't in "loaded_scripts", load the script.
		// 	if(scripts[sc].src && internal.loaded_scripts.indexOf(scripts[sc].src) === -1){
		// 		// Append to head to include
		// 		var s = document.createElement("script");
		// 		s.src = scripts[sc].src;
		// 		document.head.appendChild(s);
		// 		// Add to loaded list
		// 		internal.loaded_scripts.push(scripts[sc].src);
		// 	}else{
		// 		// If raw JS, eval it.
		// 		eval(scripts[sc].innerHTML);
		// 	}
		// }
	};

	/**
	 * handle
	 * Handle requests to load content via PJAX.
	 * @scope private
	 * @param url. Page to load.
	 * @param node. Dom node to add returned content in to.
	 * @param addtohistory. Does this load require a history event.
	 */
	internal.handle = function(options) {

		// Fire beforeSend Event.
		internal.triggerEvent(options.container, 'beforeSend', options);

		// Select only needed parameters for request from {options} object
		var requestData = {
			url: options.url,
			httpVerb: options.httpVerb,
			formData: options.formData
		};

		// Do the request
		internal.request(requestData, function(response) {

			var html = response.responseText;
			// console.log(response);

			// Fail if unable to load HTML via AJAX
			if(html === false){
				internal.triggerEvent(options.container,'complete', options);
				internal.triggerEvent(options.container,'error', options);
				return;
			}

			// Parse page & update DOM
			options = internal.updateContent(html, options);

			// Do we need to add this to the history?
			if(options.history) {
				// If this is the first time pjax has run, create a state object for the current page.
				if(internal.firstrun){
					window.history.replaceState({'url': document.location.href, 'container':  options.container.id, 'title': document.title}, document.title);
					internal.firstrun = false;
				}
				// Update browser history
				window.history.pushState({'url': response.responseURL, 'container': options.container.id, 'title': options.title }, options.title , response.responseURL);
				// window.history.pushState({'url': options.url, 'container': options.container.id, 'title': options.title }, options.title , options.url);
			}

			// Initialize any new links found within document (if enabled).
			if(options.parseLinksOnload){
				internal.parseLinks(options.container, options);
			}

			// Fire Events
			internal.triggerEvent(options.container,'complete', options);
			internal.triggerEvent(options.container,'success', options);

			// Don't track if page isn't part of history, or if autoAnalytics is disabled
			if(options.autoAnalytics && options.history) {
				// If autoAnalytics is enabled and a Google analytics tracker is detected push
				// a trackPageView, so PJAX loaded pages can be tracked successfully.
				if(window._gaq) _gaq.push(['_trackPageview']);
				if(window.ga) ga('send', 'pageview', {'page': options.url, 'title': options.title});
			}

			// Set new title
			document.title = options.title;

            // Handle hashes in URLs (for showing specific posts in topics)
            var urlHash = options.url.match(/#p\d+$/);

			// Scroll page to top on new page load
			if(options.returnToTop && !urlHash) {
				window.scrollTo(0, 0);
			} else {
                location.hash = urlHash[0];
            }
		});
	};

	/**
	 * Request
	 * Performs AJAX request to page and returns the result..
	 *
	 * @scope private
	 * @param location. Page to request.
	 * @param callback. Method to call when a page is loaded.
	 */
	internal.request = function(options, callback) {
		// Create xmlHttpRequest object.
		var xmlhttp;
		try {
			xmlhttp = window.XMLHttpRequest? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
		}  catch (e) {
			console.log("Unable to create XMLHTTP Request");
			return;
		}
		// Add state listener.
		xmlhttp.onreadystatechange = function() {
			if ((xmlhttp.readyState === 4) && (xmlhttp.status === 200)) {
				// Success, Return HTML
				return callback(xmlhttp);
				// callback(xmlhttp.responseText);
			}else if((xmlhttp.readyState === 4) && (xmlhttp.status === 404 || xmlhttp.status === 500)){
				// error (return false)
				callback(false);
			}
		};
		// xmlhttp.onload = function(evt) {
		// 	return callback(evt.currentTarget);
		// };
		// xmlhttp.onprogress = function(evt) {
		// 	console.log('Reaching '+evt.target.responseURL);
		// };
		// xmlhttp.onerror = function(evt) {
		// 	return callback(false);
		// };

		// xmlhttp.addEventListener("progress", updateProgress);
		// xmlhttp.addEventListener("load", transferComplete);
		// xmlhttp.addEventListener("error", transferFailed);
		// xmlhttp.addEventListener("abort", transferCanceled);

		// Secret pjax ?get param so browser doesn't return pjax content from cache when we don't want it to
		// Switch between ? and & so as not to break any URL params (Based on change by zmasek https://github.com/zmasek/)
		xmlhttp.open(options.httpVerb, options.url, true);
		// xmlhttp.open(options.httpVerb, options.url + ((!/[?&]/.test(options.url)) ? '?_pjax' : '&_pjax'), true);
		// Add headers so things can tell the request is being performed via AJAX.
		xmlhttp.setRequestHeader('X-PJAX', 'true'); // PJAX header
		xmlhttp.setRequestHeader('X-Requested-With', 'XMLHttpRequest');// Standard AJAX header.
		if (options.httpVerb === 'POST') {
			xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		}

		xmlhttp.send(options.formData);
	};

	/**
	 * Adapted from {@link http://www.bulgaria-web-developers.com/projects/javascript/serialize/}
	 * Changes:
	 *     Ensures proper URL encoding of name as well as value
	 *     Preserves element order
	 *     XHTML and JSLint-friendly
	 *     Disallows disabled form elements and reset buttons as per HTML4 [successful controls]{@link http://www.w3.org/TR/html401/interact/forms.html#h-17.13.2}
	 *         (as used in jQuery). Note: This does not serialize <object>
	 *         elements (even those without a declare attribute) or
	 *         <input type="file" />, as per jQuery, though it does serialize
	 *         the <button>'s (which are potential HTML4 successful controls) unlike jQuery
	 * @license MIT/GPL
	*/
	internal.serialize = function(form) {
		'use strict';
		var i, j, len, jLen, formElement, q = [];
		function urlencode (str) {
			// http://kevin.vanzonneveld.net
			// Tilde should be allowed unescaped in future versions of PHP (as reflected below), but if you want to reflect current
			// PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
			return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
			replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
		}
		function addNameValue(name, value) {
			q.push(urlencode(name) + '=' + urlencode(value));
		}
		if (!form || !form.nodeName || form.nodeName.toLowerCase() !== 'form') {
			throw 'You must supply a form element';
		}
		for (i = 0, len = form.elements.length; i < len; i++) {
			formElement = form.elements[i];
			if (formElement.name === '' || formElement.disabled) {
				continue;
			}
			switch (formElement.nodeName.toLowerCase()) {
				case 'input':
				switch (formElement.type) {
					case 'text':
					case 'hidden':
					case 'password':
					case 'button': // Not submitted when submitting form manually, though jQuery does serialize this and it can be an HTML4 successful control
					case 'submit':
					addNameValue(formElement.name, formElement.value);
					break;
					case 'checkbox':
					case 'radio':
					if (formElement.checked) {
						addNameValue(formElement.name, formElement.value);
					}
					break;
					case 'file':
					// addNameValue(formElement.name, formElement.value); // Will work and part of HTML4 "successful controls", but not used in jQuery
					break;
					case 'reset':
					break;
				}
				break;
				case 'textarea':
				addNameValue(formElement.name, formElement.value);
				break;
				case 'select':
				switch (formElement.type) {
					case 'select-one':
					addNameValue(formElement.name, formElement.value);
					break;
					case 'select-multiple':
					for (j = 0, jLen = formElement.options.length; j < jLen; j++) {
						if (formElement.options[j].selected) {
							addNameValue(formElement.name, formElement.options[j].value);
						}
					}
					break;
				}
				break;
				case 'button': // jQuery does not submit these, though it is an HTML4 successful control
				switch (formElement.type) {
					case 'reset':
					case 'submit':
					case 'button':
					addNameValue(formElement.name, formElement.value);
					break;
				}
				break;
			}
		}
		return q.join('&');
	}

	/**
	 * parseOptions
	 * Validate and correct options object while connecting up any listeners.
	 *
	 * @scope private
	 * @param options
	 * @return false | valid options object
	 */
	internal.parseOptions = function(options) {

		/**  Defaults parse options. (if something isn't provided)
		 *
		 * - history: track event to history (on by default, set to off when performing back operation)
		 * - parseLinksOnload: Enabled by default. Process pages loaded via PJAX and setup PJAX on any links found.
		 * - smartLoad: Tries to ensure the correct HTML is loaded. If you are certain your back end
		 *		will only return PJAX ready content this can be disabled for a slight performance boost.
		 * - autoAnalytics: Automatically attempt to log events to Google analytics (if tracker is available)
		 * - returnToTop: Scroll user back to top of page, when new page is opened by PJAX
		 * - parseJS: Disabled by default, when enabled PJAX will automatically run returned JavaScript
		 * - parseCSS: Ensabled by default, when enabled PJAX will automatically add stylesheets in loaded pages
		 */
		var defaults = {
			"history": true,
			"parseLinksOnload": true,
			"httpVerb": 'GET',
			"formData": null,
			"smartLoad" : true,
			"autoAnalytics": true,
			"returnToTop": true,
			"parseJS": false,
			"parseCSS": true
		};

		// Ensure a URL and container have been provided.
		if(typeof options.url === 'undefined' || typeof options.container === 'undefined' || options.container === null) {
			console.log("URL and Container must be provided.");
			return false;
		}

		// Check required options are defined, if not, use default
		for(var o in defaults) {
			if(typeof options[o] === 'undefined') options[o] = defaults[o];
		}

		// Check HTTP method is valid
		if(options.httpVerb.indexOf('GET') === -1 && options.httpVerb.indexOf('POST') === -1){
			console.log("Forms methods must be either 'GET' or 'POST'.");
			return false;
		}

		// Ensure history setting is a boolean.
		options.history = (options.history === false) ? false : true;

		// Get container (if its an id, convert it to a DOM node.)
		options.container = internal.get_container_node(options.container);

		// Events
		var events = ['ready', 'beforeSend', 'complete', 'error', 'success'];

		// If everything went okay thus far, connect up listeners
		for(var e in events){
			var evt = events[e];
			if(typeof options[evt] === 'function'){
				internal.addEvent(options.container, evt, options[evt]);
			}
		}

		// Return valid options
		return options;
	};

	/**
	 * get_container_node
	 * Returns container node
	 *
	 * @param container - (string) container ID | container DOM node.
	 * @return container DOM node | false
	 */
	internal.get_container_node = function(container) {
		if(typeof container === 'string') {
			container = document.getElementById(container);
			if(container === null){
				console.log("Could not find container with id:" + container);
				return false;
			}
		}
		return container;
	};

	/**
	 * connect
	 * Attach links to PJAX handlers.
	 * @scope public
	 *
	 * Can be called in 3 ways.
	 * Calling as connect();
	 *		Will look for links with the data-pjax attribute.
	 *
	 * Calling as connect(container_id)
	 *		Will try to attach to all links, using the container_id as the target.
	 *
	 * Calling as connect(container_id, class_name)
	 *		Will try to attach any links with the given class name, using container_id as the target.
	 *
	 * Calling as connect({
	 *						'url':'somepage.php',
	 *						'container':'somecontainer',
	 *						'beforeSend': function(){console.log("sending");}
	 *					})
	 *		Will use the provided JSON to configure the script in full (including callbacks)
	 */
	this.connect = function(/* options */) {
		// connect();
		var options = {};
		// connect(container, class_to_apply_to)
		if(arguments.length === 2){
			options.container = arguments[0];
			options.useClass = arguments[1];
		}
		// Either JSON or container id
		if(arguments.length === 1){
			if(typeof arguments[0] === 'string' ) {
				//connect(container_id)
				options.container = arguments[0];
			}else{
				//Else connect({url:'', container: ''});
				options = arguments[0];
			}
		}
		// Delete history and title if provided. These options should only be provided via invoke();
		delete options.title;
		delete options.history;

		internal.options = options;
		if(document.readyState === 'complete') {
			internal.parseLinks(document, options);
		} else {
			//Don't run until the window is ready.
			internal.addEvent(window, 'load', function(){
				//Parse links using specified options
				internal.parseLinks(document, options);
			});
		}
	};

	/**
	 * invoke
	 * Directly invoke a pjax page load.
	 * invoke({url: 'file.php', 'container':'content'});
	 *
	 * @scope public
	 * @param options
	 */
	this.invoke = function(/* options */) {

		var options = {};
		// url, container
		if(arguments.length === 2){
			options.url = arguments[0];
			options.container = arguments[1];
		}else{
			options = arguments[0];
		}

		// Process options
		options = internal.parseOptions(options);
		// If everything went okay, activate pjax.
		if(options !== false) internal.handle(options);
	};

	// Make object usable
	var pjax_obj = this;
	if (typeof define === 'function' && define.amd) {
		// Register pjax as AMD module
		define( function() {
			return pjax_obj;
		});
	}else{
		// Make PJAX object accessible in global name space
		window.pjax = pjax_obj;
	}


}).call({});
