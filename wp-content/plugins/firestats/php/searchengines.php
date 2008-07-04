<?php
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/utils.php');

/**
 * returns the search engine definition.
 */
function &fs_get_search_engines($get_hash = false)
{
	/**
	 * maps id to search engine
	 */
	static $search_engine_ht;
	
	/**
	 * array
	 */
	static $search_engines_arr;
	if (!isset($search_engines_arr))
	{
		$search_engine_ht = array();
		$engines_table = array();	
		// Note: users are encouraged NOT to add new search engines to prevent 
		// conflicts with future FireStats versions.
		// if you want a new search engine, open a support request on the site.
		//
		// To add a new search engine, add it in both arrays.
		// If you added a new engine, please open an enhancement request
		// for it (http://firestats.cc/newticket) with the information and logo icon.
		//
		// IMPORTANT: DO NOT MODIFY THE ARRAY ORDER. it gets into the database.
		// Add new elements at the end
		$engines = array
		(
			array("Google","google.png"),
			array("MSN","msn.png"),
			array("altavista","altavista.png"),
			array("Ask","ask.png"),
			array("Exite","exite.png"),
			array("Alexa","alexa.png"),
			array("Walla","walla.png"),
			array("Yahoo","yahoo.png"),
			array("AOL","aol.png"),
			array("Baidu","baidu.png"),
			array("Lycos","lycos.png"),
			array("HotBot",null),
			array("About","about.png"),
			array("Seznam","seznam.png"),
			array("Atlas","atlas.cz.png"),
			array("Centrum","centrum.cz.png"),
		); 
		
	
		/**
		 * search engines on the top of the list are recognized first.
		 * this is important for both correct recognition and performance.
		 */
		$engine_conf = array
		(
			array('Google','images.google','','fs_google_images_parser'),
			array('Google','google','q'),
			array('MSN','msn','q'),
			array('altavista','altavista','q'),
			array('Ask','ask.com','q'),
			array('Exite','exite','q'),
			array('Alexa','alexa','q'),
			array('Walla','search.walla.co.il','q'),
			array('Yahoo','yahoo','p'),
			array('AOL','aolsearch','query'),
			array('AOL','search.aol','query'),
			array('Baidu','baidu.com','wd'),
			array('Lycos','search.lycos.com','query'),
			array('HotBot','hotbot.com','query'),
			array('About','search.about.com','terms'),
			array('MSN','live.com','q'),
			array('Seznam','seznam.cz','q'),
			array('Atlas','atlas.cz','q'),
			array('Centrum','search.centrum.cz','q'),
		);
		
		foreach($engines as $engine)
		{
			fs_create_search_engine($search_engine_ht, $engines_table, $engine[0],$engine[1]);
		}			
		
		$search_engines_arr = array();
		foreach($engine_conf as $conf)
		{
			fs_create_engine_conf($engines_table,$search_engines_arr,$conf[0],$conf[1],$conf[2], isset($conf[3]) ? $conf[3] : null);
		}
	}
	if ($get_hash) return $search_engine_ht;
	return $search_engines_arr;
}

/**
 * Parser specific to google images urls
 */
function fs_google_images_parser($ref, $engine)
{
	$p = @parse_url($ref);
	$vars = array();
	parse_str($p['query'], $vars);
	if (isset($vars['prev']))
	{
		$prev = $vars['prev'];
		$p = @parse_url($prev);
		if (isset($p['query']))
		{
			parse_str($p['query'], $vars);
			if (isset($vars['q']))
			{
				$res = $vars['q'];
				return $res;
			}
		}
	}
	
	return false;
}

function fs_get_search_terms_and_engine($ref,&$url_breakdown)
{
	$engine = null;
	$terms = fs_get_search_terms3($ref, $url_breakdown, $engine);
	if ($terms)
	{
		$res = new stdClass();
		$res->engine_id = $engine->id;
		$res->search_terms = $terms;
		return $res;
	}
	else
		return false;
}

