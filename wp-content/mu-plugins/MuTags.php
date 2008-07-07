<?php
/*
Plugin Name: MuTags
Plugin URI: http://wpmudev.org
Description: Another tag cloud solution for WordpressMU
Author: Henri Simonen
Version: 0.1
Author URI: http://betraise.org
License URI: http://www.gnu.org/licenses/gpl.html
*/ 
/*  Copyright 2007    Henri Simonen

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
    0.1b - Fixed a bug
    0.2 - Fixed bugs, added new options, added better language support
    0.2b - Added new tag sort options and support for multiple custom tag clouds
*/





if (!class_exists('Mu_tags')) {
    class Mu_tags	{
	

	    //this sets the name that will be used to save and retrive your options. This needs to be unique
	    var $TagOptName = "Mu_tags_options";
	    var $version = "0.2";
	    var $dbtable;
		
	    function Mu_tags(){
		global $wpmuBaseTablePrefix;
		add_action('template_redirect', array(&$this, 'TemplateRedirect'));		
		add_action('wp_insert_post', array(&$this, 'mu_insert_post'));
		add_action('delete_post', array(&$this, 'mu_delete_post'));
		add_action('admin_menu', array(&$this,'add_admin_pages'));

		$this->TagOpt = $this->getTagOpt();	
		$this->dbtable = "".$wpmuBaseTablePrefix."global_tags";
 	
	    }


/******************************************************************
 * Method:  getTagOpt() 
 * Purpose: Gets options from site_meta.
 *****************************************************************/
	    		function getTagOpt() {				
				//Default options array here
				$TagOpt = array(
					"tag_base" => "tag",
					"results_template" => "tag_results",
					"record_cats" => "0",
					"record_empty" => "0",
					"limit_tags" => "100",	
					"exclude" => "",
					"bexclude" => "", //exclude blogs (stupid name)	
					"daysback" => "365",			
					"tag_min_size" => "100",				
					"tag_max_size" => "350",
					"tag_min_color" => "#999999",
					"tag_max_color" => "#000000",
					"tag_size_format" => "%",
					"tag_link_title" => "%amount% things tagged with %tag%",
					"tag_sort_format" => "wd",
					"include_cats" => "0");
				
		//*****************************************************************************************
		$savedOptions = get_site_option($this->TagOptName);
		if (!empty($savedOptions)) {
			foreach ($savedOptions as $key => $option) {
				$TagOpt["$key"] = $option;
			}
		}
		update_site_option($this->TagOptName, $TagOpt); 
		return $TagOpt;
		}


		function saveTagOpt(){
			update_site_option($this->TagOptName, $this->TagOpt);		
		}


		function add_admin_pages(){
		add_submenu_page("wpmu-admin.php","Mu Tags","Mu Tags",10, __FILE__, array(&$this,"AdminMenuOutput"));
		}

/******************************************************************
 * Method:  MuTagsVersion() 
 * Purpose: Checks this plugin version and makes changes to the db
 *****************************************************************/			
			function MuTagsVersion() {
				global $wpdb;			
				if(empty($this->TagOpt['version'])) {					
	
					$wpdb->query("ALTER TABLE $this->dbtable
						ADD taxonomy tinyint(1) unsigned 
						NOT NULL default '0'");

					$TagOpt['version'] = $this->version;									  update_site_option($this->TagOptName, $TagOpt);
				}
			}
		 

/******************************************************************
 * Method:  AdminMenuOutput() 
 * Purpose: Outputs the adminmenu.
 *****************************************************************/

		function AdminMenuOutput(){
		?>
<?php if($_POST['import_tags'] || $_POST['import_cats']) { ?>
<div id="message" class="updated fade">
<p><strong>Tags imported</strong><p>
</div>
<?php } ?>
<?php if($_POST['tag_options_submit']) { ?>
<div id="message" class="updated fade">
<p><strong>Options Updated</strong><p>
</div>
<?php } ?>
<?php if($_POST['createdb'] || $_POST['recreatedb']){ ?>
<div id="message" class="updated fade">
<p><strong>Tables created</strong><p>
</div>
<?php } ?>
<?php if($_POST['delete_categories_submit'] || $_POST['delete_tags_submit'] || $_POST['delete_term_submit']) { ?>
<div id="message" class="updated fade">
<p><strong>Tag(s) deleted</strong><p>
</div>
<?php } ?>


<div class="wrap">
<h2>MuTags Options</h2>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="text-align: right;">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but11.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
</form>
<form method="post" name="options" action="<?php $this->SaveNewValues();?>">
<?php if(!empty($this->TagOpt['dbcreated'])) : ?>
<p class="submit"><input type="submit" name="tag_options_submit" value="Update Options &raquo;" /></p>

<h3>General</h3>
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
<tr valign="top">
<th width="33%" scope="row"> Tag base:</th>
<td><input name="tag_base" type="text" id="" value="<?php echo $this->TagOpt['tag_base'];?>" size="2" style="width: 16.5em; " /> This needs to be the slug of an actual page.
</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Tag results template:</th>
<td><input name="results_template" type="text" id="" value="<?php echo $this->TagOpt['results_template'];?>" size="2" style="width: 16.5em; " />
.php (The actual filename without ".php")</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Record categories:</th>
<td><input name="record_cats" type="checkbox" value="1" <?php if($this->TagOpt['record_cats'] == "1") { echo 'checked="checked"';}?> />
Do you want post categories recorded into the MuTags table, when a post is posted?</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Record empty posts:</th>
<td><input name="record_empty" type="checkbox" value="1" <?php if($this->TagOpt['record_empty'] == "1") { echo 'checked="checked"';}?>/>
Insert posts with no tags or categories in the MuTags -table?</td>
</tr>
</table>
<h3>Tag Cloud</h3>
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
<tr valign="top">
<th width="33%" scope="row"> Exclude tags:</th>
<td><input name="exclude" type="text" id="" value="<?php echo $this->TagOpt['exclude'];?>" size="2" style="width: 16.5em; " />
Comma separated list of Term id's (13,29,14)
</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Exclude blogs:</th>
<td><input name="bexclude" type="text" id="" value="<?php echo $this->TagOpt['bexclude'];?>" size="2" style="width: 16.5em; " />
Comma separated list of blog id's (1,2,3)
</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Show tags from last:</th>
<td><input name="daysback" type="text" id="" value="<?php echo $this->TagOpt['daysback'];?>" size="2" style="width: 6em; " />
days (Leave empty for all tags)
</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Limit tags in the tag cloud:</th>
<td><input name="limit_tags" type="text" id="" value="<?php echo $this->TagOpt['limit_tags'];?>" size="2" style="width: 6em; " />
tags (Leave empty for all tags)</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Smallest tag size:</th>
<td><input name="tag_min_size" type="text" id="" value="<?php echo $this->TagOpt['tag_min_size'];?>" size="2" style="width: 6em; " />
</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Biggest tag size:</th>
<td><input name="tag_max_size" type="text" id="" value="<?php echo $this->TagOpt['tag_max_size'];?>" size="2" style="width: 6em; " />
</td>
</tr>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Smallest tag color:</th>
<td><input name="tag_min_color" type="text" id="" value="<?php echo $this->TagOpt['tag_min_color'];?>" size="2" style="width: 6em; " />
</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Biggest tag color:</th>
<td><input name="tag_max_color" type="text" id="" value="<?php echo $this->TagOpt['tag_max_color'];?>" size="2" style="width: 6em; " />
</td>
</tr>
<tr valign="top">
<th scope="row"> Tag size in:</th>
<td><select name="tag_size_format">
<option value='%'  <?php if($this->TagOpt['tag_size_format'] == "%") {
	echo 'selected="selected"';
}?>>%</option>
<option value='px' <?php if($this->TagOpt['tag_size_format'] == "px") {
	echo 'selected="selected"';
}?> >px</option>

	<option value='em' <?php if($this->TagOpt['tag_size_format'] == "em") {
	echo 'selected="selected"';
}?>>em</option>
	</select></td>
</tr>

<th scope="row"> Sort tags by:</th>
<td><select name="tag_sort_format" style="width: 16.5em;">

<option value='rand' <?php if($this->TagOpt['tag_sort_format'] == "rand") {
	echo 'selected="selected"';
}?> >Random</option>

	<option value='wd' <?php if($this->TagOpt['tag_sort_format'] == "wd") {
	echo 'selected="selected"';
}?>>Weight Desc</option>
	<option value='wa'  <?php if($this->TagOpt['tag_sort_format'] == "wa") {
	echo 'selected="selected"';
	}?>>Weight Asc</option>
	<option value='nd'  <?php if($this->TagOpt['tag_sort_format'] == "nd") {
	echo 'selected="selected"';
}?>>Name Desc</option>
	<option value='na'  <?php if($this->TagOpt['tag_sort_format'] == "na") {
	echo 'selected="selected"';
}?>>Name Asc</option>
</select></td>
</tr>

<tr valign="top">
<th width="33%" scope="row"> What to show in tag link title:</th>
<td><input name="tag_link_title" type="text" id="" value="<?php echo $this->TagOpt['tag_link_title'];?>" size="2" style="width: 22.5em; " /> (%amount% things tagged with %tag%)
</td>
</tr>

<tr valign="top">
<th width="33%" scope="row"> Tag css class:</th>
<td><input name="css_class" type="text" id="" value="<?php echo $this->TagOpt['css_class'];?>" size="2" style="width: 16.5em; " />
</td>
</tr>
<tr valign="top">
<th width="33%" scope="row"> Include categories in tag cloud:</th>
<td><input name="include_cats" type="checkbox" value="1" <?php if($this->TagOpt['include_cats'] == "1") {echo 'checked="checked"'; } ?>/>
</td>
</tr>

</table>
<p class="submit">

<input type="submit" name="tag_options_submit" value="Update Options &raquo;" />
</p>

<h3>Import existing WordPress tags to taglist</h3>


<p>You should backup your database before trying this!</p>
<p class="submit">
<input type="submit" name="import_tags" value="Import"/>
</p>

<h3>Import existing WordPress categories to taglist</h3>


<p>You should backup your database before trying this!</p>
<p class="submit">
<input type="submit" name="import_cats" value="Import"/>
</p>

<h3>Remove tags</h3>



<p><b>Remove a tag from the MuTags -table by term id</b></p>
<p><input type="text" style="width: 6em; " name="delete_term" /></p>
<p class="submit">
<input type="submit" name="delete_term_submit" value="Remove"/>
</p>

<p><b>Remove tags older than</b></p>
<p><input type="text" style="width: 6em; " name="delete_tags" /> days.</p>
<p class="submit">
<input type="submit" name="delete_tags_submit" value="Remove"/>
</p>


<p><b>Remove categories older than</b></p>
<p><input type="text" style="width: 6em; " name="delete_cats" /> days.</p>
<p class="submit">
<input type="submit" name="delete_cats_submit" value="Remove"/>
</p>



<h3>Recreate Database Table</h3>
<p>This drops the existing table and creates a new <b>empty</b> one.</p>
<p class="submit">
<input type="submit" name="recreatedb" value="Create!" />
</p>
<?php else: ?>

	<h3>Create Database Table</h3>
<p>This message appears if you are installing or upgrading this plugin. Don't worry. Existing data in the MuTags -table wont be deleted. (Though it's allways good to backup your database before making changes to it...)</p> 
	<p class="submit">
	<input type="submit" name="createdb" value="Create!"/>
	</p>
<?php endif; ?>
</form>

			


			</div>
<?php

	
		if($_POST["import_tags"]) 
			$this->ImportWpTags();
		if($_POST["import_cats"]) 
			$this->ImportWpTags("cats");
		if($_POST["delete_term_submit"]) 
			$this->dbmaintain($_POST["delete_term"]);
		if($_POST["delete_cats_submit"])
			$this->dbmaintain("cats", $_POST["delete_cats"]);
		if($_POST["delete_tags_submit"])
			$this->dbmaintain("tags", $_POST["delete_tags"]);
		if($_POST["createdb"]) 
			$this->setup();
		if($_POST["recreatedb"]) 
			$this->setup('true');

		}

		
			


	
		function saveNewValues() {
			if($_POST['createdb']) {
				$this->TagOpt['dbcreated'] = "1";
			}

			if($_POST['tag_options_submit']) {			
				$this->TagOpt['tag_base'] = $_POST['tag_base'];			
				$this->TagOpt['results_template'] = $_POST['results_template'];			
				$this->TagOpt['record_cats'] = $_POST['record_cats'];					
				$this->TagOpt['record_empty'] = $_POST['record_empty'];			
				$this->TagOpt['limit_tags'] = $_POST['limit_tags'];				
				$this->TagOpt['tag_min_size'] = $_POST['tag_min_size'];			
				$this->TagOpt['tag_max_size'] = $_POST['tag_max_size'];			
				$this->TagOpt['tag_min_color'] = $_POST['tag_min_color'];			
				$this->TagOpt['tag_max_color'] = $_POST['tag_max_color'];			
				$this->TagOpt['tag_size_format'] = $_POST['tag_size_format'];				
				$this->TagOpt['tag_sort_format'] = $_POST['tag_sort_format'];		
				$this->TagOpt['css_class'] = $_POST['css_class'];			
				$this->TagOpt['tag_link_title'] = $_POST['tag_link_title'];
				$this->TagOpt['exclude'] = $_POST['exclude'];			
				$this->TagOpt['bexclude'] = $_POST['bexclude'];
				$this->TagOpt['daysback'] = $_POST['daysback'];
				$this->TagOpt['include_cats'] = $_POST['include_cats'];
			}
			
			$this->saveTagOpt(); 
		}
	
	
/******************************************************************
 * Method:  mu_insert_post() 
 * Purpose: This is triggered whenever a post is posted or updated.
 * This finds the post_id, blog_id, "tag_id" and post_date of the post
*******************************************************************/

		function mu_insert_post($post_ID) {
			global $wpdb, $blog_id;

			if($this->is_public($blog_id)) {
				$postdata = $this->get_post_data($post_ID);
				$post_type = $postdata->post_type;
				$post_status = $postdata->post_status;
				$post_date = $postdata->post_date;
				$post_modified = $postdata->post_modified;
			
				$update = false;

				if ($post_date != $post_modified) {
					$update = true;
				}
				
				if ($update) {
					$this->mu_delete_post($post_ID);			
				}

				if (($post_type = 'post') && ($post_status != 'draft')) {
					$tagsmap = $wpdb->get_results("SELECT term_taxonomy_id 
						FROM $wpdb->term_relationships WHERE object_id = $post_ID AND term_taxonomy_id != 1"); 

					if($tagsmap) {
					//If tags are found
						foreach ($tagsmap as $id) {							
							$term = $wpdb->get_row("SELECT term_id, taxonomy
							FROM $wpdb->term_taxonomy WHERE
							term_taxonomy_id = $id->term_taxonomy_id"); 
							if($term->taxonomy == "post_tag") {
								$taxonomy = "0";
							}
							elseif($this->TagOpt['record_cats'] == "1") {
								$taxonomy = "1";
							}
							else {
								$taxonomy = "-1";
							}
							if($taxonomy > "-1") {
							$this->mu_query($blog_id, $term->term_id, $post_date, $post_ID, $taxonomy);
							}	
						}
					}
					
					elseif($this->TagOpt['record_empty'] == "1") {
					//If the post wasn't tagged with anything we insert a 0 for the tag_id
						$this->mu_query($blog_id, "0", $post_date, $post_ID, "1");
					}
					else {
						return;
					}
				}	
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
 * Method:  mu_query() 
 * Purpose: Just handles the inserting of the data into our
 * global_tags table
*****************************************************************/

		function mu_query($blog_id, $term_id, $post_date, $post_ID, $taxonomy) {

			$sql_query = mysql_query(
				"INSERT IGNORE INTO $this->dbtable 
				(tag_id, blog_id, post_id, post_date, taxonomy)
				VALUES
				('$term_id', '$blog_id', '$post_ID', '$post_date', '$taxonomy')");

			return $sql_query;
		}

/******************************************************************
 * Method:  mu_delete_post() 
 * Purpose: Deletes the post also from our global_tags table when 
 * a user deletes a post.
*****************************************************************/

		function mu_delete_post($post_ID) {
			global $blog_id;
			$post_type = get_post_field('post_type', $post_ID);
			$post_status = get_post_field('post_status', $post_ID);

			if ($post_type = 'post') {
				$sql_query = mysql_query("DELETE FROM $this->dbtable
					WHERE post_id = $post_ID
				       	AND blog_id = $blog_id");			
			}
		}

/******************************************************************
 * Method:  is_public() 
 * Purpose: Checks if the blog is public.
 *****************************************************************/
		function is_public($blog_id) {			
			// global $wpdb;
			// 
			// $check = $wpdb->get_row("SELECT public FROM $wpdb->blogs WHERE blog_id = $blog_id");			
			// if($check->public != 1) {				
			// 	return false;			
			// }			
			// else {			
			// 	return true;			
			// }
			return true;
		}


/******************************************************************
 * Method:  get_global_tags() 
 * Purpose: Gets tags from the database.
 *****************************************************************/
		function get_global_tags($values = "", $customvals = 'false') {		     
			global $wpdb;
			if(is_array($values))
				extract($values);

			$exclusions = '';
			if ( !empty($exclude) ) {
			       	$exposts = preg_split('/[\s,]+/',$exclude);			       
				if ( count($exposts) ) {
					foreach ( $exposts as $expost ) {		      
						if (empty($exclusions))		
							$exclusions = ' tag_id <> "' . intval($expost) . '" AND ';		       
						else
					   		$exclusions .= 'tag_id <> "' . intval($expost) . '" AND ';
			    }
		    }
	    }
	    if (!empty($exclusions)) 
		    $exclusions .= '';

			$bexclusions = '';
			if ( !empty($bexclude) ) {
				$bexposts = preg_split('/[\s,]+/',$bexclude);				
				if ( count($bexposts) ) {
					foreach ( $bexposts as $bexpost ) {		      
							if (empty($bexclusions))		
							$bexclusions = ' blog_id <> "' . intval($bexpost) . '" AND ';		       
							else
							$bexclusions .= 'blog_id <> "' . intval($bexpost) . '" AND ';		       
						
			    }
		    }
	    }
	    if (!empty($bexclusions)) 
		    $bexclusions .= '';

			if($orderby == "wd") 
				$orderby = "cnnt DESC";
			if($orderby == "wa") 
				$orderby = "cnnt ASC";
			if($orderby == "rand") 
				$orderby = "rand()";
			if($orderby == "nd") 
				$orderby = "cat_name DESC";
			if($orderby == "na") 
				$orderby = "cat_name ASC";

			if($includecats == "1") {
				$includecats = "";
			}
			else {
				$includecats = "taxonomy != '1' AND";
			}

			if(!empty($limit)) {
				$limit = "LIMIT 0, $limit";
			}
			else {
				$limit = "";
			}
			if(!empty($daysback)) {
				$ANDdaysback = "AND last_updated >= DATE_SUB(CURRENT_DATE(), INTERVAL $daysback DAY)";
			}
			else {
				$ANDdaysback = "";
			}
			if($customvals == false)			
				$tag_list = wp_cache_get('mu_tag_list');
			else
				$tag_list = false;
			
			if($tag_list == false) {
				$tag_list = $wpdb->get_results("SELECT $this->dbtable.tag_id, 
					$wpdb->sitecategories.category_nicename, 
					$wpdb->sitecategories.cat_name
				       	AS tag, COUNT(ID) as cnnt ".				       
					"FROM $this->dbtable LEFT JOIN $wpdb->sitecategories ".				
					"ON $this->dbtable.tag_id = $wpdb->sitecategories.cat_ID
					WHERE $exclusions $bexclusions $includecats tag_id != '0' 
					$ANDdaysback GROUP BY tag_id ORDER BY $orderby $limit"); 

				if($customvals == false)			
					wp_cache_set('mu_tag_list', $tag_list);
			}
			return $tag_list;
		}
/******************************************************************
 * Method:  get_tag_results() 
 * Purpose: Returns results for current tag
*****************************************************************/
		function get_tag_results($showtag = false) {
			global $wpdb;
			$url = $_SERVER['REQUEST_URI'];
			$tagbase = $this->TagOpt['tag_base']; 
			$tag = preg_replace("/(.*)$tagbase\/(.*)/", "\\2", $url); 
			if(substr($tag, -1, 1) == "/") {
				$taglength = strlen($tag);
				$tag = substr($tag, 0, $taglength-1);
			}
		//	$tag = str_replace("/", "", $tag);
			//$tag = str_replace("%20", " ", $tag);
		//	$tag = urldecode($tag);
			
		/*	$trans = get_html_translation_table(HTML_ENTITIES);
			$tag = strtr($tag, $trans);*/
			//$tag = remove_accents("ייי");			
			if($showtag == true) {
				$tag = urldecode($tag);
				$tag = str_replace("-", " ", $tag);
				return $tag;
			}
			
		//$tag = remove_accents($tag);
		

			$bexclude = $this->TagOpt['bexclude'];
			$bexclusions = '';
			if ( !empty($bexclude) ) {
				$bexposts = preg_split('/[\s,]+/',$bexclude);			       
				if ( count($bexposts) ) {
					foreach ( $bexposts as $bexpost ) {							
							if (empty($bexclusions))		
							$bexclusions = ' blog_id <> "' . intval($bexpost) . '" AND ';		       
							else
					   		$bexclusions .= 'blog_id <> "' . intval($bexpost) . '" AND ';		      										      
					}			       
				}		       
			}
			
			if (!empty($bexclusions)) 			       
				$bexclusions .= '';

			$results = wp_cache_get('tag_results_'.$tag.'');		
		
			if($results == false) {
				$results = $wpdb->get_results("SELECT $this->dbtable.tag_id, 
					$this->dbtable.post_id, 
					$this->dbtable.blog_id,
					$this->dbtable.post_date, 
					$wpdb->sitecategories.cat_name ".					
					"FROM $this->dbtable LEFT JOIN $wpdb->sitecategories ".					
					"ON $this->dbtable.tag_id = $wpdb->sitecategories.cat_ID 
					WHERE $bexclusions category_nicename = '$tag' ORDER BY post_date DESC"); 
				
				wp_cache_set('tag_results_'.$tag.'', $results);
			}
			return $results;
		}

/******************************************************************
 * Method:  get_recent_posts() 
 * Purpose: Returns a list of recent posts
 *****************************************************************/
	function get_recent_posts($offset = 0, $ppp = 10) {
		global $wpdb;
		$post_list = wp_cache_get('mu_recent_posts');
		if($post_list == false) {
			$post_list = $wpdb->get_results("SELECT blog_id, post_id, post_date
				FROM $this->dbtable
				GROUP BY post_id 
				ORDER BY post_date
				DESC LIMIT $offset, $ppp"); 
			wp_cache_set('mu_recent_posts', $post_list);
		}
			if($post_list) {
				return $post_list;
			}
			else {
				return FALSE;
			}
	}

/****************************************************************
 * Method:  setup() 
 * Purpose: creates our global_tags table if needed.
*****************************************************************/
	function setup($drop = "false") {
		global $wpdb;
			if($drop == "true") {
				$drop = "DROP TABLE IF EXISTS $this->dbtable";	
			}
			$this->MuTagsVersion(); //Checks the version and makes some changes
	    		$create_table = "CREATE TABLE IF NOT EXISTS $this->dbtable (     
			id int(11) unsigned NOT NULL auto_increment,
    			tag_id int(11) unsigned NOT NULL,			    
	    		blog_id int(11) unsigned NOT NULL,	  
	    		post_id int(11) unsigned NOT NULL default '0',
			post_date datetime NOT NULL,
			taxonomy tinyint(1) unsigned NOT NULL default '0',
	    		UNIQUE KEY id (id)	 
		)";
			
			$wpdb->query($drop);
			$wpdb->query($create_table);
			$this->TagOpt['dbcreated'] = "1";
			$this->saveTagOpt();
	}

/****************************************************************
 * Method:  ImportWptags() 
 * Purpose: Imports existing Wordpress tags. (Very hazardous...)
*****************************************************************/
		function ImportWptags($type = 'tags') {
			global $wpdb, $wpmuBaseTablePrefix;

			if($type == 'tags') {
				$delete = mysql_query("DELETE FROM $this->dbtable WHERE taxonomy <> '1'");
				$taxonomy = "post_tag";
				$taxonomyid = "0";
			}
			if($type == 'cats') {
				$delete = mysql_query("DELETE FROM $this->dbtable WHERE taxonomy <> '0'");
				$taxonomy = "category";
				$taxonomyid = "1";
			}

			$blogs = $wpdb->get_results( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = '$wpdb->siteid'");

			if($blogs) {
				foreach($blogs as $blog) {
					$table_blog_posts = $wpmuBaseTablePrefix.$blog->blog_id."_posts";
					$table_term_relationships = $wpmuBaseTablePrefix.$blog->blog_id."_term_relationships";
					$table_term_taxonomy = $wpmuBaseTablePrefix.$blog->blog_id."_term_taxonomy";
					
					$results = $wpdb->get_results("SELECT ID, post_date 
						FROM $table_blog_posts 
						WHERE post_status = 'publish' 
						AND post_type = 'post'");
					if(!$results) {
						// Can be empty blog
						//die('There was an error');
					}
					
					foreach ($results as $result) {
					
						$term_ids = $wpdb->get_results("SELECT $table_term_relationships.term_taxonomy_id,
								$table_term_taxonomy.term_id ".	
								"FROM $table_term_relationships LEFT JOIN $table_term_taxonomy ".		
								"ON $table_term_relationships.term_taxonomy_id = $table_term_taxonomy.term_taxonomy_id	
								WHERE object_id = $result->ID 
								AND object_id != '1' 
								AND taxonomy = '$taxonomy'"); 
					
					
						foreach($term_ids as $term) {
							$tag_id = $term->term_id;
							if(!empty($tag_id)) {
								$this->mu_query($blog->blog_id, $tag_id, $result->post_date, $result->ID, $taxonomyid);
							}
						}
					
						

					}
						
				}
			
			}
			if($_POST['import_tags']) {
				return TRUE;
			}
		}

/****************************************************************
 * Method:  dbmaintain() 
 * Purpose: Removes data from the database.
*****************************************************************/

		function dbmaintain($term = "", $daysback = "") {
			global $wpdb;
			if(!empty($term)) {
				//	"last_updated >= DATE_SUB(CURRENT_DATE(), INTERVAL $daysback DAY)"
				if($term == "tags") {
					$todelete = "taxonomy = '0'";
				}
				elseif($term == "cats") {
					$todelete = "taxonomy = '1'";
				}
				else {
					$todelete = "tag_id = '$term'";
				}
			

				if(!empty($daysback)) {
					$ANDdaysback = "AND post_date < DATE_SUB(CURRENT_DATE(), INTERVAL $daysback DAY)";
				}
				else {
					$ANDdaysback = "";
				}


			$delete = "DELETE FROM $this->dbtable
				WHERE $todelete $ANDdaysback";
			$wpdb->query($delete);
			}
		}

/****************************************************************
 * Method:  get_the_posts() 
 * Purpose: Get current postdata to display with tag results.
 * Credits: From zappo_wpmu_topposts by Brad Hefta-Gaub (http://heftagaub.wordpress.com/)
*****************************************************************/	

		function get_the_posts($blogarray) {
			global $wpdb, $wpmuBaseTablePrefix;			
			$posts = array();		

			foreach($blogarray as $post_mapping) {								
				$table_blog_posts = $wpmuBaseTablePrefix.$post_mapping->blog_id."_posts";	      				  			   	
				$query ="SELECT *,'$post_mapping->blog_id' AS 'blog_id' 
					FROM $table_blog_posts 
					WHERE ID = $post_mapping->post_id";
				
				$results = $wpdb->get_results($query);			       
			
				if ( !empty($results) ) {
    	        /* sleazy - should really be certain this only returned 1 row... */	      					
					$single_post = $results[0];
					$posts[] = $single_post;				
				}		       
			}

			return $posts;
    }
	
/******************************************************************
 * Method:  TemplateRedirect() 
 * Purpose: Redirects to the tag results template if tags are found
 *****************************************************************/

function TemplateRedirect(){
	global $blog_id;

	$url = $_SERVER['REQUEST_URI'];
	$tagbase = $this->TagOpt['tag_base'];

	$tags_found = FALSE;

	if(preg_match("'$tagbase/(.*)+'", $url)) 
		$tags_found = TRUE;
	

	if($blog_id == '1') {
		
		if ($tags_found) {
		include(TEMPLATEPATH . '/'.$this->TagOpt['results_template'].'.php');
		//it is essential to include the exit. This prevents more than one template file being included
		exit;
		}
	}
}



    }
}

//instantiate the class
if (class_exists('Mu_tags')) {
	$Mu_tags = new Mu_tags();	
}

/******************************************************************
 * Function:  ColorWeight() 
 * Purpose: Generates the appropriate color according to tag size
 * Credits: This is from CTC (configurable tag cloud widget),
 * but it think
 * that originally this is from UTW. I dunno thanks anysay!
 *****************************************************************/


function ColorWeight($weight, $mincolor, $maxcolor) {
	if ($weight) {
		$weight = $weight/100;

		if (strlen($mincolor) == 4) {
			$r = substr($mincolor, 1, 1);
			$g = substr($mincolor, 2, 1);
			$b = substr($mincolor, 3, 1);

			$mincolor = "#$r$r$g$g$b$b";
		}

		if (strlen($maxcolor) == 4) {
			$r = substr($maxcolor, 1, 1);
			$g = substr($maxcolor, 2, 1);
			$b = substr($maxcolor, 3, 1);

			$maxcolor = "#$r$r$g$g$b$b";
		}

		$minr = hexdec(substr($mincolor, 1, 2));
		$ming = hexdec(substr($mincolor, 3, 2));
		$minb = hexdec(substr($mincolor, 5, 2));

		$maxr = hexdec(substr($maxcolor, 1, 2));
		$maxg = hexdec(substr($maxcolor, 3, 2));
		$maxb = hexdec(substr($maxcolor, 5, 2));

		$r = dechex(intval((($maxr - $minr) * $weight) + $minr));
		$g = dechex(intval((($maxg - $ming) * $weight) + $ming));
		$b = dechex(intval((($maxb - $minb) * $weight) + $minb));

		if (strlen($r) == 1) $r = "0" . $r;
		if (strlen($g) == 1) $g = "0" . $g;
		if (strlen($b) == 1) $b = "0" . $b;

		$color = "#$r$g$b";
		$color = substr($color,0,7);
		
		return $color;
	}
}

//This is just a tiny tiny helper.			
function linktitle($key, $value) {
	global $Mu_tags;
	$linktitle = $Mu_tags->TagOpt['tag_link_title'];
	$linktitle = stripslashes($linktitle);
	$linktitle = str_replace("'", "", $linktitle);
	$linktitle = str_replace("\\", "", $linktitle);
	$linktitle = str_replace('"', "\"", $linktitle);
	$linktitle = preg_replace("/%amount%/", $value, $linktitle);
	$linktitle = preg_replace("/%tag%/", $key, $linktitle);

	return $linktitle;
}

/******************************************************************
 * Function:  mu_tag_cloud() 
 * Purpose: Generates the tag cloud
 * Credits: This is from DeltaKids global categories hack/plugin,
 * but originally from somewhere else. Thanks to the creators!
 *****************************************************************/

function mu_tag_cloud($custom = "") {
	global $wpdb, $table_prefix, $Mu_tags;

	$defaults = array(
		'maxsize' => $Mu_tags->TagOpt['tag_max_size'],
		'minsize' => $Mu_tags->TagOpt['tag_min_size'],
		'mincolor' => $Mu_tags->TagOpt['tag_min_color'],		
		'maxcolor' => $Mu_tags->TagOpt['tag_max_color'],
		'limit' => $Mu_tags->TagOpt['limit_tags'],
		'exclude' => $Mu_tags->TagOpt['exclude'],
		'bexclude' => $Mu_tags->TagOpt['bexclude'],
		'cssclass' => $Mu_tags->TagOpt['css_class'],
		'orderby' => $Mu_tags->TagOpt['tag_sort_format'],
		'daysback' => $Mu_tags->TagOpt['daysback'],
		'includecats' => $Mu_tags->TagOpt['include_cats']
	);

	extract($defaults);
	//If custom values are found use them insead
	if(is_array($custom)) {
		extract($custom);
		$customvals = true;
	}
	else {
		$customvals = false;
	}
       	
	$notagsmsg = '<ul><li>No tags found...</li></ul>';
	$tag_list = $Mu_tags->get_global_tags($values = array(
		'limit' => $limit,
		'exclude' => $exclude,
		'bexclude' => $bexclude,
		'orderby' => $orderby,
		'daysback' => $daysback,
		'includecats' => $includecats
		), $customvals);

	if ($tag_list) {				
		foreach($tag_list as $tmp) {			
			$tags[$tmp->tag] = $tmp->cnnt;	
	}
		
		
		// get the largest and smallest array values		
		$max_qty = max(array_values($tags));		
		$min_qty = min(array_values($tags));
		
		// find the range of values		
		$spread = $max_qty - $min_qty;		
		if (0 == $spread) { // we don't want to divide by zero		      
			$spread = 1;		
		}
		
		// determine the font-size increment		
		// this is the increase per tag quantity (times used)		
		$step = ($maxsize - $minsize)/($spread);

		
		// loop through our tag array		
		foreach ($tag_list as $tag) {
		
			$link = $tag->category_nicename;			
			$key = $tag->tag;		
			$value = $tag->cnnt;
		      
			// calculate CSS font-size		       
			// find the $value in excess of $min_qty		      
			// multiply by the font-size increment ($size)		      
			// and add the $min_size set above

			$size = $minsize + (($value - $min_qty) * $step);
		      
			// uncomment if you want sizes in whole %:			      
			//$size = ceil($size);
			
			$color_weight = round(99*($size-$minsize)/($maxsize-$minsize)+1);
		       	
			
			echo '<a href="/'.$Mu_tags->TagOpt['tag_base'].'/'.$link.'" class="'.$cssclass.'" style="font-size: '.$size.''.$Mu_tags->TagOpt['tag_size_format'].'; color: '.ColorWeight($color_weight, $mincolor, $maxcolor).';"';
    echo ' title="'.linktitle($key, $value).'"';
    echo '>'.$key.'</a> ';		
		}	
	}	
	else {		
		echo $notagsmsg;
	}
}

    
function tag_results() {
	global $Mu_tags, $post;
	$blogarray = $Mu_tags->get_tag_results();
	return $Mu_tags->get_the_posts($blogarray);
	}

function mu_recent_posts() {
	global $Mu_tags, $post;
	$blogarray = $Mu_tags->get_recent_posts();
	return $Mu_tags->get_the_posts($blogarray);
	}


function current_tag() {
	global $Mu_tags;
	$tag = $Mu_tags->get_tag_results('true');
	echo $tag;
}

?>
