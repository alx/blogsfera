<?php
/*
Plugin Name: MuSearch
Plugin URI: http://wpmudev.org
Description: Another search solution for WordpressMU
Author: Alexandre Girard
Version: 0.1
Author URI: http://blog.alexgirard.com
License URI: http://www.gnu.org/licenses/gpl.html
*/ 
/*  Mostly Inspired by: MuTags - Henri Simonen

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    0.1 - Original version
*/





if (!class_exists('Mu_search')) {
    class Mu_search	{
	
		var $SearchOptName = "Mu_search_options";
	    var $version = "0.1";
	
		var $per_page = 10;
		var $current_page = 1;
		var $page_count = 1;
		
	    function Mu_search(){
			add_action('template_redirect', array(&$this, 'TemplateRedirect'));
			$this->SearchOpt = $this->getSearchOpt();
	    }


/******************************************************************
 * Method:  getTagOpt() 
 * Purpose: Gets options from site_meta.
 *****************************************************************/
	    function getSearchOpt() {				
			//Default options array here
			$SearchOpt = array();
				
			//*****************************************************************************************
			$savedOptions = get_site_option($this->SearchOptName);
			if (!empty($savedOptions)) {
				foreach ($savedOptions as $key => $option) {
					$SearchOpt["$key"] = $option;
				}
			}
			update_site_option($this->SearchOptName, $SearchOpt); 
			return $SearchOpt;
		}


		function saveSearchOpt(){
			update_site_option($this->SearchOptName, $this->SearchOpt);		
		}

/******************************************************************
 * Method:  MuSearchVersion() 
 * Purpose: Checks this plugin version
 *****************************************************************/			
			function MuSearchVersion() {
				if(empty($this->SearchOpt['version'])) {					
					$SearchOpt['version'] = $this->version;
					update_site_option($this->SearchOptName, $SearchOpt);
				}
			}

/******************************************************************
 * Method: get_post_data() 
 * Purpose: Just a tiny helper...
*******************************************************************/
		function get_post_data($post_ID) {
		global $wpdb;
		$data = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID = $post_ID");
		return $data;
		}

/******************************************************************
 * Method:  is_public() 
 * Purpose: Checks if the blog is public.
 *****************************************************************/
		function is_public($blog_id) {			
			global $wpdb;
			
			$check = $wpdb->get_row("SELECT public FROM $wpdb->blogs WHERE blog_id = $blog_id");			
			if($check->public != 1) {				
				return false;			
			}			
			else {			
				return true;			
			}
		}
		
		function get_users_blog($user_list){
			$blog_list = array();
			
			foreach ($user_list as $user) {
				$blog = get_active_blog_for_user($user['ID']);
				$blog->userblog_id = $user['ID'];
				$blog->userblog_name = $user['display_name'];
				//error_log("$blog->userblog_id - $blog->userblog_name");
				$blog_list[] = $blog;
			}
				
			reset($blog_list);	
			// foreach ($blog_list as $blog) {
			// 	error_log("$blog->userblog_id - $blog->userblog_name");
			// }
			
			return $blog_list;
		}
		
		
		
		function search_blog($term){
			global $wpdb;
			$reults_blogs = array();
			$blogs_ids = $wpdb->get_col("SELECT blog_id FROM wp_blogs");
			
			foreach($blogs_ids as $blog_id){
				$blog_name = $wpdb->get_var("SELECT option_value FROM wp_".$blog_id."_options WHERE option_name='blogname'");
				similar_text(strtolower($blog_name), strtolower($term), $p);
				if ($p>75){
					array_push($reults_blogs, $blog_id);
				}
				
			}
			return $reults_blogs;
		/*	$blog_results = $wpdb->get_results(
				"SELECT blog_id, {$wpdb->blogs}.domain, {$wpdb->blogs}.path, registered, last_updated
				FROM {$wpdb->blogs}, {$wpdb->site}
				WHERE site_id = '{$wpdb->siteid}'
				AND {$wpdb->blogs}.site_id = {$wpdb->site}.id
				AND ( {$wpdb->blogs}.domain LIKE '%{$term}%' OR {$wpdb->blogs}.path LIKE '%{$term}%' );", ARRAY_A);*/

		}
		
		function search_by_name($term){
			global $wpdb;
			
			$table_users 	= "wp_users";

			$user_results = $wpdb->get_results("SELECT ID, display_name FROM $table_users
			WHERE ($table_users.user_nicename LIKE '%$term%' 
			OR $table_users.user_email LIKE '%$term%' 
			OR $table_users.user_login LIKE '%$term%' 
			OR $table_users.display_name LIKE '%$term%') 
			AND $table_users.spam = 0 AND $table_users.deleted = 0", ARRAY_A);

			return $user_results;
		}

		function search_by_country($term){
			$query = array(array("key" => 'pais', "value" => $term));
			return $this->search_user_meta($query);
		}

		function search_by_area($term){
			
			// begin query with area
			$query = array(array("key" => 'area', "value" => $term));
			
			// Get unidad
			$unidad = addslashes($_POST["unidad"]);
			
			if(strcmp($unidad, "all") != 0) {
				$query[] = array("key" => 'unidad', "value" => $unidad);
				reset($query);
			}
			
			return $this->search_user_meta($query);
		}
		
		function search_user_meta($query_array){
			global $wpdb;
			
			$sql_count = $this->build_sql($query_array, $count = true);
			
			$this->page_count = ceil($wpdb->get_row($sql_count) / $this->per_page);
			
			$sql = $this->build_sql($query_array);
			
			return $wpdb->get_results($sql, ARRAY_A);
		}
		
		function build_sql($query_array, $count = false){
			
			$table_users 	= "wp_users";
			$table_meta 	= "wp_usermeta";
			
			if($count == true){
				$sql = "SELECT COUNT(*) ";
			} else  {
				$sql = "SELECT DISTINCT users.ID, users.display_name ";
			}
			
			$sql .= "FROM $table_users as users ";
			$sql .= "LEFT JOIN $table_meta as meta ";
			$sql .= "ON users.ID = meta.user_id WHERE users.spam = 0 AND users.deleted = 0 ";

			// Make subquery for each query key/value
			foreach ($query_array as $query) {
				extract($query);
				$sql .= "AND users.ID IN (";
				$sql .= "SELECT meta.user_id ";
				$sql .= "FROM $table_meta as meta ";
				$sql .= "WHERE meta.meta_key LIKE '$key' ";
				$sql .= "AND meta.meta_value LIKE '$value' ";
				$sql .= ") ";
			}
			
			if($count == false){
				// Only fetch necessary tuples for pagination
				$sql .= "LIMIT ".$this->per_page." OFFSET ".(($this->current_page - 1) * $this->per_page);
			}
			
			return $sql;
		}

/******************************************************************
 * Method:  get_search_results() 
 * Purpose: Returns results for current search
*****************************************************************/
function get_search_results($args = '') {
	global $wpdb;
	$url = $_SERVER['REQUEST_URI'];
	
	$type = $_POST['type'];
	
	$search_term = addslashes($_POST['params']);
		
	if($showsearch == true) {
		$search_term = urldecode($search_term);
		$search_term = str_replace("-", " ", $search_term);
		return $search_term;
	}		

	$results = wp_cache_get('search_results_'.$type.'_'.$search_term.'');		
		
	if($results == false) {
		switch ($type) {
		case 'name':
			$results = $this->search_by_name($search_term);
		    break;
		case 'country':
			$results = $this->search_by_country($search_term);
		    break;
		case 'area':
			$results = $this->search_by_area($search_term);
		    break;
		case 'blog':
			$results = $this->search_blog($search_term);
		    break;
		}
				
		wp_cache_set('search_results_'.$type.'_'.$search_term.'', $results);
	}
	
	
	return $results;
}

function get_search_type() {
	return $_POST['type'];
}

/****************************************************************
 * Method:  setup() 
 * Purpose: creates our global_tags table if needed.
*****************************************************************/
	function setup() {
			$this->MuSearchVersion(); //Checks the version and makes some changes
			$this->saveSearchOpt();
	}
	
/******************************************************************
 * Method:  TemplateRedirect() 
 * Purpose: Redirects to the search results template if searchs are found
 *****************************************************************/

function TemplateRedirect(){
	global $blog_id;

	$url = $_SERVER['REQUEST_URI'];

	$search_found = FALSE;

	if(preg_match("'search/(.*)+'", $url)) 
		$search_found = TRUE;
	

	if($blog_id == '1') {
		if ($search_found) {
		include(TEMPLATEPATH . '/search_results.php');
		//it is essential to include the exit. This prevents more than one template file being included
		exit;
		}
	}
}



    }
}

//instantiate the class
if (class_exists('Mu_search')) {
	$Mu_search = new Mu_search();	
}

function search_results() {
	global $Mu_search;
	return $Mu_search->get_search_results();
}

function search_type() {
	global $Mu_search;
	return $Mu_search->get_search_type();
}

function current_search() {
	echo str_replace("_", " ", urldecode($_POST['params']));
}

?>
