<?php
@header('Content-type: text/javascript; charset=utf-8');
require_once(dirname(dirname(__FILE__)).'/php/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
?>

FS.newSiteDialog = function()
{
	createWindowUrl(400,340,'center','center',"<?php echo fs_js_url("php/window-new-edit-site.php")?>");
}

FS.deleteSiteDialog = function(id)
{
	var url = "<?php echo fs_js_url("php/window-delete-site.php",'site_id=')?>"+id;
	createWindowUrl(320,280,'center','center',url);
}

FS.editSiteDialog = function(id)
{
	var url = "<?php echo fs_js_url("php/window-new-edit-site.php",'site_id=')?>"+id;
	createWindowUrl(400,340,'center','center',url);
}

FS.createSite = function(button)
{
	var orig_sid = $F('original_site_id');
	if (orig_sid == '<?php fs_e('Automatic')?>') orig_sid = 'auto';
	var new_sid = $F('site_edit_id');
	if (new_sid == '<?php fs_e('Automatic')?>') new_sid = 'auto';
	var name = $F('site_edit_name');
	var type = $F('site_edit_type');
	var baseline_views = $F('baseline_views');
	var baseline_visitors = $F('baseline_visitors');
	var request = 'action=createNewSite&new_sid='+new_sid+'&name='+name+'&type='+type+'&baseline_views='+baseline_views+'&baseline_visitors='+baseline_visitors+'&update=fs_sites_table,sites_filter_span';
	sendRequest(request,function(response)
	{
		if (response.status == 'ok')
		{
			closeParentWindow(button);
		}
	});
}

FS.updateSite = function(id,button)
{
	var orig_sid = $F('original_site_id');
	if (orig_sid == '<?php fs_e('Automatic')?>') orig_sid = 'auto';
	var new_sid = $F('site_edit_id');
	if (new_sid == '<?php fs_e('Automatic')?>') new_sid = 'auto';
	var name = $F('site_edit_name');
	var type = $F('site_edit_type');
	var baseline_views = $F('baseline_views');
	var baseline_visitors = $F('baseline_visitors');
	var request = 'action=updateSiteInfo&new_sid='+new_sid+'&orig_sid='+orig_sid+'&name='+name+'&type='+type+'&baseline_views='+baseline_views+'&baseline_visitors='+baseline_visitors+'&update=fs_sites_table,sites_filter_span';
	sendRequest(request,function(response)
	{
		if (response.status == 'ok')
		{
			closeParentWindow(button);
		}
	});
}


FS.deleteSite = function(id,button)
{
	var sid = id;
	var action = $F('delete_type');
	var new_sid = $F('transfer_site_id');
	var request = 'action=deleteSite&site_id='+sid+'&update=fs_sites_table,sites_filter_span&action_code=' + action + "&new_sid=" + new_sid;
	sendRequest(request,function(response)
	{
		if (response.status == 'ok')
		{
			closeParentWindow(button);
		}
	});
}

FS.clearSiteID = function()
{
	$('fs_clear_site_id').style.display='none';
	$('site_edit_id').value = $('original_site_id').value;
}


function updateDeleteDialog()
{
	var dt = $('delete_type');
	var del = dt.selectedIndex == 0;
	var s = (del ? "none" : "block");
	$('transfer_option').style.display = s;
	var text = del ? "<?php fs_e("Delete")?>" : "<?php fs_e("Transfer")?>";
	$('fs_site_delete_button').innerHTML = text;
}


FS.activationHelp = function(type,id)
{
	var url = '<?php echo fs_url('php/help-window.php')?>?TYPE=' + type + "&SITE_ID=" + id;
	openWindow(url,600,600);
}
