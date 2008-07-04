<?php
@header('Content-type: text/javascript; charset=utf-8');
require_once(dirname(dirname(__FILE__)).'/php/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
?>
function addBot()
{
    var bot = $F('bot_wildcard');
    $('bot_wildcard').value = '';
    var params = 'action=' + 'addBot' + '&wildcard=' + bot;
    sendRequest(params);
}

function removeBot()
{
    var index = $('botlist').selectedIndex;
    if (index == -1)
    {
        showError('<?php fs_e('You need to select an bot you want to remove from the table')?>');
    }
    else
    {
        var bot_id = $('botlist').item(index).value;
        var params = 'action=' + 'removeBot' + '&bot_id=' +bot_id;
        sendRequest(params);
    }
}

function addExcludedIP()
{
    var ip = $F('excluded_ip_text');
    if (validateIP(ip))
    {
        $('excluded_ip_text').value = '';
        var params = 'action=' + 'addExcludedIP' + '&ip=' +ip;
        sendRequest(params);
    }
    else
    {
        showError('<?php fs_e("Invalid IP address")?>' + ": " + ip);
    }
}

function removeExcludedIP()
{
    var index = $('exclude_ip_table').selectedIndex;
    if (index == -1)
    {
        showError("<?php fs_e('You need to select an IP address you want to remove from the table')?>");
    }
    else
    {
        var ip = $F('exclude_ip_table');
        var params = 'action=' + 'removeExcludedIP' + '&ip=' +ip;
        sendRequest(params);
    }
}


function changeLanguage()
{
    sendRequest('action=changeLanguage&language=' + $F('language_code'));
}

function changeTimeZone()
{
    saveOption('firestats_user_timezone','firestats_user_timezone','string','records_table');
}


function toggleArchiveOldData()
{
	if (!FS.archivingOldData)
	{
		FS.archiveOldData();
	}
	else
	{
		FS.archiveCleanup();
	}
}

function openImportBots() 
{
	openWindow('<?php echo fs_url('bridge.php').fs_get_request_suffix("file_id=import_bots")?>',300,300);
}
