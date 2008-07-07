var autosaveLast = '';
var autosavePeriodical;
var autosaveOldMessage = '';
var autosaveDelayURL = null;
var previewwin;

jQuery(function($) {
	autosaveLast = $('#post #title').val()+$('#post #content').val();
	autosavePeriodical = $.schedule({time: autosaveL10n.autosaveInterval * 1000, func: function() { autosave(); }, repeat: true, protect: true});
	
function autosave_start_timer() {
	var form = $('post');
	autosaveLast = form.post_title.value+form.content.value;
	// Keep autosave_interval in sync with edit_post().
	autosavePeriodical = new PeriodicalExecuter(autosave, autosaveL10n.autosaveInterval);
	//Disable autosave after the form has been submitted
	$("#post").submit(function() { $.cancel(autosavePeriodical); });
	
	// Autosave when the preview button is clicked. 
	$('#previewview a').click(function(e) {
		autosave();
		autosaveDelayURL = this.href;
		previewwin = window.open('','_blank');

		e.preventDefault();
		return false;
	});
});

function autosave_parse_response(response) {
	var res = wpAjax.parseAjaxResponse(response, 'autosave'); // parse the ajax response
	var message = '';

	if ( res && res.responses && res.responses.length ) {
		message = res.responses[0].data; // The saved message or error.
		// someone else is editing: disable autosave, set errors
		if ( res.responses[0].supplemental ) {
			if ( 'disable' == res.responses[0].supplemental['disable_autosave'] ) {
				autosave = function() {};
				res = { errors: true };
			}
			jQuery.each(res.responses[0].supplemental, function(selector, value) {
				if ( selector.match(/^replace-/) ) {
					jQuery('#'+selector.replace('replace-', '')).val(value);
				}
			});
		}

		// if no errors: add preview link and slug UI
		if ( !res.errors ) {
			var postID = parseInt( res.responses[0].id );
			if ( !isNaN(postID) && postID > 0 ) {
				autosave_update_preview_link(postID);
				autosave_update_slug(postID);
			}
		}
	}
}
addLoadEvent(autosave_start_timer)

function autosave_cur_time() {
	var now = new Date();
	return "" + ((now.getHours() >12) ? now.getHours() -12 : now.getHours()) + 
	((now.getMinutes() < 10) ? ":0" : ":") + now.getMinutes() +
	((now.getSeconds() < 10) ? ":0" : ":") + now.getSeconds();
}

function autosave_update_nonce() {
	var response = nonceAjax.response;
	document.getElementsByName('_wpnonce')[0].value = response;
}

function autosave_update_post_ID() {
	var response = autosaveAjax.response;
	var res = parseInt(response);
	var message;

	if(isNaN(res)) {
		message = autosaveL10n.errorText.replace(/%response%/g, response);
	} else {
		message = autosaveL10n.saveText.replace(/%time%/g, autosave_cur_time());
		$('post_ID').name = "post_ID";
		$('post_ID').value = res;
		// We need new nonces
		nonceAjax = new sack();
		nonceAjax.element = null;
		nonceAjax.setVar("action", "autosave-generate-nonces");
		nonceAjax.setVar("post_ID", res);
		nonceAjax.setVar("cookie", document.cookie);
		nonceAjax.setVar("post_type", $('post_type').value);
		nonceAjax.requestFile = autosaveL10n.requestFile;
		nonceAjax.onCompletion = autosave_update_nonce;
		nonceAjax.method = "POST";
		nonceAjax.runAJAX();
		$('hiddenaction').value = 'editpost';
	}
	$('autosave').innerHTML = message;
	autosave_enable_buttons();
}

function autosave_update_preview_link(post_id) {
	// Add preview button if not already there
	if ( !jQuery('#previewview > *').size() ) {
		var post_type = jQuery('#post_type').val();
		var previewText = 'page' == post_type ? autosaveL10n.previewPageText : autosaveL10n.previewPostText;
		jQuery.post(autosaveL10n.requestFile, {
			action: "get-permalink",
			post_id: post_id,
			getpermalinknonce: jQuery('#getpermalinknonce').val()
		}, function(permalink) {
			jQuery('#previewview').html('<a target="_blank" href="'+permalink+'" tabindex="4">'+previewText+'</a>');

			// Autosave when the preview button is clicked.  
			jQuery('#previewview a').click(function(e) {
				autosave();
				autosaveDelayURL = this.href;
				previewwin = window.open('','_blank');

				e.preventDefault();
				return false;
			});
		});
	}
}

function autosave_saved() {
	var response = autosaveAjax.response;
	var res = parseInt(response);
	var message;

	if(isNaN(res)) {
		message = autosaveL10n.errorText.replace(/%response%/g, response);
	} else {
		message = autosaveL10n.saveText.replace(/%time%/g, autosave_cur_time());
	}
	$('autosave').innerHTML = message;
	autosave_enable_buttons();
}

function autosave_disable_buttons() {
	var form = $('post');
	form.save ? form.save.disabled = 'disabled' : null;
	form.submit ? form.submit.disabled = 'disabled' : null;
	form.publish ? form.publish.disabled = 'disabled' : null;
	form.deletepost ? form.deletepost.disabled = 'disabled' : null;
	setTimeout('autosave_enable_buttons();', 1000); // Re-enable 1 sec later.  Just gives autosave a head start to avoid collisions.
}

function autosave_enable_buttons() {
	jQuery("#submitpost :button:disabled, #submitpost :submit:disabled").attr('disabled', '');
	if ( autosaveDelayURL ) {
		previewwin.location = autosaveDelayURL;
		autosaveDelayURL = null;
	}
}

function autosave() {
	var form = $('post');
	var rich = ((typeof tinyMCE != "undefined") && tinyMCE.getInstanceById('content')) ? true : false;

	autosaveAjax = new sack();

	/* Gotta do this up here so we can check the length when tinyMCE is in use */
	if ( rich ) {		
		var ed = tinyMCE.activeEditor;
		if ( 'mce_fullscreen' == ed.id )
			tinyMCE.get('content').setContent(ed.getContent({format : 'raw'}), {format : 'raw'});
		tinyMCE.get('content').save();
	}
	
	post_data["content"] = jQuery("#content").val();
	if ( jQuery('#post_name').val() )
		post_data["post_name"] = jQuery('#post_name').val();

	// Nothing to save or no change.
	if( (post_data["post_title"].length==0 && post_data["content"].length==0) || post_data["post_title"] + post_data["content"] == autosaveLast) {
		doAutoSave = false
	}

	if(form.post_title.value.length==0 || form.content.value.length==0 || form.post_title.value+form.content.value == autosaveLast)
		return;

	autosave_disable_buttons();

	var origStatus = jQuery('#original_post_status').val();

	cats = document.getElementsByName("post_category[]");
	goodcats = ([]);
	jQuery("[@name='post_category[]']:checked").each( function(i) {
		goodcats.push(this.value);
	} );
	post_data["catslist"] = goodcats.join(",");

	if ( jQuery("#comment_status").attr("checked") )
		post_data["comment_status"] = 'open';
	if ( jQuery("#ping_status").attr("checked") )
		post_data["ping_status"] = 'open';
	if ( jQuery("#excerpt").size() )
		post_data["excerpt"] = jQuery("#excerpt").val();
	if ( jQuery("#post_author").size() )
		post_data["post_author"] = jQuery("#post_author").val();

	// Don't run while the TinyMCE spellcheck is on.  Why?  Who knows.
	if ( rich && tinyMCE.activeEditor.plugins.spellchecker && tinyMCE.activeEditor.plugins.spellchecker.active ) {
		doAutoSave = false;
	}
	catslist = goodcats.join(",");

	autosaveAjax.setVar("action", "autosave");
	autosaveAjax.setVar("cookie", document.cookie);
	autosaveAjax.setVar("catslist", catslist);
	autosaveAjax.setVar("post_ID", $("post_ID").value);
	autosaveAjax.setVar("post_title", form.post_title.value);
	autosaveAjax.setVar("post_type", form.post_type.value);
	if ( form.comment_status.checked )
		autosaveAjax.setVar("comment_status", 'open');
	if ( form.ping_status.checked )
		autosaveAjax.setVar("ping_status", 'open');
	if(form.excerpt)
		autosaveAjax.setVar("excerpt", form.excerpt.value);

	if ( typeof tinyMCE == "undefined" || tinyMCE.configs.length < 1 || rich == false ) {
		autosaveAjax.setVar("content", form.content.value);
	} else {
		tinyMCE.wpTriggerSave();
		autosaveAjax.setVar("content", form.content.value);
	}

	autosaveAjax.requestFile = autosaveL10n.requestFile;
	autosaveAjax.method = "POST";
	autosaveAjax.element = null;
	autosaveAjax.onLoading = autosave_loading;
	autosaveAjax.onInteractive = autosave_loading;
	if(parseInt($("post_ID").value) < 1)
		autosaveAjax.onCompletion = autosave_update_post_ID;
	else
		autosaveAjax.onCompletion = autosave_saved;
	autosaveAjax.runAJAX();
}
