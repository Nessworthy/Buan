/*
# $Id$
*/

/*
# @function void callExtensionMethod( string extName, string method )
# extName	= Extension name
# method	= Class method to be called
#
# Initiates an AJAX call that will call the specified class method.
# TODO: Replace the JSON stuff with normal XML tree parsing because JSON interferes with other third-party scripts - notably prototype.js
*/
function callExtensionMethod(extName, method) {

	// Vars
	var url = self.location.pathname+'/'+extName+'/'+method;
	var extBtn = document.getElementById('fe_extBtn_'+extName+'_'+method);
	var extBtnContainer = document.getElementById('fe_extBtnContainer_'+extName);
	//var btnUninstall = document.getElementById('fe_btnUninstall_'+extName);

	// Prepare and send request
	var conn = BuanCore.getAjaxConnection();
	conn.open("GET", url, true);
	conn.onreadystatechange = function() {

		// Ignore any state other than 4
		if(conn.readyState!=4) {
			return;
		}

		// Act on result
		var result = conn.responseXML.getElementsByTagName('result')[0];
		var resultCode = parseInt(result.getAttribute('code'));
		var message = result.firstChild.lastChild.nodeValue;
		var returnValue = result.lastChild.lastChild.nodeValue;
		if(resultCode==1) {
			var logEntries = returnValue.toString().parseJSON();
			for(var i=0; i<logEntries.length; i++) {
				message += "\n\n"+logEntries[i].typeString.toUpperCase()+":\n"+logEntries[i].message;
			}
			alert(message);
		}
		extBtnContainer.innerHTML = '';

		// Refresh button list
		getButtonMap(extName);
	}
	extBtn.disabled = true;
	extBtn.value = 'Please wait ...';
	conn.send(null);
}

/*
# @function void getButtonMap( string extName )
# extname	= Extension name
#
# Retrieves a list of buttons for the specified Extension, and draws them to the interface.
*/
function getButtonMap(extName) {

	// Vars
	var url = self.location.pathname+'/'+extName+'/getManagerButtonMap';
	var extBtnContainer = document.getElementById('fe_extBtnContainer_'+extName);

	// Prepare and send request
	var conn = BuanCore.getAjaxConnection();
	conn.open("GET", url, true);
	conn.onreadystatechange = function() {

		// Ignore any state other than 4
		if(conn.readyState!=4) {
			return;
		}

		// Act on result
		var result = conn.responseXML.getElementsByTagName('result')[0];
		var resultCode = parseInt(result.getAttribute('code'));
		var message = result.firstChild.lastChild.nodeValue;
		var returnValue = result.lastChild.lastChild.nodeValue.toString().parseJSON();
		var buttonMap = [];
		if(resultCode==0) {
			buttonMap = returnValue;
		}
		else {
			buttonMap = [];
			alert(message);
		}

		// Draw buttons to the container
		extBtnContainer.innerHTML = '';
		for(var i=0; i<buttonMap.length; i++) {
			var btn = buttonMap[i];
			var onclick = btn.onclick ? btn.onclick : 'callExtensionMethod(\''+extName+'\', \''+btn.method+'\');';
			extBtnContainer.innerHTML += '<input type="button" id="fe_extBtn_'+extName+'_'+btn.method+'" value="'+btn.label+'" onclick="'+onclick+'" /> ';
		}
	}
	extBtnContainer.innerHTML = 'Refreshing buttons, please wait ...';
	conn.send(null);
}