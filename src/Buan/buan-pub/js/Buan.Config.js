/*
# $Id$
#
# DO NOT INCLUDE THIS FILE DIRECTLY IN YOUR WEB PAGES, INSTEAD USE "Buan.js.php".
*/

/*
# @namespace Buan.Config
*/
if(!self.Buan) {
	var Buan = {};
}
Buan.Config = {};

/*
# @variable object Buan.Config.vars
# Stores configuration variables
*/
Buan.Config.vars = {};

/*
# @variable object Buan.Config.cachedVars
# Stores cached configuration variables
*/
Buan.Config.cachedVars = {};

/*
# @function mixed Buan.Config.get( [string varPath] )
# varPath	= Namespace path (same as Buan Config::get())
#
# Returns the requested config variable.
*/
Buan.Config.get = function(varPath) {

	// Return all settings
	if(arguments.length==0) {
		return Buan.Config.vars;
	}

	// First, check if the path has been retrieved at some point previously
	if(Buan.Config.cachedVars[varPath]) {
		return Buan.Config.cachedVars[varPath];
	}

	// Find the setting pointed to by varPath
	try {
		var cVar;
		eval("cVar = Buan.Config.vars['"+varPath.split(".").join("']['")+"'];");
		Buan.Config.cachedVars[varPath] = cVar;
	}
	catch(e) {
		// Load config var from server
		alert("Buan configuration variable '"+varPath+"' is undefined");
		cVar = null;
	}
	return cVar;
}

/*
# @function mixed Buan.Config.set( string varPath, mixed varValue )
# varPath	= Namespace path (same as Buan Config::set())
# varValue	= Value to store
#
# Stores the specified config variable.
*/
Buan.Config.set = function(varPath, varValue) {

	// Handle objects passed to varValue
	if(typeof varValue=='object') {
		for(var i in varValue) {
			Buan.Config.set(varPath+'.'+i, varValue[i]);
		}
		return;
	}

	// Create each element in the specified path, if required
	var pathElements = varPath.split(".");
	var path = '';
	var p = '';
	while(p = pathElements.shift()) {
		path += p;
		eval("if(Buan.Config.vars['"+path.split(".").join("']['")+"']==null) {\
					Buan.Config.vars['"+path.split(".").join("']['")+"'] = "+(pathElements.length==0 ? '""' : '{}')+";\
				}");
		path += '.';
	}
	path = path.replace(/\.$/, '');

	// Store the value in the path
	eval("Buan.Config.vars['"+path.split(".").join("']['")+"'] = '"+varValue.replace(/'/, "\'")+"';");
}