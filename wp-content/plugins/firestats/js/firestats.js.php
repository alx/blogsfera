<?php
if (isset($_REQUEST['test']))
{
	// for test just return an empty page.
	// this is used by the system test page to detect 
	// that no crap is injected into the javascript page
	return;
}

@header('Content-type: text/javascript; charset=utf-8');
// it is important that this is included before any character is outputed
// since it may include wordpress config file which sets some http headers.
require_once(dirname(__FILE__).'/../php/init.php');
//global $FS_SESSION_ERROR;
//if (!empty($FS_SESSION_ERROR))
//{
//	echo "alert('JS: Failed to start session: $FS_SESSION_ERROR')";
//}
?>

// FireStats namespace.
// todo: move all the other functions and globals into it.
FS = {}
FS.loadCSS = function(url)
{
	var st = document.createElement("link");
	st.href = url;
	st.rel = "stylesheet";
	st.type = "text/css"; 
	var head = document.getElementsByTagName('head')[0];
	head.appendChild(st);
}

FS.loadJavaScript = function(url)
{
	var st = document.createElement("script");
	st.src = url;
	st.type = "text/javascript"; 
	var head = document.getElementsByTagName('head')[0];
	head.appendChild(st);
}


FS.archivingOldData = false;
FS.archiveOldData = function()
{
	FS.archivingOldData = true;
	if ($('fs_archive_button')) 
	{
		$('fs_archive_button').innerHTML = '<?php fs_e('Stop')?>';
		$('fs_archive_status').innerHTML = '<?php fs_e('Compacting...')?>';
		$('archive_method').disabled = true;
		$('archive_older_than').disabled = true;
	}
	sendSilentRequest('action=archiveOldData&max_days_to_archive=1',FS.archiveCallback);
}

FS.archiveCleanup = function()
{
	if (FS.archivingOldData)
	{
		FS.archivingOldData = false;
		if ($('fs_archive_button')) $('fs_archive_button').innerHTML = '<?php fs_e('Stopping...')?>';
		sendSilentRequest('action=updateFields&update=fs_archive_status',FS.archiveDoneCallback);
	}
}

FS.archiveDoneCallback = function(response)
{
	if ($('archive_method')) 
	{
		$('archive_method').disabled = false;
		$('archive_older_than').disabled = false;
		$('fs_archive_button').innerHTML = '<?php fs_e('Compact now')?>';
	}
}

FS.archiveCallback = function(response)
{
	if (response.status == 'ok')
	{
		if (FS.archivingOldData)
		{
			if (response.done)
			{
				FS.archiveCleanup();
			}
			else
			{
				if (!FS.archivingOldData) // if user canceled 
				{
					response.cancel = true;
					FS.archiveCleanup();
				}
				else
				{
					if ($('fs_archive_button')) $('fs_archive_button').innerHTML = '<?php fs_e('Stop')?>';
				}
			}
		}
		else
		{
			response.cancel = true;
			FS.archiveCleanup();
		}
	}
	else
	{
		FS.archiveCleanup();
	}
}

FS.operations = new Array();
FS.executeProcess = function(type)
{
	if (FS.operations[type] == null)
	{
		FS.operations[type] = true;
		if ($(type+'_button')) $(type+'_button').innerHTML = "<?php fs_e('Abort')?>";
		
	    var params = 'action=incrementalProcess&type='+type;
	    sendSilentRequest(params, function(response)
	    {
	    	if (response.status == 'ok')
	    	{
		    	var type = response['type'];
		    	if (type == null) alert("Type not specified in callback");
	    		var progress = $(type+"_process_progress");
				var progress_text = response['progress_text'];
				
		    	if (FS.operations[type] == null)
		    	{
		    		response.cancel = true;
		    		if ($(type+'_button')) $(type+'_button').innerHTML = "<?php fs_e('Start')?>";
		    		if (progress) progress.innerHTML = "<?php fs_e("Canceled")?>";
		    	}
		    	else
		    	{
		    		if ($(type+'_button')) $(type+'_button').innerHTML = "<?php fs_e('Abort')?>";
		    		if (progress) progress.innerHTML = progress_text;
		    	}

		    	
				if (response.done == 'true')
				{
					if ($(type+'_button')) $(type+'_button').innerHTML= "<?php fs_e('Start')?>";
					FS.operations[type] = null;
				}
	    	}
	    });
	}
	else
	{
		if ($(type+'_button')) $(type+'_button').innerHTML = "<?php fs_e('Start')?>";
		FS.operations[type] = null;
	}
}


