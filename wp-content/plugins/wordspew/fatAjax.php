<?php
// Why is this file php? so that wordpress can find its url and print it for the ajax...this saves
// some setup time in if you have a blog not in the root
// The headers below cache the file and make it javascript
 	require('../../../wp-config.php');
	header("Cache-Control: must-revalidate");
	$offset = 60*60*24*30;
	$ExpStr = "Expires: ".gmdate("D, d M Y H:i:s",time() + $offset)." GMT";
	header($ExpStr);
	header('Content-Type: application/x-javascript; charset='.get_option('blog_charset'));
	define('wordspew', 'wordspew/wordspew');
	if(function_exists('load_plugin_textdomain')) load_plugin_textdomain(wordspew);
	$major_version = explode("-", $wp_version);
	$pathtoSmiley = (floatval($major_version[0]) > '1.5') ? "/wp-includes/images/smilies/" : "/wp-images/smilies/";
	$XHTML=get_option('shoutbox_XHTML');
	$GetSmiley=get_option('shoutbox_Smiley');
?>
// This script file contains 2 major sections, one for the AJAX chat, and one for the FAT
// technique. The AJAX chat script part is below the FAT part

// @name      The Fade Anything Technique
// @namespace http://www.axentric.com/aside/fat/
// @version   1.0-RC1
// @author    Adam Michela

var Fat = {
	make_hex : function (r,g,b) 
	{
		r = r.toString(16); if (r.length == 1) r = '0' + r;
		g = g.toString(16); if (g.length == 1) g = '0' + g;
		b = b.toString(16); if (b.length == 1) b = '0' + b;
		return "#" + r + g + b;
	},
	fade_all : function ()
	{
		var a = document.getElementsByTagName("*");
		for (var i = 0; i < a.length; i++) 
		{
			var o = a[i];
			var r = /fade-?(\w{3,6})?/.exec(o.className);
			if (r)
			{
				if (!r[1]) r[1] = "";
				if (o.id) Fat.fade_element(o.id,null,null,"#"+r[1]);
			}
		}
	},
	fade_element : function (id, fps, duration, from, to) 
	{
		if (!fps) fps = 30;
		if (!duration) duration = 3000;
		if (!from || from=="#") from = "#FFFF33";
		if (!to) to = this.get_bgcolor(id);
		
		var frames = Math.round(fps * (duration / 1000));
		var interval = duration / frames;
		var delay = interval;
		var frame = 0;
		
		if (from.length < 7) from += from.substr(1,3);
		if (to.length < 7) to += to.substr(1,3);
		
		var rf = parseInt(from.substr(1,2),16);
		var gf = parseInt(from.substr(3,2),16);
		var bf = parseInt(from.substr(5,2),16);
		var rt = parseInt(to.substr(1,2),16);
		var gt = parseInt(to.substr(3,2),16);
		var bt = parseInt(to.substr(5,2),16);
		
		var r,g,b,h;
		while (frame < frames)
		{
			r = Math.floor(rf * ((frames-frame)/frames) + rt * (frame/frames));
			g = Math.floor(gf * ((frames-frame)/frames) + gt * (frame/frames));
			b = Math.floor(bf * ((frames-frame)/frames) + bt * (frame/frames));
			h = this.make_hex(r,g,b);
		
			setTimeout("Fat.set_bgcolor('"+id+"','"+h+"')", delay);

			frame++;
			delay = interval * frame; 
		}
		setTimeout("Fat.set_bgcolor('"+id+"','"+to+"')", delay);
	},
	set_bgcolor : function (id, c)
	{
		var o = document.getElementById(id);
		o.style.backgroundColor = c;
	},
	get_bgcolor : function (id)
	{
		var o = document.getElementById(id);
		while(o)
		{
			var c;
			if (window.getComputedStyle) c = window.getComputedStyle(o,null).getPropertyValue("background-color");
			if (o.currentStyle) c = o.currentStyle.backgroundColor;
			if ((c != "" && c != "transparent") || o.tagName == "BODY") { break; }
			o = o.parentNode;
		}
		if (c == undefined || c == "" || c == "transparent") c = "#FFFFFF";
		var rgb = c.match(/rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)/);
		if (rgb) c = this.make_hex(parseInt(rgb[1]),parseInt(rgb[2]),parseInt(rgb[3]));
		return c;
	}
}


