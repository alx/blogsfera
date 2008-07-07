/* Import plugin specific language pack */
tinyMCE.importPluginLanguagePack('wordpress', 'en');

var TinyMCE_wordpressPlugin = {
	getInfo : function() {
		return {
			longname : 'WordPress Plugin',
			author : 'WordPress',
			authorurl : 'http://wordpress.org',
			infourl : 'http://wordpress.org',
			version : '1'
		};
	},

	getControlHTML : function(control_name) {
		switch (control_name) {
			case "wp_more":
				return tinyMCE.getButtonHTML(control_name, 'lang_wordpress_more_button', '{$pluginurl}/images/more.gif', 'wpMore');
			case "wp_page":
				return tinyMCE.getButtonHTML(control_name, 'lang_wordpress_page_button', '{$pluginurl}/images/page.gif', 'wpPage');
			case "wp_help":
				var buttons = tinyMCE.getButtonHTML(control_name, 'lang_help_button_title', '{$pluginurl}/images/help.gif', 'wpHelp');
				var hiddenControls = '<div class="zerosize">'
				+ '<input type="button" accesskey="n" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceSpellCheck\',false);" />'
				+ '<input type="button" accesskey="k" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'Strikethrough\',false);" />'
				+ '<input type="button" accesskey="l" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'InsertUnorderedList\',false);" />'
				+ '<input type="button" accesskey="o" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'InsertOrderedList\',false);" />'
				+ '<input type="button" accesskey="w" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'Outdent\',false);" />'
				+ '<input type="button" accesskey="q" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'Indent\',false);" />'
				+ '<input type="button" accesskey="f" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'JustifyLeft\',false);" />'
				+ '<input type="button" accesskey="c" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'JustifyCenter\',false);" />'
				+ '<input type="button" accesskey="r" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'JustifyRight\',false);" />'
				+ '<input type="button" accesskey="j" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'JustifyFull\',false);" />'
				+ '<input type="button" accesskey="a" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceLink\',true);" />'
				+ '<input type="button" accesskey="s" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'unlink\',false);" />'
				+ '<input type="button" accesskey="m" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceImage\',true);" />'
				+ '<input type="button" accesskey="t" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'wpMore\');" />'
				+ '<input type="button" accesskey="g" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'wpPage\');" />'
				+ '<input type="button" accesskey="u" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'Undo\',false);" />'
				+ '<input type="button" accesskey="y" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'Redo\',false);" />'
				+ '<input type="button" accesskey="h" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'wpHelp\',false);" />'
				+ '<input type="button" accesskey="b" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'Bold\',false);" />'
				+ '<input type="button" accesskey="v" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'wpAdv\',false);" />'
				+ '</div>';
				return buttons+hiddenControls;
			case "wp_adv":
				return tinyMCE.getButtonHTML(control_name, 'lang_wordpress_adv_button', '{$pluginurl}/images/toolbars.gif', 'wpAdv');
			case "wp_adv_start":
				return '<div id="wpadvbar" style="display:none;"><br />';
			case "wp_adv_end":
				return '</div>';
		}
		return '';
	},

	execCommand : function(editor_id, element, command, user_interface, value) {
		var inst = tinyMCE.getInstanceById(editor_id);
		var focusElm = inst.getFocusElement();
		var doc = inst.getDoc();

		function getAttrib(elm, name) {
			return elm.getAttribute(name) ? elm.getAttribute(name) : "";
		}

		// Handle commands
		switch (command) {
			case "wpMore":
				var flag = "";
				var template = new Array();
				var altMore = tinyMCE.getLang('lang_wordpress_more_alt');

				// Is selection a image
				if (focusElm != null && focusElm.nodeName.toLowerCase() == "img") {
					flag = getAttrib(focusElm, 'class');

					if (flag != 'mce_plugin_wordpress_more') // Not a wordpress
						return true;

					action = "update";
				}
			});

			// Register commands
			ed.addCommand('WP_More', function() {
				ed.execCommand('mceInsertContent', 0, moreHTML);
			});

			ed.addCommand('WP_Page', function() {
				ed.execCommand('mceInsertContent', 0, nextpageHTML);
			});

			ed.addCommand('WP_Help', function() {
					ed.windowManager.open({
						url : tinymce.baseURL + '/wp-mce-help.php',
						width : 450,
						height : 420,
						inline : 1
					});
				});

			ed.addCommand('WP_Adv', function() {
				var id = ed.controlManager.get(tbId).id, cm = ed.controlManager, cook = tinymce.util.Cookie, date;

				date = new Date();
				date.setTime(date.getTime()+(10*365*24*60*60*1000));

				if (DOM.isHidden(id)) {
					cm.setActive('wp_adv', 1);
					DOM.show(id);
					t._resizeIframe(ed, tbId, -28);
					ed.settings.wordpress_adv_hidden = 0;
					cook.set('kitchenSink', '1', date);
				} else {
					adv.style.display = 'none';
					tinyMCE.switchClass(editor_id + '_wp_adv', 'mceButtonNormal');
				}
			});

			// Register buttons
			ed.addButton('wp_more', {
				title : 'wordpress.wp_more_desc',
				image : url + '/img/more.gif',
				cmd : 'WP_More'
			});

			ed.addButton('wp_page', {
				title : 'wordpress.wp_page_desc',
				image : url + '/img/page.gif',
				cmd : 'WP_Page'
			});

			ed.addButton('wp_help', {
				title : 'wordpress.wp_help_desc',
				image : url + '/img/help.gif',
				cmd : 'WP_Help'
			});

			ed.addButton('wp_adv', {
				title : 'wordpress.wp_adv_desc',
				image : url + '/img/toolbars.gif',
				cmd : 'WP_Adv'
			});

			// Add Media buttons
			ed.addButton('add_media', {
				title : 'wordpress.add_media',
				image : url + '/img/media.gif',
				onclick : function() {
					tb_show('', tinymce.DOM.get('add_media').href);
					tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
				}
			});

			ed.addButton('add_image', {
				title : 'wordpress.add_image',
				image : url + '/img/image.gif',
				onclick : function() {
					tb_show('', tinymce.DOM.get('add_image').href);
					tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
				}
			});

			ed.addButton('add_video', {
				title : 'wordpress.add_video',
				image : url + '/img/video.gif',
				onclick : function() {
					tb_show('', tinymce.DOM.get('add_video').href);
					tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
				}
			});

			ed.addButton('add_audio', {
				title : 'wordpress.add_audio',
				image : url + '/img/audio.gif',
				onclick : function() {
					tb_show('', tinymce.DOM.get('add_audio').href);
					tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
				}
			});

			// Add Media buttons to fullscreen
			ed.onBeforeExecCommand.add(function(ed, cmd, ui, val) {
				if ( 'mceFullScreen' != cmd ) return;
				if ( 'mce_fullscreen' != ed.id )
					ed.settings.theme_advanced_buttons1 += ',|,add_image,add_video,add_audio,add_media';
			});

			// Add class "alignleft", "alignright" and "aligncenter" when selecting align for images.
			ed.addCommand('JustifyLeft', function() {
				var n = ed.selection.getNode();

				if ( n.nodeName != 'IMG' )
					ed.editorCommands.mceJustify('JustifyLeft', 'left');
				else ed.plugins.wordpress.do_align(n, 'alignleft');
			});

			ed.addCommand('JustifyRight', function() {
				var n = ed.selection.getNode();

				if ( n.nodeName != 'IMG' )
					ed.editorCommands.mceJustify('JustifyRight', 'right');
				else ed.plugins.wordpress.do_align(n, 'alignright');
			});

			ed.addCommand('JustifyCenter', function() {
				var n = ed.selection.getNode(), P = ed.dom.getParent(n, 'p'), DL = ed.dom.getParent(n, 'dl');

				if ( n.nodeName == 'IMG' && ( P || DL ) )
					ed.plugins.wordpress.do_align(n, 'aligncenter');
				else ed.editorCommands.mceJustify('JustifyCenter', 'center');
			});

			// Word count if script is loaded
			if ( 'undefined' != typeof wpWordCount ) {
				var last = 0;
				ed.onKeyUp.add(function(ed, e) {
					if ( e.keyCode == last ) return;
					if ( 13 == e.keyCode || 8 == last || 46 == last ) wpWordCount.wc( ed.getContent({format : 'raw'}) );
					last = e.keyCode;
				});
			};

			// Add listeners to handle more break
			t._handleMoreBreak(ed, url);

			// Add custom shortcuts
			ed.addShortcut('alt+shift+c', ed.getLang('justifycenter_desc'), 'JustifyCenter');
			ed.addShortcut('alt+shift+r', ed.getLang('justifyright_desc'), 'JustifyRight');
			ed.addShortcut('alt+shift+l', ed.getLang('justifyleft_desc'), 'JustifyLeft');
			ed.addShortcut('alt+shift+j', ed.getLang('justifyfull_desc'), 'JustifyFull');
			ed.addShortcut('alt+shift+q', ed.getLang('blockquote_desc'), 'mceBlockQuote');
			ed.addShortcut('alt+shift+u', ed.getLang('bullist_desc'), 'InsertUnorderedList');
			ed.addShortcut('alt+shift+o', ed.getLang('numlist_desc'), 'InsertOrderedList');
			ed.addShortcut('alt+shift+d', ed.getLang('striketrough_desc'), 'Strikethrough');
			ed.addShortcut('alt+shift+n', ed.getLang('spellchecker.desc'), 'mceSpellCheck');
			ed.addShortcut('alt+shift+a', ed.getLang('link_desc'), 'mceLink');
			ed.addShortcut('alt+shift+s', ed.getLang('unlink_desc'), 'unlink');
			ed.addShortcut('alt+shift+m', ed.getLang('image_desc'), 'mceImage');
			ed.addShortcut('alt+shift+g', ed.getLang('fullscreen.desc'), 'mceFullScreen');
			ed.addShortcut('alt+shift+z', ed.getLang('wp_adv_desc'), 'WP_Adv');
			ed.addShortcut('alt+shift+h', ed.getLang('help_desc'), 'WP_Help');
			ed.addShortcut('alt+shift+t', ed.getLang('wp_more_desc'), 'WP_More');
			ed.addShortcut('alt+shift+p', ed.getLang('wp_page_desc'), 'WP_Page');

			if ( tinymce.isWebKit ) {
				ed.addShortcut('alt+shift+b', ed.getLang('bold_desc'), 'Bold');
				ed.addShortcut('alt+shift+i', ed.getLang('italic_desc'), 'Italic');
			}
		},

		getInfo : function() {
			return {
				longname : 'WordPress Plugin',
				author : 'WordPress', // add Moxiecode?
				authorurl : 'http://wordpress.org',
				infourl : 'http://wordpress.org',
				version : '3.0'
			};
		},

		// Internal functions
		do_align : function(n, a) {
			var P, DL, DIV, cls, c, ed = tinyMCE.activeEditor;

			P = ed.dom.getParent(n, 'p');
			DL = ed.dom.getParent(n, 'dl');
			DIV = ed.dom.getParent(n, 'div');

			if ( DL && DIV ) {
				cls = ed.dom.hasClass(DL, a) ? 'alignnone' : a;
				DL.className = DL.className.replace(/align[^ '"]+\s?/g, '');
				ed.dom.addClass(DL, cls);
				c = (cls == 'aligncenter') ? ed.dom.addClass(DIV, 'mceIEcenter') : ed.dom.removeClass(DIV, 'mceIEcenter');
			} else if ( P ) {
				cls = ed.dom.hasClass(n, a) ? 'alignnone' : a;
				n.className = n.className.replace(/align[^ '"]+\s?/g, '');
				ed.dom.addClass(n, cls);
				if ( cls == 'aligncenter' )
					ed.dom.setStyle(P, 'textAlign', 'center');
				else if (P.style && P.style.textAlign == 'center')
					ed.dom.setStyle(P, 'textAlign', '');
			}

			ed.execCommand('mceRepaint');
		},

		// Resizes the iframe by a relative height value
		_resizeIframe : function(ed, tb_id, dy) {
			var ifr = ed.getContentAreaContainer().firstChild;

			DOM.setStyle(ifr, 'height', ifr.clientHeight + dy); // Resize iframe
			ed.theme.deltaHeight += dy; // For resize cookie
		},

		_handleMoreBreak : function(ed, url) {
			var moreHTML = '<img src="' + url + '/img/trans.gif" alt="$1" class="mceWPmore mceItemNoResize" title="'+ed.getLang('wordpress.wp_more_alt')+'" />';
			var nextpageHTML = '<img src="' + url + '/img/trans.gif" class="mceWPnextpage mceItemNoResize" title="'+ed.getLang('wordpress.wp_page_alt')+'" />';

			// Load plugin specific CSS into editor
			ed.onInit.add(function() {
				ed.dom.loadCSS(url + '/css/content.css');
			});

			// Display morebreak instead if img in element path
			ed.onPostRender.add(function() {
				if (ed.theme.onResolveName) {
					ed.theme.onResolveName.add(function(th, o) {
						if (o.node.nodeName == 'IMG') {
							if ( ed.dom.hasClass(o.node, 'mceWPmore') )
								o.name = 'wpmore';
							if ( ed.dom.hasClass(o.node, 'mceWPnextpage') )
								o.name = 'wppage';
						}

					});
				}

				// Remove anonymous, empty paragraphs.
				content = content.replace(new RegExp('<p>(\\s|&nbsp;)*</p>', 'mg'), '');

				// Handle table badness.
				content = content.replace(new RegExp('<(table( [^>]*)?)>.*?<((tr|thead)( [^>]*)?)>', 'mg'), '<$1><$3>');
				content = content.replace(new RegExp('<(tr|thead|tfoot)>.*?<((td|th)( [^>]*)?)>', 'mg'), '<$1><$2>');
				content = content.replace(new RegExp('</(td|th)>.*?<(td( [^>]*)?|th( [^>]*)?|/tr|/thead|/tfoot)>', 'mg'), '</$1><$2>');
				content = content.replace(new RegExp('</tr>.*?<(tr|/table)>', 'mg'), '</tr><$1>');
				content = content.replace(new RegExp('<(/?(table|tbody|tr|th|td)[^>]*)>(\\s*|(<br ?/?>)*)*', 'g'), '<$1>');

				// Pretty it up for the source editor.
				var blocklist = 'blockquote|ul|ol|li|table|thead|tr|th|td|div|h\\d|pre|p';
				content = content.replace(new RegExp('\\s*</('+blocklist+')>\\s*', 'mg'), '</$1>\n');
				content = content.replace(new RegExp('\\s*<(('+blocklist+')[^>]*)>', 'mg'), '\n<$1>');
				content = content.replace(new RegExp('<((li|/?tr|/?thead|/?tfoot)( [^>]*)?)>', 'g'), '\t<$1>');
				content = content.replace(new RegExp('<((td|th)( [^>]*)?)>', 'g'), '\t\t<$1>');
				content = content.replace(new RegExp('\\s*<br ?/?>\\s*', 'mg'), '<br />\n');
				content = content.replace(new RegExp('^\\s*', ''), '');
				content = content.replace(new RegExp('\\s*$', ''), '');

				break;
		}

		// Pass through to next handler in chain
		return content;
	},

	handleNodeChange : function(editor_id, node, undo_index, undo_levels, visual_aid, any_selection) {

		tinyMCE.switchClass(editor_id + '_wp_more', 'mceButtonNormal');
		tinyMCE.switchClass(editor_id + '_wp_page', 'mceButtonNormal');

		if (node == null)
			return;

		do {
			if (node.nodeName.toLowerCase() == "img" && tinyMCE.getAttrib(node, 'class').indexOf('mce_plugin_wordpress_more') == 0)
				tinyMCE.switchClass(editor_id + '_wp_more', 'mceButtonSelected');
			if (node.nodeName.toLowerCase() == "img" && tinyMCE.getAttrib(node, 'class').indexOf('mce_plugin_wordpress_page') == 0)
				tinyMCE.switchClass(editor_id + '_wp_page', 'mceButtonSelected');
		} while ((node = node.parentNode));

		return true;
	},

	saveCallback : function(el, content, body) {
		// We have a TON of cleanup to do.

		// Mark </p> if it has any attributes.
		content = content.replace(new RegExp('(<p[^>]+>.*?)</p>', 'mg'), '$1</p#>');

		// Decode the ampersands of time.
		// content = content.replace(new RegExp('&amp;', 'g'), '&');

		// Get it ready for wpautop.
		content = content.replace(new RegExp('\\s*<p>', 'mgi'), '');
		content = content.replace(new RegExp('\\s*</p>\\s*', 'mgi'), '\n\n');
		content = content.replace(new RegExp('\\n\\s*\\n', 'mgi'), '\n\n');
		content = content.replace(new RegExp('\\s*<br ?/?>\\s*', 'gi'), '\n');

		// Fix some block element newline issues
		var blocklist = 'blockquote|ul|ol|li|table|thead|tr|th|td|div|h\\d|pre';
		content = content.replace(new RegExp('\\s*<(('+blocklist+') ?[^>]*)\\s*>', 'mg'), '\n<$1>');
		content = content.replace(new RegExp('\\s*</('+blocklist+')>\\s*', 'mg'), '</$1>\n');
		content = content.replace(new RegExp('<li>', 'g'), '\t<li>');

		// Unmark special paragraph closing tags
		content = content.replace(new RegExp('</p#>', 'g'), '</p>\n');
		content = content.replace(new RegExp('\\s*(<p[^>]+>.*</p>)', 'mg'), '\n$1');

		// Trim trailing whitespace
		content = content.replace(new RegExp('\\s*$', ''), '');

		// Hope.
		return content;

	},

	_parseAttributes : function(attribute_string) {
		var attributeName = "";
		var attributeValue = "";
		var withInName;
		var withInValue;
		var attributes = new Array();
		var whiteSpaceRegExp = new RegExp('^[ \n\r\t]+', 'g');
		var titleText = tinyMCE.getLang('lang_wordpress_more');
		var titleTextPage = tinyMCE.getLang('lang_wordpress_page');

		if (attribute_string == null || attribute_string.length < 2)
			return null;

		withInName = withInValue = false;

		for (var i=0; i<attribute_string.length; i++) {
			var chr = attribute_string.charAt(i);

			if ((chr == '"' || chr == "'") && !withInValue)
				withInValue = true;
			else if ((chr == '"' || chr == "'") && withInValue) {
				withInValue = false;

				var pos = attributeName.lastIndexOf(' ');
				if (pos != -1)
					attributeName = attributeName.substring(pos+1);

				attributes[attributeName.toLowerCase()] = attributeValue.substring(1);

				attributeName = "";
				attributeValue = "";
			} else if (!whiteSpaceRegExp.test(chr) && !withInName && !withInValue)
				withInName = true;

			if (chr == '=' && withInName)
				withInName = false;

			if (withInName)
				attributeName += chr;

			if (withInValue)
				attributeValue += chr;
		}

		return attributes;
	}
};

tinyMCE.addPlugin("wordpress", TinyMCE_wordpressPlugin);

/* This little hack protects our More and Page placeholders from the removeformat command */
tinyMCE.orgExecCommand = tinyMCE.execCommand;
tinyMCE.execCommand = function (command, user_interface, value) {
	re = this.orgExecCommand(command, user_interface, value);

	if ( command == 'removeformat' ) {
		var inst = tinyMCE.getInstanceById('mce_editor_0');
		doc = inst.getDoc();
		var imgs = doc.getElementsByTagName('img');
		for (i=0;img=imgs[i];i++)
			img.className = img.name;
	}
	return re;
};
wpInstTriggerSave = function (skip_cleanup, skip_callback) {
	var e, nl = new Array(), i, s;

	this.switchSettings();
	s = tinyMCE.settings;

	// Force hidden tabs visible while serializing
	if (tinyMCE.isMSIE && !tinyMCE.isOpera) {
		e = this.iframeElement;

		do {
			if (e.style && e.style.display == 'none') {
				e.style.display = 'block';
				nl[nl.length] = {elm : e, type : 'style'};
			}

			if (e.style && s.hidden_tab_class.length > 0 && e.className.indexOf(s.hidden_tab_class) != -1) {
				e.className = s.display_tab_class;
				nl[nl.length] = {elm : e, type : 'class'};
			}
		} while ((e = e.parentNode) != null)
	}

	tinyMCE.settings['preformatted'] = false;

	// Default to false
	if (typeof(skip_cleanup) == "undefined")
		skip_cleanup = false;

	// Default to false
	if (typeof(skip_callback) == "undefined")
		skip_callback = false;

//	tinyMCE._setHTML(this.getDoc(), this.getBody().innerHTML);

	// Remove visual aids when cleanup is disabled
	if (this.settings['cleanup'] == false) {
		tinyMCE.handleVisualAid(this.getBody(), true, false, this);
		tinyMCE._setEventsEnabled(this.getBody(), true);
	}

	tinyMCE._customCleanup(this, "submit_content_dom", this.contentWindow.document.body);
	tinyMCE.selectedInstance.getWin().oldfocus=tinyMCE.selectedInstance.getWin().focus;
	tinyMCE.selectedInstance.getWin().focus=function() {};
	var htm = tinyMCE._cleanupHTML(this, this.getDoc(), this.settings, this.getBody(), tinyMCE.visualAid, true, true);
	tinyMCE.selectedInstance.getWin().focus=tinyMCE.selectedInstance.getWin().oldfocus;
	htm = tinyMCE._customCleanup(this, "submit_content", htm);

	if (!skip_callback && tinyMCE.settings['save_callback'] != "")
		var content = eval(tinyMCE.settings['save_callback'] + "(this.formTargetElementId,htm,this.getBody());");

	// Use callback content if available
	if ((typeof(content) != "undefined") && content != null)
		htm = content;

	// Replace some weird entities (Bug: #1056343)
	htm = tinyMCE.regexpReplace(htm, "&#40;", "(", "gi");
	htm = tinyMCE.regexpReplace(htm, "&#41;", ")", "gi");
	htm = tinyMCE.regexpReplace(htm, "&#59;", ";", "gi");
	htm = tinyMCE.regexpReplace(htm, "&#34;", "&quot;", "gi");
	htm = tinyMCE.regexpReplace(htm, "&#94;", "^", "gi");

	if (this.formElement)
		this.formElement.value = htm;

	if (tinyMCE.isSafari && this.formElement)
		this.formElement.innerText = htm;

	// Hide them again (tabs in MSIE)
	for (i=0; i<nl.length; i++) {
		if (nl[i].type == 'style')
			nl[i].elm.style.display = 'none';
		else
			nl[i].elm.className = s.hidden_tab_class;
	}
}
tinyMCE.wpTriggerSave = function () {
	var inst, n;
	for (n in tinyMCE.instances) {
		inst = tinyMCE.instances[n];
		if (!tinyMCE.isInstance(inst))
			continue;
		inst.wpTriggerSave = wpInstTriggerSave;
		inst.wpTriggerSave(false, false);
	}
}

function switchEditors(id) {
	var inst = tinyMCE.getInstanceById(id);
	var qt = document.getElementById('quicktags');
	var H = document.getElementById('edButtonHTML');
	var P = document.getElementById('edButtonPreview');
	var ta = document.getElementById(id);
	var pdr = ta.parentNode;

	if ( inst ) {
		edToggle(H, P);

		if ( tinyMCE.isMSIE && !tinyMCE.isOpera ) {
			// IE rejects the later overflow assignment so we skip this step.
			// Alternate code might be nice. Until then, IE reflows.
		} else {
			// Lock the fieldset's height to prevent reflow/flicker
			pdr.style.height = pdr.clientHeight + 'px';
			pdr.style.overflow = 'hidden';
		}

		// Save the coords of the bottom right corner of the rich editor
		var table = document.getElementById(inst.editorId + '_parent').getElementsByTagName('table')[0];
		var y1 = table.offsetTop + table.offsetHeight;

		if ( TinyMCE_AdvancedTheme._getCookie("TinyMCE_" + inst.editorId + "_height") == null ) {
			var expires = new Date();
			expires.setTime(expires.getTime() + 3600000 * 24 * 30);
			var offset = tinyMCE.isMSIE ? 1 : 2;
			TinyMCE_AdvancedTheme._setCookie("TinyMCE_" + inst.editorId + "_height", "" + (table.offsetHeight - offset), expires);
		}

		// Unload the rich editor
		inst.triggerSave(false, false);
		htm = inst.formElement.value;
		tinyMCE.removeMCEControl(id);
		document.getElementById(id).value = htm;
		--tinyMCE.idCounter;

		// Reveal Quicktags and textarea
		qt.style.display = 'block';
		ta.style.display = 'inline';

		// Set the textarea height to match the rich editor
		y2 = ta.offsetTop + ta.offsetHeight;
		ta.style.height = (ta.clientHeight + y1 - y2) + 'px';

		// Tweak the widths
		ta.parentNode.style.paddingRight = '12px';

		if ( tinyMCE.isMSIE && !tinyMCE.isOpera ) {
		} else {
			// Unlock the fieldset's height
			pdr.style.height = 'auto';
			pdr.style.overflow = 'display';
		}
	} else {
		edToggle(P, H);
		edCloseAllTags(); // :-(

		if ( tinyMCE.isMSIE && !tinyMCE.isOpera ) {
		} else {
			// Lock the fieldset's height
			pdr.style.height = pdr.clientHeight + 'px';
			pdr.style.overflow = 'hidden';
		}

		// Hide Quicktags and textarea
		qt.style.display = 'none';
		ta.style.display = 'none';

		// Tweak the widths
		ta.parentNode.style.paddingRight = '0px';

		// Load the rich editor with formatted html
		if ( tinyMCE.isMSIE ) {
			ta.value = wpautop(ta.value);
			tinyMCE.addMCEControl(ta, id);
		} else {
			htm = wpautop(ta.value);
			tinyMCE.addMCEControl(ta, id);
			tinyMCE.getInstanceById(id).execCommand('mceSetContent', null, htm);
		}

		if ( tinyMCE.isMSIE && !tinyMCE.isOpera ) {
		} else {
			// Unlock the fieldset's height
			pdr.style.height = 'auto';
			pdr.style.overflow = 'display';
		}
	}
}

function edToggle(A, B) {
	A.className = 'edButtonFore';
	B.className = 'edButtonBack';

	B.onclick = A.onclick;
	A.onclick = null;
}

function wpautop(pee) {
	pee = pee + "\n\n";
	pee = pee.replace(new RegExp('<br />\\s*<br />', 'gi'), "\n\n");
	pee = pee.replace(new RegExp('(<(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)', 'gi'), "\n$1"); 
	pee = pee.replace(new RegExp('(</(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|p|h[1-6])>)', 'gi'), "$1\n\n");
	pee = pee.replace(new RegExp("\\r\\n|\\r", 'g'), "\n");
	pee = pee.replace(new RegExp("\\n\\s*\\n+", 'g'), "\n\n");
	pee = pee.replace(new RegExp('([\\s\\S]+?)\\n\\n', 'mg'), "<p>$1</p>\n");
	pee = pee.replace(new RegExp('<p>\\s*?</p>', 'gi'), '');
	pee = pee.replace(new RegExp('<p>\\s*(</?(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|hr|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)\\s*</p>', 'gi'), "$1");
	pee = pee.replace(new RegExp("<p>(<li.+?)</p>", 'gi'), "$1");
	pee = pee.replace(new RegExp('<p><blockquote([^>]*)>', 'gi'), "<blockquote$1><p>");
	pee = pee.replace(new RegExp('</blockquote></p>', 'gi'), '</p></blockquote>');
	pee = pee.replace(new RegExp('<p>\\s*(</?(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|hr|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)', 'gi'), "$1");
	pee = pee.replace(new RegExp('(</?(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)\\s*</p>', 'gi'), "$1"); 
	pee = pee.replace(new RegExp('\\s*\\n', 'gi'), "<br />\n");
	pee = pee.replace(new RegExp('(</?(?:table|thead|tfoot|caption|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)\\s*<br />', 'gi'), "$1");
	pee = pee.replace(new RegExp('<br />(\\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)>)', 'gi'), '$1');
	pee = pee.replace(new RegExp('^((?:&nbsp;)*)\\s', 'mg'), '$1&nbsp;');
	//pee = pee.replace(new RegExp('(<pre.*?>)(.*?)</pre>!ise', " stripslashes('$1') .  stripslashes(clean_pre('$2'))  . '</pre>' "); // Hmm...
	return pee;
}