function getWindowSize() 
{
	var myWidth = 0, myHeight = 0;
	if( typeof( window.innerWidth ) == 'number' ) 
	{
		//Non-IE
		myWidth = window.innerWidth;
		myHeight = window.innerHeight;
	} 
	else 
	if(document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) 
	{
		//IE 6+ in 'standards compliant mode'
		myWidth = document.documentElement.clientWidth;
		myHeight = document.documentElement.clientHeight;
	} 
	else
	if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) 
	{
		//IE 4 compatible
		myWidth = document.body.clientWidth;
		myHeight = document.body.clientHeight;
	}
	var res = new Object();
	res.width = myWidth;
	res.height = myHeight;
	return res;
}

function createWindowUrl(width,height,left,top, url)
{
	var myAjax = new Ajax.Request(
	url,
	{
		method: 'get', 
		onComplete: function(response)
		{
			createWindow(width,height,left,top,response.responseText);
		}	
	});
}

function createWindow(width,height,left,top, content)
{
	var size = getWindowSize();
	if (left == "center")
	{
		left = (size.width - width) / 2;
	}
	if (top == "center")
	{
		top = (size.height - height) / 2;
	}
	var divId = createNewWindow(width,height,left,top);
	document.getElementById('windowContent' + divId).innerHTML = content;
}

function openWindow(page, width, height)
{
	window.open (page, 'newwindow',"height="+height+",width="+width+",toolbar=no,menubar=no,location=no, directories=no, status=no,resizable=yes,scrollbars=yes");
}

function toggle_div_visibility(id)
{
	var disp = $(id).style.display;
	if (disp == "inline")
	{
		$(id).style.display = "none";
	}
	else
	{
		$(id).style.display = "inline";
	}
}


function hideFeedback()
{
	var e = document.getElementById("feedback_div").style.display = "none";
}

var messageTimerID;
function showFeedback(response, timeout)
{
	var e = $("feedback_zone");
	if (!e) return; // for dialogs etc
	if (!response.message) return;
	e.innerHTML = response.message;

	e = $("feedback_div");
	if (response.status == 'error')
	{
		e.style.background = '#f86262';
	}
	else
	{
		e.style.background = '#3aa9ff';
	}

	e.style.display = "block";
	
	if (timeout != null)
	{
		clearTimeout(messageTimerID);
		messageTimerID = setTimeout("hideFeedback()", timeout);
	}
}

function clearOptions(idlist,save,update)
{
	var a = idlist.split(',');
	a.each(function(item) 
	{
		var x = $(item);
		if(x.tagName.toLowerCase() == 'input')
		{
			x.value="";
		}
		else
		{
			x.innerHTML="";
		}
	});

	if(save == true) 
	{
		saveOptions(idlist,update);
	}
}

function saveOptions(idlist,update)
{
	saveOptions_imp(idlist,"firestats",update);
}

function saveLocalOptions(idlist,update)
{
	saveOptions_imp(idlist,"local",update);
}

function saveOptions_imp(idlist,dest,update)
{
	// creates a list in the format list=key1,val1;key2,val2
	var a = idlist.split(',');
	var list = '';
	var missing = '';
	a.each(function(item) 
	{
		if ($(item))
		{
			list += encodeURIComponent(item) + "," + encodeURIComponent($F(item)) + ";";
		}
		else
		{
			if (missing == '') missing += item;
			else missing += "," + item;
		}
	});

	if (missing != '') alert("saveOptions: missing element(s): " + missing);

	var params = 'action=' + 'saveOptions' + '&list=' + encodeURIComponent(list) + 
				 (update != null ? "&update="+encodeURIComponent(update) : "") +
				 "&dest=" + encodeURIComponent(dest);
	sendRequest(params);
}

function saveOptionValue(name, value, type)
{
	saveOptionImpl(name,value,type,null,"firestats");
}

function saveOption(inputID, optionName, type)
{
	saveOptionImpl(optionName,$F(inputID), type,null,"firestats");
}

function saveOption(inputID, optionName, type, update)
{
	saveOptionImpl(optionName, $F(inputID), type, update, "firestats");
}

function saveLocalOption(inputID, optionName, type, update)
{
	saveOptionImpl(optionName, $F(inputID),type, update, "local");
}

function saveSystemOptionValue(name, value, type)
{
	saveOptionImpl(name,value,type,null,"system");
}

function saveSystemOption(inputID, optionName, type, update)
{
	saveOptionImpl(optionName, $F(inputID), type, update, "system");
}