function jal_apply_filters(s) {
	return filter_smilies(make_links((s)));
}
var smilies=[<?php
if(is_array($wpsmiliestrans)) {
    // Get smileys information from Wordpress
	natsort($wpsmiliestrans);
    $strFatSmilies = '';
    foreach($wpsmiliestrans as $tag => $file) {
        $strFatSmilies .= "['".trim(str_replace("'","\'",$tag))."', '".trim($file)."'],"."\r\n";
    }
    $strFatSmilies = substr($strFatSmilies, 0, - 3);
    echo $strFatSmilies;
	}
?>
];

function make_links (s) {
	target="";
	<?php if($XHTML==0) echo 'if (s.indexOf(this.location.href)==-1) target=\' target="_blank"\';'; ?>
	var re = /((http|https|ftp):\/\/[^ ]*)/gi;	
	text = s.replace(re,"<a href=\"$1\""+target+">&laquo;<?php _e('link',wordspew);?>&raquo;</a>");
	return text;
}

var PathToSmiley="<?php bloginfo('wpurl'); ?><?php echo $pathtoSmiley; ?>";

function filter_smilies(s) {
	for (var i = 0; i < smilies.length; i++) {
		var replace = '<img src="'+PathToSmiley + smilies[i][1] + '" class="wp-smiley" alt="[smiley]" />';
		var search = smilies[i][0].replace(/(\(|\)|\$|\?|\*|\+|\^|\[|\.|\|)/gi, "\\$1");
		re = new RegExp(search, 'gi');
		s = s.replace(re, replace);
	}
	var re =/([_.0-9a-z-]+@([0-9a-z][0-9a-z-]+.)+(\.[-a-z0-9]+)*\.[a-z]{2,6})/gi;
	s = s.replace(re,"<a href=\"mailto:$1\">&laquo;<?php _e('email',wordspew);?>&raquo;</a>");
	return s;
}

// XHTML live Chat
// author: alexander kohlhofer
// version: 1.0
// http://www.plasticshore.com
// http://www.plasticshore.com/projects/chat/
// please let the author know if you put any of this to use
// XHTML live Chat (including this script) is published under a creative commons license
// license: http://creativecommons.org/licenses/by-nc-sa/2.0/


var jal_loadtimes;
var jal_org_timeout = <?php echo get_option('shoutbox_update_seconds'); ?>;
var jal_timeout = jal_org_timeout;
var GetChaturl = "<?php echo dirname($_SERVER['PHP_SELF']); ?>/wordspew.php?jalGetChat=yes";
var SendChaturl = "<?php echo dirname($_SERVER['PHP_SELF']); ?>/wordspew.php?jalSendChat=yes";
var httpReceiveChat;
var httpSendChat;
var jalSound;
///////////////////////////////////////
//
//  Generic onload by Brothercake
//  http://www.brothercake.com/
//
///////////////////////////////////////

//onload function

//setup onload function
if(typeof window.addEventListener != 'undefined')
{
	//.. gecko, safari, konqueror and standard
	window.addEventListener('load', initJavaScript, false);
}
else if(typeof document.addEventListener != 'undefined')
{
	//.. opera 7
	document.addEventListener('load', initJavaScript, false);
}
else if(typeof window.attachEvent != 'undefined')
{
	//.. win/ie
	window.attachEvent('onload', initJavaScript);
}

function initJavaScript() {
	if (!document.getElementById('chatbarText')) { return; }
	document.forms['chatForm'].elements['chatbarText'].setAttribute('autocomplete','off'); //this non standard attribute prevents firefox' autofill function to clash with this script
	// initiates the two objects for sending and receiving data
	checkStatus(''); //sets the initial value and state of the input comment
	checkName(); //checks the initial value of the input name
	checkUrl();
	jalSound = (jal_getCookie("jalSound")==null || jal_getCookie("jalSound")==1) ? 1 : 0;
	jal_loadtimes = 1;

	httpReceiveChat = getHTTPObject();
	httpSendChat = getHTTPObject();

	setTimeout('receiveChatText()', jal_timeout); //initiates the first data query

	document.getElementById('shoutboxname').onblur = checkName;
	document.getElementById('shoutboxU').onblur = checkUrl;
	document.getElementById('chatbarText').onfocus = function () { checkStatus('active'); }	
	document.getElementById('chatbarText').onblur = function () { checkStatus(''); }
	document.getElementById('submitchat').onclick = sendComment;
	document.getElementById('chatForm').onsubmit = function () { return false; }
	// When user mouses over shoutbox
	document.getElementById('chatoutput').onmouseover = function () {
		if (jal_loadtimes > 9) {
			jal_loadtimes = 1;
			receiveChatText();
		}
		jal_timeout = jal_org_timeout;
	}
	
	var obj = "";
	<?php if($GetSmiley==1) {?>
	ActualSmile="";
	style="";
	lib="-";
	if (jal_getCookie("jalSmiley")==1) {
		style="display:none";
		lib="+"
	}
	obj+="<a href=\"javascript:ShowHide('SmileyParent','SmileyChild')\" id=\"SmileyParent\" title=\"<?php _e('Click here to expand/collapse the smiley\'s list',wordspew);?>\">"+lib+"</a> Smileys :";
	obj+="<div id='SmileyChild' style=\""+style+"\">";
	for (var i = 0; i < smilies.length; i++) {
		if(ActualSmile!=smilies[i][1]) {
			obj+="<a href=\"javascript:appendSmiley('"+smilies[i][0].replace("'","\\'")+"')\">";
			obj+="<img src=\""+PathToSmiley+smilies[i][1]+"\" alt=\"\" class=\"wp-smiley\"/></a> ";
		}
		ActualSmile=smilies[i][1];
	}
	obj+="</div>"	
	document.getElementById("SmileyList").innerHTML=obj;
	<?php };?>
}
function appendSmiley(text) {
	document.getElementById('chatbarText').value+=' '+text;
	document.getElementById('chatbarText').focus();
}
	
function ShowHide(parent, enfant) {
	txtParent=document.getElementById(parent).innerHTML;
	etatEnfant=document.getElementById(enfant).style.display;
	document.getElementById(parent).innerHTML=(txtParent=="+") ? "-" : "+";
	document.getElementById(enfant).style.display=(etatEnfant=="none") ? "" : "none";
	jalSmiley = (jal_getCookie("jalSmiley")==1) ? 0 : 1;
	document.cookie = "jalSmiley="+jalSmiley+";expires=<?php echo gmdate("D, d M Y H:i:s",time() + $offset)." UTC"; ?>;path=/;";
}

//initiates the first data query
function receiveChatText() {
	jal_lastID = parseInt(document.getElementById('jal_lastID').value) - 1;
	if (httpReceiveChat.readyState == 4 || httpReceiveChat.readyState == 0) {
		httpReceiveChat.open("GET",GetChaturl + '&jal_lastID=' + jal_lastID + '&rand='+Math.floor(Math.random() * 1000000), true);
		httpReceiveChat.onreadystatechange = handlehHttpReceiveChat; 
		httpReceiveChat.send(null);
		jal_loadtimes++;
		if (jal_loadtimes > 9) jal_timeout = jal_timeout * 5 / 4;
	}
	setTimeout('receiveChatText()',jal_timeout);
}

//deals with the servers' reply to requesting new content
function handlehHttpReceiveChat() {
	if (httpReceiveChat.readyState == 4) {
		firstarray = httpReceiveChat.responseText.split('\n');
		if (firstarray.length == 2) { // if != 2, it failed, we should skip the processing part
			if(firstarray[0]!="") replaceUserOnline(firstarray[0]); //inserts the new content into the page
	
			results = firstarray[1].split('---'); //the fields are seperated by ---
			if(results.length > 2 && document.getElementById('TheBox') && jalSound==1)
			document.getElementById('TheBox').innerHTML='<embed src="<?php bloginfo('wpurl'); ?>/wp-content/plugins/wordspew/msg.wav" height="0" width="0" autostart="true" hidden="true"/>';

			if (results.length > 4) {
				for(i=0;i < (results.length-1);i=i+5) { //goes through the result one message at a time
				insertNewContent(results[i+1],results[i+2],results[i+3], results[i], results[i+4]); //inserts the new content into the page
				document.getElementById('jal_lastID').value = parseInt(results[i]) + 1;
				}
				jal_timeout = jal_org_timeout;
				jal_loadtimes = 1;
			}
			else if(results.length==3) {
				insertNewContent(results[1], results[2], "", results[0]);
				document.getElementById('jal_lastID').value = parseInt(results[0]) + 1;
			}
		}
	}
}

function setSound() {
pathToImg="<?php bloginfo('wpurl'); ?>/wp-content/plugins/wordspew/";
jalSound = (jal_getCookie("jalSound")=="" || jal_getCookie("jalSound")==0) ? 1 : 0;
document.cookie = "jalSound="+jalSound+";expires=<?php echo gmdate("D, d M Y H:i:s",time() + $offset)." UTC"; ?>;path=/;";
document.getElementById('JalSound').src=(jalSound==1) ? pathToImg+"sound_1.gif": pathToImg+"sound_0.gif"
}

//inserts the new content into the page
function insertNewContent(liName,liText, liUrl, liId, liUser) {
var myClass="";
if(liUser==1)
	myClass="jal_user ";
myClass+=liName;
	lastResponse="<?php _e('0 minute ago',wordspew);?>";
	response = document.getElementById("responseTime");
	response.replaceChild(document.createTextNode(lastResponse), response.firstChild);
	insertO = document.getElementById("outputList");
	oLi = document.createElement('li');
	oLi.setAttribute('id','comment-new'+liId);

	oSpan = document.createElement('span');
	oSpan.setAttribute('class',myClass);

	oName = document.createTextNode(liName);

	if (liUrl != "http://" && liUrl != '') {
		oURL = document.createElement('a');
		oURL.href = liUrl;
		<?php if($XHTML==0) echo 'if (liUrl.indexOf(this.location.href)==-1) oURL.setAttribute(\'target\',\'_blank\');'; ?>
		oURL.appendChild(oName);
	} else {
		oURL = oName;
	}

	oSpan.appendChild(oURL);
	oSpan.appendChild(document.createTextNode(' : '));
	oLi.appendChild(oSpan);
	oLi.innerHTML += jal_apply_filters(liText);
	insertO.insertBefore(oLi, insertO.firstChild);
	Fat.fade_element("comment-new"+liId, 30, <?php echo get_option('shoutbox_fade_length'); ?>, "#<?php echo get_option('shoutbox_fade_from'); ?>", "#<?php echo get_option('shoutbox_fade_to'); ?>");
}

function MasqueSelect() {
	mabox=document.getElementById('shoutboxOp');
	posEgal=mabox.options[0].text.indexOf("=");
	if(mabox.options[mabox.selectedIndex].value==eval(mabox.options[0].text.substr(0,posEgal)))
	document.getElementById('shoutbox_captcha').style.display="none";
}
//stores a new comment on the server
function sendComment() {
	currentChatText = document.getElementById('chatbarText').value;
	currentUrl = document.getElementById('shoutboxU').value;
	currentName = document.getElementById('shoutboxname').value;
	shoutboxOp = document.getElementById('shoutboxOp').value;
	shoutboxControl= document.getElementById('shoutboxControl').value;
	
	if (currentChatText == '') return;
	if(CheckSpam(currentName+' '+currentChatText, currentUrl)) {
		if (httpSendChat.readyState == 4 || httpSendChat.readyState == 0) {
			param = 'n='+ encodeURIComponent(currentName)+'&c='+ encodeURIComponent(currentChatText) +'&u='+ encodeURIComponent(currentUrl)+'&shoutboxOp='+encodeURIComponent(shoutboxOp)+'&shoutboxControl='+encodeURIComponent(shoutboxControl);	
			httpSendChat.open("POST", SendChaturl, true);
			httpSendChat.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			httpSendChat.onreadystatechange = receiveChatText;
			httpSendChat.send(param);
			document.forms['chatForm'].elements['chatbarText'].value = '';
		}
	}
}

// http://www.codingforums.com/showthread.php?t=63818
function pressedEnter(field,event) {
	var theCode = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;
	if (theCode == 13) {
		sendComment();
		return false;
	} 
	else return true;
}


//does clever things to the input and submit
function checkStatus(focusState) {
	currentChatText = document.forms['chatForm'].elements['chatbarText'];
	oSubmit = document.forms['chatForm'].elements['submit'];
	if (currentChatText.value != '' || focusState == 'active') {
		oSubmit.disabled = false;
	} else {
		oSubmit.disabled = true;
	}
}

function jal_getCookie(name) {
	var dc = document.cookie;
	var prefix = name + "=";
	var begin = dc.indexOf("; " + prefix);
	if (begin == -1) {
		begin = dc.indexOf(prefix);
		if (begin != 0) return null;
	} else
		begin += 2;
	var end = document.cookie.indexOf(";", begin);
	if (end == -1)
		end = dc.length;
	return unescape(dc.substring(begin + prefix.length, end));
}


//autoasigns a random name to a new user
//If the user has chosen a name, use that
function checkName() {
	jalCookie = jal_getCookie("jalUserName");
	currentName = document.getElementById('shoutboxname');
	
	if (currentName.value != jalCookie) {
		document.cookie = "jalUserName="+encodeURIComponent(currentName.value)+";expires=<?php echo gmdate("D, d M Y H:i:s",time() + $offset)." UTC"; ?>;path=/;"
	}

	if (jalCookie && currentName.value == '') {
		currentName.value = jalCookie;
		return;
	}
}

function checkUrl() {
	jalCookie = jal_getCookie("jalUrl");
	currentName = document.getElementById('shoutboxU');
	if(currentName.style.display!="none") {
		if (currentName.value == '')
			return;
			
		if (currentName.value != jalCookie) {
			document.cookie = "jalUrl="+currentName.value+";expires=<?php echo gmdate("D, d M Y H:i:s",time() + $offset)." UTC"; ?>;path=/;"
			return;
		}

		if (jalCookie && ( currentName.value == '' || currentName.value == "http://")) {
			currentName.value = jalCookie;
			return;
		}
	}
}


//initiates the XMLHttpRequest object as found here: http://www.webpasties.com/xmlHttpRequest
function getHTTPObject() {
  var xmlhttp;
  /*@cc_on
  @if (@_jscript_version >= 5)
    try {
      xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (E) {
        xmlhttp = false;
      }
    }
  @else
  xmlhttp = false;
  @end @*/
  if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
    try {
      xmlhttp = new XMLHttpRequest();
    } catch (e) {
      xmlhttp = false;
    }
  }
  return xmlhttp;
}

function replaceUserOnline(useronlinetext) {
	response = document.getElementById("usersOnline");
	response.replaceChild(document.createTextNode(useronlinetext), response.firstChild);
}