function fs_get_search_terms($ref)
{
	$url_breakdown = null;
	$engine = null;
	return fs_get_search_terms3($ref, $url_breakdown, $engine);
}

function fs_get_search_terms3($ref,&$url_breakdown, &$engine)
{
	$p = @parse_url($ref);
	$url_breakdown = $p;
	if (!$p) return false;
	if (!isset($p['host'])) return false;
	
	$engine = fs_find_matching_engine($p['host']);
	if ($engine === false) return false;
	if ($engine->parse_function == null)
	{
		$vars = array();
		if (!isset($p['query'])) return false;
		parse_str($p['query'], $vars);
		if (isset($vars[$engine->query]))
		{
			// if there are no earch terms don't record this as a search engine hit.
			// chances are its just a spam bot looking for some love.
			$terms = $vars[$engine->query];
			if (!empty($terms)) 
			{
				return $terms;
			}
		}
		return false;
	}
	else
	{
		$func = $engine->parse_function;
		$res = $func($ref, $engine);
		return $res;
	}
}

function fs_find_matching_engine($ref)
{
	$engines = fs_get_search_engines();
	foreach($engines as $e)
	{
		if (strpos($ref, $e->pattern) !== false) return $e;
	}
	return false;
}

function fs_create_engine_conf($engines_table, &$search_engines_arr,$name, $pattern, $query, $parse_function)
{
	$engine = $engines_table[$name];
	if ($engine == null) die("Unknown search engine " .$name);
	
	$conf = new stdClass();
	$conf->id = $engine->id;
	$conf->name = $engine->name;
	$conf->logo_icon = $engine->logo_icon;
	$conf->pattern = $pattern;
	$conf->query = $query;
	$conf->parse_function = $parse_function;
	$search_engines_arr[] = $conf;
}

function fs_create_search_engine(&$search_engine_ht,&$engines, $name, $logo_icon)
{
	static $id = 1;
	$engine = new stdClass();
	$engine->id = $id;
	$engine->name = $name;
	$engine->logo_icon = $logo_icon;
	$search_engine_ht[$engine->id] = $engine;
	$engines[$name] = $engine;
	$id++;
}

function fs_recalculate_search_engine_terms_calc_max()
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$count = $fsdb->get_var("SELECT COUNT(*) c FROM `$urls`");
	if ($count === null)
	{
		return fs_db_error();
	}
	else
	{
		return $count;
	}	
}

function fs_recalculate_search_engine_terms($value, $chunk = 1000)
{
	require_once(FS_ABS_PATH.'/php/db-common.php');
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	if ($value == 0)
	{
		if (false === $fsdb->get_results("UPDATE `$urls` SET `search_engine_id` = NULL, `search_terms` = NULL"))
		{
			return fs_db_error();
		}
	}
	
	$value = $fsdb->escape($value);
	$res = $fsdb->get_results("SELECT id,url from $urls LIMIT $chunk OFFSET $value");
	if ($res === false)
	{
		return fs_db_error();
	}
	
	if (count($res) > 0)
	{
		foreach($res as $r)
		{
			$id = $r->id;
			$ref = $r->url;
			$engine = null;
			$p = array();
			$terms = fs_get_search_terms3($ref, $p, $engine);
			if ($terms !== false && $terms != '')
			{
				$terms = $fsdb->escape($terms);
				$r2 = $fsdb->query("UPDATE `$urls` SET `search_engine_id`='$engine->id', `search_terms` = $terms WHERE `id` = '$id'");
				if ($r2 === false)
				{
					return fs_db_error();
				}
			}
		}
	}
	return count($res);	
}

function fs_recalculate_all_search_engine_terms($value)
{
	$total = fs_recalculate_search_engine_terms_calc_max();
	$done = 0;
	while ($done < $total)
	{
		$res = fs_recalculate_search_engine_terms($done, 5000);
		if (is_string($res)) return $res;
		$done += $res;
	}
	return true;
}
?>