function saveOptionImpl(optionName, txt, type, update, dest)
{
	var output = txt;
	var parsed = true;
	switch (type)
	{
	case 'positive_num':
		var n = parseInt(txt);
		parsed = n && n >= 0;
		if (!parsed)
		{
			showError("<?php print fs_r("Not a positive number : ") ?>" + txt);
		}
		break;
	case 'boolean':
		output = (txt == 'on' || txt == 'yes' || txt == 'true') ? 'true' : 'false';
		break;
	case 'string':
		break;
	default:
		showError('JS:unsupported type ' + type);
		return;
	}

	if (parsed)
	{
		var params = 'action=' + 'saveOption' + '&key=' + encodeURIComponent(optionName) + 
					 "&value=" + encodeURIComponent(output) +
					 (update != null ? "&update="+encodeURIComponent(update) : "") +
					 "&dest=" + encodeURIComponent(dest);
		sendRequest(params);
	}
}

function showMessage(msg)
{
	try
	{	
		var x = {};
		x['status'] = 'ok';
		x['message'] = msg;
		showFeedback(x);
	}
	catch (e2)	
	{	
		// if even this failed, use alert.
		alert('error : ' + errorMessage);
	}
}

function showError(errorMessage)
{
	try
	{	
		var x = {};
		x['status'] = 'error';
		x['message'] = errorMessage;
		clearTimeout(messageTimerID);
		showFeedback(x);
	}
	catch (e2)	
	{	
		// if even this failed, use alert.
		alert('error : ' + errorMessage);
	}
}

function sendSilentRequest(params,callback)
{
	sendRequest2(params,true,callback);
}

function sendRequest(params,callback)
{
	sendRequest2(params,false,callback);
}

var fs_network_status_count = 0;
function sendRequest2(params,silent,callback)
{
	if (!isIE6OrOlder())
	{
		if (!silent)
		{
			fs_network_status_count++;
			var net = $('network_status');
			if (net) net.style.display = "block";
		}
	}
	params += ("&sid=<?php echo fs_get_session_id()?>");
	var ajaxUrl = "<?php echo fs_js_url('php/ajax-handler.php')?>";
	var myAjax = new Ajax.Request(
	ajaxUrl,
	{
		method: 'post', 
		parameters: params, 
		onComplete: function(response)
		{
			handleResponse(response,silent,callback);
			if (!isIE6OrOlder())
			{
				if (!silent)
				{
					fs_network_status_count--;
					if (fs_network_status_count == 0)
					{
						var net = $('network_status');
						if (net) net.style.display = "none";
					}
				}
			}
		}	
	});
}


