<?php
// don't handle error in ajax callback handler
if (defined('FS_AJAX_HANDLER')) return;

$fs_error_types = array
(
	E_ERROR          => 'error',
	E_WARNING        => 'warning',
	E_PARSE          => 'parsing error',
	E_NOTICE          => 'notice',
	E_CORE_ERROR      => 'core error',
	E_CORE_WARNING    => 'core warning',
	E_COMPILE_ERROR  => 'compile error',
	E_COMPILE_WARNING => 'compile warning',
	E_USER_ERROR      => 'user error',
	E_USER_WARNING    => 'user warning',
	E_USER_NOTICE    => 'user notice'
);

if(defined('E_STRICT')) $fs_errortypes[E_STRICT] = 'runtime notice';

$GLOBALS['fs_errortypes'] = $fs_error_types;


/*
$GLOBALS['fs_last_error_id'] = 0;
function fs_on_error($errno, $errstr, $errfile, $errline) // resume next!
{	
	global $fs_error_types;
	$err_id = $GLOBALS['fs_last_error_id'];
	$GLOBALS['fs_last_error_id']++;

	$ts = date("H:m:s");
	$type = $fs_error_types[$errno];
	if ($err_id == 0) // first error
	{?>
<script type='text/javascript'>
function appendError(error)
{
	try
	{
		var err = document.createElement('div');
		err.innerHTML = stripslashes(error);
		document.getElementById('php_errors').appendChild(err);
	}
	catch (e)
	{
	}
}
</script>
<div id='php_errors' class='php_error'><div>PHP Error(s)</div></div>";
	<?php
	}
	
	$err = "$ts: $type ($errno) at $errfile (#$errline) : $errstr";
	$err .= fs_get_backtrace();
	$err = str_replace("\n","<br/>",$err);
	$err = addslashes($err);
?>

<script type='text/javascript'>
	eval("var txt = '<?php echo $err?>'");
	appendError(txt);
</script>"
<?php

}
*/




if (file_exists(FS_ABS_PATH.'/dev'))
{
	set_error_handler('fs_on_error');
	error_reporting(E_ALL | (defined('E_STRICT')? E_STRICT : 0));
}



function fs_on_error($errno, $errstr, $errfile, $errline) // resume next!
{
	global $fs_error_types;

	$ts = date("H:m:s");
	$type = $fs_error_types[$errno];
	echo "$ts: $type ($errno) at $errfile (#$errline) : $errstr";

	echo fs_get_backtrace();
}

function fs_get_backtrace()
{
	$s = '';
	if (PHPVERSION() >= 4.3) {

		$MAXSTRLEN = 64;

		$s = '<pre align=left>';
		$traceArr = debug_backtrace();
		array_shift($traceArr);
		array_shift($traceArr);
		$tabs = sizeof($traceArr)-1;
		foreach ($traceArr as $arr) {
			//for ($i=0; $i < $tabs; $i++) $s .= '&nbsp;';
			$s .="&nbsp;&nbsp;";
			$tabs -= 1;
			$s .= '<font face="Courier New,Courier">';
			if (isset($arr['class'])) $s .= $arr['class'].'.';
			/*
			foreach($arr['args'] as $v) {
				if (is_null($v)) $args[] = 'null';
				else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
				else if (is_object($v)) $args[] = 'Object:'.get_class($v);
				else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
				else {
					$v = (string) @$v;
					$str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
					if (strlen($v) > $MAXSTRLEN) $str .= '...';
					$args[] = $str;
				}
			}
			*/
			$line = isset($arr['line']) ? $arr['line'] : '?';
			$file = isset($arr['file']) ? $arr['file'] : '';
			$args = array();
			$s .= $arr['function'].'('.implode(', ',$args).')';
			if (!empty($file))
			{
				$s .= sprintf("</font><font color=#808080 size=-1> # line %4d,".
						" file: <a href=\"file:/%s\">%s</a></font>",
						$line,$file,$file);
			}
			else
			{
				$s .=sprintf("</font><font color=#808080 size=-1> # line %4d,".
						" unknown file</font>",
						$line); 
			}
			$s .= "\n";
		}   
		$s .= '</pre>';
	}
	return $s;
}
?>
