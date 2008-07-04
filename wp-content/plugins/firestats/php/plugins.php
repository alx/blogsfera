<?php

init_plugins();

function fs_add_action($action_type, $callback)
{
    $actions = &fs_get_actions();
    if (isset($actions[$action_type]))
    {
        $list = &$actions[$action_type];
    }
    else
    {
        $list = array();
    }
    $data['context'] = fs_pcontext();
    $data['callback'] = $callback;
    $list[] = $data;
    $actions[$action_type] = $list;
}

function &fs_get_actions()
{
    global $actions;
    if (!isset($actions)) $actions = array();
    return $actions;
}

function init_plugins()
{
    $dir = FS_ABS_PATH."/plugins";
    if (@file_exists($dir))
    {
	    $dh  = opendir($dir);
	    $list = array();
	    while (false !== ($filename = readdir($dh)))
	    {
	        if ($filename == "." || $filename == "..") continue;
	        $r = sscanf($filename,"%s.php");
	        // set current plugin id.
	        $plugin_id = $r[0];
	        push_context($plugin_id);
	        // initialize plugin
	        include($dir."/".$filename);
	        pop_context();
	    }
    }
}


function fs_do_action($type, $args = null)
{
    $actions = &fs_get_actions();
    if (isset($actions[$type]))
    {
        $list = $actions[$type];
        foreach($list as $data)
        {
            $plugin_id = $data['context'];
            fs_push_pcontext($plugin_id);
            $callback = $data['callback'];
            if ($args != null)
            {
            	$callback($args);
            }
            else
            {
	            $callback();
            }
            fs_pop_pcontext();
        }
    }
}

function fs_push_pcontext($context)
{
    global $context_stack;
    if (!isset($context_stack)) $context_stack = array();
    array_push($context_stack,$context);
}

function fs_pop_pcontext()
{
    global $context_stack;
    return array_pop($context_stack);
}

function fs_pcontext()
{
    global $context_stack;
    return $context_stack[count($context_stack)-1];
}

function fs_dump_actions()
{
	$actions = &fs_get_actions();
	echo "<pre>".var_export($actions,true)."</pre>";
}
?>