function stripslashes(value)
{
	str = "" + value;
	str = str.replace(/\\"/g,'"' );
	str = str.replace(/\\\'/g,'\'' );
	return str;
}


function trapEnter(e, enterFunction)
{
	if (!e) e = window.event;
	if (e.keyCode == 13)
	{
		e.cancelBubble = true;
		if (e.returnValue) e.returnValue = false;
		if (e.stopPropagation) e.stopPropagation();
		if (enterFunction) eval(enterFunction);
		return false;
	} 
	else 
	{

		return true;
	}     
}


function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;
	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";

	if(typeof(arr) == 'object') { //Array/Hashes/Objects
		for(var item in arr) {
			var value = arr[item];

			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}


function validateIP(what) 
{
  if (what.search(/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/) != -1) 
	{
	  var myArray = what.split(/\./);
    if (myArray[0] > 255 || myArray[1] > 255 || myArray[2] > 255 || myArray[3] > 255)
  		return false;
		if (myArray[0] == 0 && myArray[1] == 0 && myArray[2] == 0 && myArray[3] == 0)
    	return false;
    return true;
  }
  else
		return false;
}

function isIE6OrOlder()
{
    return isIEXOrOlder(6);
}

function isIEXOrOlder(x)
{
	var ua = navigator.userAgent;
	var i = ua.indexOf("MSIE");
	if (i != -1)
	{
		var ver = parseFloat(ua.substring(i + 5, i + 8));
        return ver <= x;
	}
    return false;
}


function applyResponse(data)
{
	applyResponse2(data,false);
}

function applyResponse2(data,silent)
{
	var fields = data['fields'];
	if (fields)
	{
		for (var key in fields) 
		{
			try
			{	
				var txt =  stripslashes(fields[key]);
				var e = $(key);
				if(e)
				{
					if (data['type'] && data['type'][key] == 'tree') 
					{
						replaceTree(key, txt);
					}
					else
					if(e.tagName.toLowerCase() == 'input')
					{
						e.value=txt;
					}
					else
					{
						e.innerHTML=txt;
					}
				}
				else
				{
					if (!silent) alert('Element not found: ' + key);
				}
			}
			catch(e)
			{
				alert(dump(e));	
			}
		}
	}
	
	var styles = data['styles'];
	for (var key in styles) 
	{
		var style  = styles[key];
	
		for (var prop in style) 
		{
			try
			{
				var e = $(key);
				if(e) 
				{	
					e.style[prop]=style[prop];
				}
				else
				{
					if (!silent) alert('Element not found: ' + key);
				}
			}
			catch(ex)
			{
				alert(ex);
			}
		}
	}

}

var disableResponses = false;
function handleResponse(response,silent,callback)
{
	if (disableResponses) return;
	try
	{
		eval("var r = " + response.responseText);
	}
	catch (e)
	{
		showError("error evaluating response : Response text:<br/>" + response.responseText);
		var r = new Object();
		r.message = 'Error evaluating response';
		r.status = 'error';
		if (typeof callback == 'function') callback(r);
		return;
	}

	try
	{
		if (r.status == 'error')
		{	
			showError(r.message);
			if (typeof callback == 'function') 
			{
				callback(r);
			}
		}
		else if (r.status == 'ok')
		{
			//alert(dump(r));
			if (typeof callback == 'function') 
			{
				callback(r);
				// check if callback canceled the response.
				if (r.cancel) return; 
			}
			
			switch (r.action)
			{
			case 'createNewDatabase':
			case 'upgradeDatabase':
			case 'attachToDatabase':
			case 'installDBTables':
				if (r.db_status == 'ok')
				{
					window.location.reload(); // no ideal, but it will have to do for now.
				}
				else
				{
					showFeedback(r,5000);
					applyResponse2(r,silent);
				}
			break;
			default:
				showFeedback(r,5000);
				applyResponse2(r,silent);
			}

			if (r.cookies != null && r.cookies.length > 0)
			{
				for(var i=0;i<r.cookies.length;i++)
				{
					var c = r.cookies[i];
					createCookie(c.name, c.value, c.days);
				}
			}
			
			if (r.refresh == 'true')
			{
				window.location.reload();
			}
			
			if (r.redirect)
			{
				window.location = r.redirect;
			}			
			
			if (r.send_request)
			{
				sendRequest2(r.send_request, silent, callback);
			}
			
			if (r.new_floating_window)
			{
				if (r.content != undefined)
				{
					createWindow(r.width,r.height,r.left,r.top,r.content);
				}
				else
				if (r.url != undefined)
				{
					createWindowUrl(r.width,r.height,r.left,r.top,r.url);
				}
			}
			
			if (r.execute)
			{
				var exec = r.execute;
				try
				{
					eval(exec);
				}
				catch(e)
				{
					showError('Error executing ' + exec);
				}
			}
			
		}
		else if (r.status == 'session_expired')
		{
			disableResponses = true; // ignore futher responses
			alert("<?php fs_e('Session expired, press ok to reload')?>");
			window.location.reload();
		}
		else
		{
			showError('Unknown response type ' + r.status);
		}
	}
	catch (e)
	{
		showError('error processing response : ' + dump(e));	
	}
}

function replaceTree(id,tree)
{
	var str = stripslashes(tree);
	var tree = convertTreeString(str);
	var treeDiv = $(id);
	treeDiv.replaceChild(tree, treeDiv.getElementsByTagName('div')[0]);
}

// selects the select item with the speficied text
function selectByText(selectId, text)
{
	var children = $(selectId).childNodes;
	for (var i = 0; i < children.length; i++)
	{
		var child = children[i];
		if (child.text == text)
		{
			$(selectId).selectedIndex = i;
			break;
		}
	}
}

/**
 * cookie scripts from http://www.quirksmode.org/js/cookies.html
 */
function createCookie(name,value,days) 
{
	if (days) 
	{
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else 
		var expires = "";
	document.cookie = name+"="+value+expires;//+"; path=/"; //
}

function readCookie(name) 
{
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) 
	{
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) 
{
	createCookie(name,"",-1);
}

function updateAllStats()
{
	sendRequest('action=updateFields&update=stats_total_count,stats_total_unique,stats_total_count_last_day,stats_total_unique_last_day;fs_recent_referers,fs_search_terms,popular_pages;countries_list,fs_browsers_tree,fs_os_tree;records_table');
}

// add some functions to string.
String.prototype.trim = function() 
{
	return this.replace(/^\s+|\s+$/g,"");
}
String.prototype.ltrim = function() 
{
	return this.replace(/^\s+/,"");
}
String.prototype.rtrim = function() 
{
	return this.replace(/\s+$/,"");
}	


FS.openDonationWindow = function()
{
	createWindowUrl(400,370,'center','center','<?php echo fs_js_url("php/window-donation.php")?>');
}