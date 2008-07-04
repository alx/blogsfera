<?php
@header('Content-type: text/javascript; charset=utf-8');
?>
function updateExcludedUsers(checkbox, user_id)
{
	var checked = checkbox.checked;
    var params = 'action=updateExcludedUser&user_id=' + user_id +"&selected=" + checked;
	sendRequest(params);
}

function saveWpSiteID()
{
	saveLocalOption('wp_site_id', 'firestats_site_id','positive_num');
}
