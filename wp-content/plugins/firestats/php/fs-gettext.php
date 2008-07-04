<?php

function fs_get_lang_name($filename)
{
	if ($filename == null) return;
	$lines = file($filename);
	$n = count($lines);
	for($i=2;$i<$n;$i++)
	{
		$line = $lines[$i];
		// DHA: damn hack alert
		if (eregi('\"X-Poedit-Language:',$line))
		{
			$r = trim(substr($line, 20));
			$r = substr($r,0,strlen($r) - 3);
			return $r;
		}
	}
	return $filename; // if lang name is not defined use filename
}

// parses po files (that's right, not the horrid mo files).
class fs_gettext
{
	var $m_translation;

	function fs_gettext($filename = null)
	{
		if ($filename == null) return;
		if (!file_exists($filename)) return;
		$lines = file($filename);
		$n = count($lines);
		$key = '';
		$value = '';
		for($i=0;$i<$n;$i++)
		{
			$line = $lines[$i];
			if (eregi('msgid \".*\"',$line))
			{
				$key = trim(substr($line,5));
				$key = trim($key,'"');
			}
			else
			if (eregi('msgstr \".*\"',$line))
			{
				$value = trim(substr($line,6));
				$value = trim($value,'"');
				$this->m_translation[$key] = $value;
			}
		}
	}

	function get($key)
	{
		if (isset($this->m_translation))
		{
			$value = isset($this->m_translation[$key]) ? $this->m_translation[$key] : $key;
			if (isset($value)) return $value;
		}
		return $key;
	}
}



?>
