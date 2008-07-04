<?php
@header('Content-type: text/javascript; charset=utf-8');
require_once(dirname(dirname(__FILE__)).'/php/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
?>
function testDBConnection()
{
    var host    = $F('text_database_host');
    var user    = $F('text_database_user');
    var pass    = $F('text_database_pass');
    var dbname  = $F('text_database_name');
    var prefix  = $F('text_database_prefix');
    var params = 'action=testDBConnection&host=' + host + "&user=" + user + "&pass="+pass+"&dbname="+dbname+"&table_prefix="+prefix;
    sendRequest(params);
}

function installDBTables()
{
    var host    = $F('text_database_host');
    var user    = $F('text_database_user');
    var pass    = $F('text_database_pass');
    var dbname  = $F('text_database_name');
    var prefix  = $F('text_database_prefix');
    var params = 'action=installDBTables&host=' + host + "&user=" + user + "&pass="+pass+"&dbname="+dbname+"&table_prefix="+prefix;
    sendRequest(params);
}


function attachToDatabase()
{
    var host    = $F('text_database_host');
    var user    = $F('text_database_user');
    var pass    = $F('text_database_pass');
    var dbname  = $F('text_database_name');
    var prefix  = $F('text_database_prefix');
    var params = 'action=attachToDatabase&host=' + host + "&user=" + user + "&pass="+pass+"&dbname="+dbname+"&table_prefix="+prefix;
    sendRequest(params);
}

function createNewDatabase()
{
    var user        = $F('text_database_firestats_user');
    var pass        = $F('text_database_firestats_pass');
    var host        = $F('text_database_host');
    var admin_user  = $F('text_database_user');
    var admin_pass  = $F('text_database_pass');
    var dbname      = $F('text_database_name');
    var prefix      = $F('text_database_prefix');
    var params      =   'action=createNewDatabase&host=' + host +
                        "&user=" + user + "&pass=" + pass +
                        "&dbname=" + dbname + "&table_prefix="+prefix +
                        "&admin_user=" + admin_user + "&admin_pass=" + admin_pass;
    sendRequest(params);

}

function useWordpressDB()
{
    var params = 'action=useWordpressDB';
    sendRequest(params);
}

function upgradeDatabase()
{
	$('upgrade_db').disabled = true;
	$('upgrade_db').innerHTML = '<?php fs_e('Upgrading, do not interrupt')?>';
    var params = 'action=upgradeDatabase';
    sendRequest(params, function(response)
    {
    	if (response.status == 'error')
    	{
    		$('upgrade_db').disabled = false;
   			$('upgrade_db').innerHTML = '<?php fs_e('Upgrade')?>';
    	}
    });
}
