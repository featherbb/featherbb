/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

function select_checkboxes(curFormId, link, new_string) {
	var curForm = document.getElementById(curFormId);
	var inputlist = curForm.getElementsByTagName("input");
	for (i = 0; i < inputlist.length; i++) {
		if (inputlist[i].getAttribute("type") == 'checkbox' && inputlist[i].disabled == false)
			inputlist[i].checked = true;
	}

	link.setAttribute('onclick', 'return unselect_checkboxes(\'' + curFormId + '\', this, \'' + link.innerHTML + '\')');
	link.innerHTML = new_string;

	return false;
}

function unselect_checkboxes(curFormId, link, new_string) {
	var curForm = document.getElementById(curFormId);
	var inputlist = curForm.getElementsByTagName("input");
	for (i = 0; i < inputlist.length; i++) {
		if (inputlist[i].getAttribute("type") == 'checkbox' && inputlist[i].disabled == false)
			inputlist[i].checked = false;
	}

	link.setAttribute('onclick', 'return select_checkboxes(\'' + curFormId + '\', this, \'' + link.innerHTML + '\')');
	link.innerHTML = new_string;

	return false;
}

<<<<<<< HEAD
=======
// Display message box so that it is always visible
function isElementInViewport(el) {

	//special bonus for those using jQuery
	if (typeof jQuery === "function" && el instanceof jQuery) {
		el = el[0];
	}

	var rect = el.getBoundingClientRect();

	return (
		rect.top >= 0 &&
		rect.left >= 0 &&
		rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && /*or $(window).height() */
		rect.right <= (window.innerWidth || document.documentElement.clientWidth) /*or $(window).width() */
	);
}

function onVisibilityChange(el, callback) {
	function hasClass(element, cls) {
		return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
	}
	return function() {
		// if (!isElementInViewport(el) && !hasClass(el, 'sticky')) {
		// 	el.className = 'block sticky';
		// } else {
		// 	el.className = 'block';
		// }
		console.log('visibility ' + isElementInViewport(el));

	}
}

>>>>>>> featherbb/new-db-layer
function fadeOut(id, val) {
	if (isNaN(val)) {
		val = 9;
	}
	document.getElementById(id).style.opacity = '0.' + val;
	//For IE
	document.getElementById(id).style.filter = 'alpha(opacity=' + val + '0)';
	if (val > 0) {
		val--;
		setTimeout('fadeOut("' + id + '",' + val + ')', 120);
	} else {
		return;
	}
}

function fadeIn(id, val) {
	if (isNaN(val)) { val = 0; }
	document.getElementById(id).style.opacity = '0.' + val;
	//For IE
	document.getElementById(id).style.filter = 'alpha(opacity=' + val + '0)';

	if (val < 9) {
		val++;
		setTimeout('fadeIn("' + id + '",' + val + ')', 120);
	} else {
		return;
	}
}
