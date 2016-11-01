/*
# $Id$
*/

/*
# @namespace BuanCore
*/
var BuanCore = {

	/*
	# @function XMLHttpRequest getAjaxConnection()
	#
	# Returns a valid XML requestor object.
	*/
	getAjaxConnection: function() {

		var connection;
		if(window.XMLHttpRequest && !(window.ActiveXObject)) {
			try {
				connection = new XMLHttpRequest();
			}
			catch(e) {
				connection = false;
			}
		}
		else if(window.ActiveXObject) {
			try {
				connection = new ActiveXObject("Msxml2.XMLHTTP");
			}
			catch(e) {
				try {
					connection = new ActiveXObject("Microsoft.XMLHTTP");
				}
				catch(e) {
					connection = false;
				}
			}
		}
		return connection;
	},

	/*
	# @namespace BuanCore.Util
	*/
	Util: {

		/*
		# @function void linkCss( string src )
		# src	= URL to CSS source file
		#
		# Inserts the CSS source file into the document.
		*/
		linkCss: function(src) {
			var el = document.createElement('link');
			el.href = src;
			el.type = 'text/css';
			el.rel = 'stylesheet';
			document.documentElement.insertBefore(el, document.documentElement.lastChild);
		}
	}
};