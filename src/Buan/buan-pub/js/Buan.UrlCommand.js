/*
# $Id$
#
# DO NOT INCLUDE THIS FILE DIRECTLY IN YOUR WEB PAGES, INSTEAD USE "Buan.js.php".
*/

/*
# @namespace Buan.UrlCommand
*/
if(!self.Buan) {
	var Buan = {};
}
Buan.UrlCommand = {};

/*
# @function string Buan.UrlCommand.createUrl( string controller, [string action, [array|object|string param1, array|object|string param2, ...]] )
# controller	= Controller
# action		= Action
# paramX		= Action parameters
#
# Create URL to access specified controller/action.
# If any of the paramX variables are an object, then the key=>value pairs in that
# object will be used to populate the query element of the generated URL (ie.
# everything after the "?")
*/
Buan.UrlCommand.createUrl = function(controller, action) {

	// Vars
	var params = [];
	var query = [];
	var undefined;
	if(arguments.length>2) {
		for(var i=2; i<arguments.length; i++) {
			if(arguments[i].push) {
				for(var j=0; j<arguments[i].length; j++) {
					if(typeof arguments[i][j]=='object') {
						for(var k in arguments[i][j]) {
							query.push(k+(arguments[i][j][k]===null ? '' : '='+encodeURIComponent(arguments[i][j][k])));
						}
					}
					else {
						params.push(arguments[i][j]);
					}
				}
			}
			else if(typeof arguments[i]=='object') {
				for(var j in arguments[i]) {
					query.push(arguments[i][j]===null || arguments[i][j]===undefined ? j : (arguments[i][j].push ? j+'[]='+arguments[i][j].join('&'+j+'[]=') : j+'='+encodeURIComponent(arguments[i][j])));
				}
			}
			else {
				params.push(arguments[i]);
			}
		}
	}
	if(action==null) {
		var paramCount = 0;
		for(var i=2; i<arguments.length; i++) {
			if(typeof arguments[i]!='object') {
				paramCount++;
			}
		}
		action = paramCount>0 ? 'index' : '';
	}

	// Generate URL
	var urlPrefix = Buan.Config.get('app.command.urlPrefix');
	var url = Buan.Config.get('app.urlRoot')+'/'+(urlPrefix=='' ? '' : urlPrefix+'/')+controller+'/'+action;
	for(var i=0; i<params.length; i++) {
		//url += '/'+escape(params[i]);
		url += '/'+encodeURIComponent(params[i]);
	}

	// Result
	return url+(query.length>0 ? '?'+query.join('&') : '')
}

/*
# @function string Buan.UrlCommand.createAbsoluteUrl( string controller, [string action, [string|array params]] )
# controller	= Controller
# action		= Action
# params		= Action parameters
#
# Create absolute URL to the specified controller/action.
*/
Buan.UrlCommand.createAbsoluteUrl = function(controller, action) {

	// Result
	var params = [];
	for(var i=2; i<arguments.length; i++) {
		params[params.length] = arguments[i];
	}
	return "http://"+Buan.Config.get('app.domain')+Buan.UrlCommand.createUrl(controller, action, params);
}