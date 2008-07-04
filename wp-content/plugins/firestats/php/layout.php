<?php
require_once(dirname(__FILE__).'/init.php');
// horizontal begin, left for ltr layout and right for rtl layout
function H_BEGIN()
{
	if (fs_lang_dir() == 'rtl') echo "right";
	else echo "left";
}

function H_END()
{
	if (fs_lang_dir() == 'rtl') echo "left";
	else echo "right";
}


?>
