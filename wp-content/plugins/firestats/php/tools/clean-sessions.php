<?php
$enabled = false;

if (!$enabled)
{
	echo "This script will delete the sessions data created by FireStats<br/>";
	echo "To use it, edit it and set <b>\$enabled</b> to true<br/>";
	echo "Then call it with your browser (IE: <b>http://server.com/firestats/php/tools/clean-sessions.php</b>)<br/>";
	echo "Don't forget to turn <b>\$enabled</b> to false once you are done.<br/>";
	return;
}

$session_path = dirname(dirname(dirname(__FILE__)))."/fs_sessions";
if (!is_dir($session_path))
{
	echo "Sessions directory <b>$session_path</b> does not exist<br/>";
	return;
}
echo "Deleting sessions directory : ".$session_path ."<br/>";

$ok = removeDir($session_path);
if ($ok)
{
	echo "Success<br/>";
}
else
{
	echo "Failed<br/>";
}

function removeDir($path) {
    // Add trailing slash to $path if one is not there
    if (substr($path, -1, 1) != "/") {
        $path .= "/";
    }
    $files = glob($path . "*");
    if (count($files) > 0)
    {
	    foreach ($files as $file) 
	    {
	        if (is_file($file) === true) 
	        {
	            // Remove each file in this Directory
	            if (unlink($file) === false) return false;
	        }
	        else if (is_dir($file) === true) 
	        {
	            // If this Directory contains a Subdirectory, run this Function on it
	            if (removeDir($file) == false) return false;
	        }
	    }
    }
    // Remove Directory once Files have been removed (If Exists)
    if (is_dir($path) === true) {
        if (rmdir($path) == false) return false;
    }
    
    return true;
}
?>