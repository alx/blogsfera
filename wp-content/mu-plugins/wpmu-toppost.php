<?php
/******************************************************************************************************************
 
Plugin Name: zappo_wpmu_topposts

Plugin URI: http://heftagaub.wordpress.com/2007/03/11/wpmu-top-posts-plugin/

Description: Simple WordPressMU plugin for tracking top posts and top blogs. Inspired by WordPress.com's Top Posts feature, devloped in the style of wp-shortstats.

Version: 0.42.4

Author: Brad Hefta-Gaub

Author URI: http://heftagaub.wordpress.com/

Copyright: (c) 2007 Brad Hefta-Gaub

License:    

            THE WORK IS PROVIDED UNDER THE TERMS OF THIS CREATIVE COMMONS PUBLIC LICENSE ("CCPL" OR 
            "LICENSE"). THE WORK IS PROTECTED BY COPYRIGHT AND/OR OTHER APPLICABLE LAW. ANY USE 
            OF THE WORK OTHER THAN AS AUTHORIZED UNDER THIS LICENSE OR COPYRIGHT LAW IS PROHIBITED.

                            Attribution-NonCommercial-ShareAlike 3.0 Unported
                            
            You are free:

                * to Share — to copy, distribute and transmit the work
                * to Remix — to adapt the work

            Under the following conditions:

                * Attribution. You must attribute the work in the manner specified by the author or 
                  licensor (but not in any way that suggests that they endorse you or your use of the work).
                  
                * Noncommercial. You may not use this work for commercial purposes.
                
                * Share Alike. If you alter, transform, or build upon this work, you may distribute 
                  the resulting work only under the same or similar license to this one.

                * For any reuse or distribution, you must make clear to others the license terms of 
                  this work. The best way to do this is with a link to this web page.
                  
                * Any of the above conditions can be waived if you get permission from the copyright holder.
                
                * Nothing in this license impairs or restricts the author's moral rights.


            The complete license can be found here: http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode


            My reasoning behind this license choice is as follows:

                * I intend for these posts to be informative and educational. They are not intended to 
                  be fully functioning implementations. They are intended to represent reusable ideas. 
                  Therefore, you shouldn’t need more than this license to learn from what I’m presenting here.
                  
                * If you are planning on using this as is, then I would like you to give me the appropriate 
                  level of credit. And if you see me on the street, please introduce yourself and say "Thanks Man!"
                  
                * If you are want to use this code as is for commercial purposes, then, well, contact me. 
                  I make my living as a technologist, and if you’re making money off my hard work, then I want 
                  some control over that. That being said, I don’t mind teaching you what I’ve learned, and 
                  so please feel free to read my code, learn from it, and go about your business as you see fit.
                  But you can't use this code for commercial purposes without contacting me first.

            If you like this code, and you run into me in person, then you should say, "Hey Zappo, that 
            was cool code, I learned something from it, it helped me out. Thanks!", and offer to buy 
            me a beer or a coffee depending on what we're both in the mood for at the time. 
            
            Please may not remove this copyright and license message.
            
            THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
            BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
            NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, 
            DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
            OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

History:    
            0.42.4 - Lots of changes:
            
                      + Added support for top_blogs(), same calling convention as top_posts() but returns
                        top blogs based on total hits to the blog for all posts on the blog. 
                        
                      + Added a couple new arguments to top_posts() include a new max_per_blog which if set
                        to 1 will limit the posts to 1 per blog.
                        
                      + Added days_back argument which will limit the stats to a certain number of days
                        into the past.
                        
                      + Added many new formatting arguments to make it easyer to use the get_*_html functions
                        and get the formating you want.
                        
                      + Fixed a couple bugs that would cause cache corruption (removed a call to 
                        update_post_caches() which was definitley corrupting the post cache)
                        Also correctly call switch_to_blog() in the get_*_html functions so that the
                        blog template tags actually work properly.
            
            0.42.3 - Finally actually fixed the setup behavior. In the past I was trying to use the 
                     built in 'maybe_create_table' function, but sometimes this function is available and sometimes
                     it isn't so people were having trouble one way or the other. Now I've made a local copy of
                     the function and call it in setup everytime. This way the tables are guarenteed to be created.
            
            0.42.2 - Released under Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.
            
            0.42.1 - Small bug fix to 'maybe_create_table' behavior. Namely, we used to try to load it
                     if this function wasn't available, and now we simply check that the function is available.
                     
                     Added blog_id to the posts returned by get_top_posts. This can be useful in forming
                     correct permalinks to your blog posts.
                     
                     Added function zap_setup_post_globals() which in some cases will setup the globals for
                     other wordpressmu templates to work, but this doesn't always reliably work.
                     
            0.42   - Original Version

            
*******************************************************************************************************************/


/*********************************************************************
 * Class:   zappo_wpmu_topposts
 * Purpose: This guy does all the work.
 *
 ********************************************************************/
class zappo_wpmu_topposts 
{
	/******************************************************************
	 * Member Variables - Used throughout the class
	 *****************************************************************/
    var $table_hits;
    var $timezone;
    var $current_time;
	
	/******************************************************************
	 * Method: Constructor - zappo_wpmu_topposts() 
	 *
	 *****************************************************************/
	function zappo_wpmu_topposts() 
	{	
		global $table_prefix,$wpdb;

		/*
		 * our hit tracking table name, this is kinda sleazy, but
		 * since we want our hits to be tracked across all of the blogs (site wide)
		 * we don't want to use $table_prefix, because wpmu rewrites that prefix
		 * to include the blog_id. So we will use the "blogs" table name as our prefix.
		 * This is the same for all blogs.
		 */
		$this->table_hits  = $wpdb->blogs . "_zap_hits";
		
        /*
         * Time related details
         */
		$this->timezone = get_settings('gmt_offset') * 3600;
		$this->current_time = strtotime(gmdate('Y-m-d g:i:s a'))+$this->timezone;

	}
	
	/******************************************************************
	 * Method:  maybe_create_table() 
	 * Purpose: This function comes from mu's admin functions, but
	 *          I couldn't find a way to reliably ensure that it was
	 *          available, so I've included it here. What it basically
	 *          does is check for the exsitance of a table, and if the 
	 *          table doesn't exist then it creates it using the create
	 *          query string passed in.
	 *****************************************************************/
    function maybe_create_table($table_name, $create_ddl) 
    {
	    //echo "<!-- wpmu-topposts.php maybe_create_table() -->";
	    global $wpdb;
	    foreach ($wpdb->get_col("SHOW TABLES",0) as $table ) 
	    {
		    if ($table == $table_name) 
		    {
	            //echo "<!-- maybe_create_table() found table-->";
			    return true;
		    }
	    }
	    //didn't find it try to create it.
	    $q = $wpdb->query($create_ddl);
	    // we cannot directly tell that whether this succeeded!
	    foreach ($wpdb->get_col("SHOW TABLES",0) as $table ) 
	    {
		    if ($table == $table_name) 
		    {
	            //echo "<!-- maybe_create_table() created table-->";
			    return true;
		    }
	    }
        //echo "<!-- maybe_create_table() no table?!! -->";
	    return false;
    }
	

	/******************************************************************
	 * Method:  setup() 
	 * Purpose: creates our tracking table if needed.
	 *****************************************************************/
	function setup() 
	{
	    //echo "<!-- wpmu-topposts.php setup() -->";
	    $table_hits_query = "CREATE TABLE $this->table_hits (
						      id int(11) unsigned NOT NULL auto_increment,
						      blog_id BIGINT(20) unsigned NOT NULL,
						      post_id BIGINT(20) unsigned,
						      hit_time int(10) unsigned NOT NULL default '0',
						      UNIQUE KEY id (id)
						      )";
		$this->maybe_create_table($this->table_hits, $table_hits_query);
	}

	/******************************************************************
	 * Method:  recordhit() 
	 * Purpose: records a hit to a page/post
	 *****************************************************************/
	function recordhit() 
	{	
		global $wpdb;
	    global $blog_id;
	    $post_id = 0; /* in case of non-single post hits, we store a 0 for the post_id */
		$hit_time = $this->current_time; /* What time is it? */
		
        /*
         * Things we don't track: admin hits, 404's, previews, and login.
         */		
 	    if  (
 	            is_admin() || 
 	            is_404() || 
 	            is_preview() || 
 	            strstr($_SERVER['PHP_SELF'], 'wp-login.php')
		    )
            return;
		
	    /*
	     * If this is a "single" post page, then we record it as a post hit
	     * otherwise we just record it as a blog hit.
	     */
        if(is_single())
        {
    	    $post_id = get_the_ID();
        }

        /*
         * We are inserting these hits even in non-single post cases, because
         * we may eventually implement support for "top blogs" based on 
         * hits to the blog. Right now, we only support accessor functions for 
         * top_posts.
         */
		$query = "INSERT INTO $this->table_hits (blog_id,post_id,hit_time) 
				  VALUES ('$blog_id','$post_id',$hit_time)";

		$wpdb->query($query);

    }

    /**********************************************************************************
     *
     * Method  : get_top_posts()
     * Purpose :
     *      Similar to WP's standard get_posts() function, it is used to return a list
     *      of posts, that qualify as "top posts".
     * Parameters:
     *      max_per_blog 
     *          (integer) Number of posts allowed in list per blog.
     *
     *              - Default to -1 which means no maximum. 
     *
     *              - Currently only one other value is allowed which is 1.
     *                If you set this parameter to 1 then you'll only get 
     *                the top post per blog in the list. 
     *
     *      days_back 
     *          (integer) Number of days back to allow stats from. 
     *
     *              - Default to -1 which means allow all hits. 
     *
     *              - If for example you want the top posts from the last week
     *                you could call this with days_back = 7;
     *
     *      numberposts 
     *          (integer) Number of posts to return. Defaults to 5. 
     *
     *      offset 
     *          (integer) Offset from the "top most" post. Defaults to 0. 
     *
     *      orderby 
     *          ("string") Sort Posts by one of various values, including: 
     *              'post_hits'  - Sort by number of hits to the post (Default). 
     *              'post_id'    - Sort by numeric Post ID. 
     *              'blog_id'    - Sort by numeric Blog ID. 
     *
     *      order 
     *          (string) Sort order for options. Valid values: 
     *              'DESC' - Sort from highest to lowest (Default). 
     *              'ASC' - Sort from lowest to highest. 
     *
     *              Note: Unlike get_posts(), the default order is DESC so that the "top_posts"
     *              has a "top" behavior where the post with the "most hits" is listed first.
     *
     *      include/exclude
     *          same behavior as get_posts() 
     *
     */
    function get_top_posts($args = '')
	{
	    global $wpdb, $wpmuBaseTablePrefix;
  
        /*
         * Same behavior as get_posts(), this function accepts an array of args or
         * a URL encoded list of args.
         */
         
	    if ( is_array($args) )
		    $r = &$args;
	    else
		    parse_str($args, $r);

        /*
         * default vaules array here.
         *
         * Note: This is different from get_posts() defaults as noted in the comment
         * of this function.
         */
	    $defaults = array('max_per_blog' => -1, 'days_back' => -1, 'numberposts' => 6, 'offset' => 0, 'category' => '',
		    'orderby' => 'post_hits', 'order' => 'DESC', 'include' => '', 'exclude' => '', 'meta_key' => '', 'meta_value' =>'');
	    $r = array_merge($defaults, $r);
	    extract($r);

        /*
         * Ok, like get_posts() we will attempt to support include='' and exclude=''
         */
         
	    $inclusions = '';
	    if ( !empty($include) ) {
		    $offset = 0;	//ignore offset, category, exclude, meta_key, and meta_value params if using include
		    $category = ''; 
		    $exclude = '';  
		    $meta_key = '';
		    $meta_value = '';
		    $incposts = preg_split('/[\s,]+/',$include);
		    $numberposts = count($incposts);  // only the number of posts included
		    if ( count($incposts) ) {
			    foreach ( $incposts as $incpost ) {
				    if (empty($inclusions))
					    $inclusions = ' AND ( ID = ' . intval($incpost) . ' ';
				    else
					    $inclusions .= ' OR ID = ' . intval($incpost) . ' ';
			    }
		    }
	    }
	    if (!empty($inclusions)) 
		    $inclusions .= ')';	

        /*
         * Like get_posts() we support exclude=''
         */
	    $exclusions = '';
	    if ( !empty($exclude) ) {
		    $exposts = preg_split('/[\s,]+/',$exclude);
		    if ( count($exposts) ) {
			    foreach ( $exposts as $expost ) {
				    if (empty($exclusions))
					    $exclusions = ' AND ( ID <> ' . intval($expost) . ' ';
				    else
					    $exclusions .= ' AND ID <> ' . intval($expost) . ' ';
			    }
		    }
	    }
	    if (!empty($exclusions)) 
		    $exclusions .= ')';
		    
	    if ($days_back != -1) 
	        $days_back_where = "AND TO_DAYS(NOW()) - TO_DAYS(FROM_UNIXTIME(hit_time)) <= $days_back";
	    else
	        $days_back_where = "";

        /*
         * Here's where we start to siginficantly diverge from get_posts(),
         * namely we are reading from a very different table, and so we 
         * have to construct a different query.
         *
         */
        $query = "SELECT blog_id, post_id, COUNT(post_id) AS 'post_hits'
				  FROM $this->table_hits 
				  WHERE post_id <> '0' $exclusions $inclusions $days_back_where
				  GROUP BY blog_id, post_id
	              ORDER BY $orderby $order LIMIT $offset , $numberposts ";
	              
        /*
         * If the caller asked us to limit our posts per blog to 1 then
         * we actually use the above query as a subquery. Sneaky eh? 
         */
        if ($max_per_blog == 1)
        {
            $query = "SELECT blog_id,post_id,MAX(post_hits) as 'post_hits' 
                        FROM ($query) as top_posts GROUP BY blog_id
	                    ORDER BY $orderby $order LIMIT $offset , $numberposts ";
        }
        
        /**************************************************************************
        
         From: get_posts() left here as a reference for later support of more 
               features.
               
	    $query ="SELECT DISTINCT * FROM $wpdb->posts " ;
	    $query .= ( empty( $category ) ? "" : ", $wpdb->post2cat " ) ; 
	    $query .= ( empty( $meta_key ) ? "" : ", $wpdb->postmeta " ) ; 
	    $query .= " WHERE (post_type = 'post' AND post_status = 'publish') $exclusions $inclusions " ;
	    $query .= ( empty( $category ) ? "" : "AND ($wpdb->posts.ID = $wpdb->post2cat.post_id AND $wpdb->post2cat.category_id = " . $category. ") " ) ;
	    $query .= ( empty( $meta_key ) | empty($meta_value)  ? "" : " AND ($wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$meta_key' AND $wpdb->postmeta.meta_value = '$meta_value' )" ) ;
	    $query .= " GROUP BY $wpdb->posts.ID ORDER BY " . $orderby . " " . $order . " LIMIT " . $offset . ',' . $numberposts ;
        **************************************************************************/

        /*
         * Note: this doesn't actually contain a nice "posts" style array.
         * It only contains an array of blog_id, post_ids.Next we need to
         * get the actaual table details from the prefix_{blog_id}_posts tables.
         */
	    $top_posts_mappings = $wpdb->get_results($query);

        /*
         * Start with a fresh array, fill it in below.
         */	    
	    $posts = array();

        foreach($top_posts_mappings as $post_mapping)
        {
            $table_blog_posts = $wpmuBaseTablePrefix.$post_mapping->blog_id."_posts";
	        $query ="SELECT *,'$post_mapping->post_hits' AS 'post_hits','$post_mapping->blog_id' AS 'blog_id' FROM $table_blog_posts WHERE ID = $post_mapping->post_id AND post_type = 'post' AND post_status = 'publish'";
	        $results = $wpdb->get_results($query);

    	    if ( !empty($results) ) 
    	    {
    	        /* sleazy - should really be certain this only returned 1 row... */
	            $single_post = $results[0];
    	        $posts[] = $single_post;
    	    }
        }
	    
        /*
         * We used to call update_post_caches() but it turns out that was a bad idea because it would
         * mix in posts from other blogs into our post cache. So now we've eliminated this caching.
         * However, it might be a good idea in the furture to implement a cache of the top posts here.
         */
	    //update_post_caches($posts);

	    return $posts;
    }

    /*
     * format some HTML for a top posts list.
     */
    function get_top_posts_html($args = '')
    {
        global $post; /* needs to be global if you want get_permalink() and get_the_title() to work. */
        
        $top_posts = $this->get_top_posts($args);

	    if ( is_array($args) )
		    $r = &$args;
	    else
		    parse_str($args, $r);

        /*
         * default vaules array here.
         *
         * Note: This is different from get_posts() defaults as noted in the comment
         * of this function.
         */
	    $defaults = array(

	        'before_list' => '<ul>', 
	        'after_list' => '</ul>',

	        'include_blog_link' => 1, 
	        'seperator' => ' &raquo; ',

	        'before_item' => '<li>', 
	        'after_item' => '</li>', 

	        'include_hits' => 1, 
	        'before_hits' => '(', 
	        'after_hits' => ')' 
	        );

	    $r = array_merge($defaults, $r);
	    extract($r);

        $html=$before_list;

        foreach($top_posts as $post)
        {
            global $wpdb;
            $blog_id = $post->blog_id;
	        if ( $blog_id != $wpdb->blogid ) {
		        $switch = true;
		        switch_to_blog($blog_id);	
	        }

            $html.=$before_item;
            if ($include_blog_link)
                $html.="<a href='".get_bloginfo('home')."'>".get_bloginfo('name')."</a>$seperator";
            $html .="<a href='".get_permalink()."'>".get_the_title()."</a>";
            if ($include_hits)
                $html.="{$before_hits}{$post->post_hits}{$after_hits}";
            $html .=$after_item;

	        if ( $switch )
		        restore_current_blog();
        }
        $html.=$after_list;

        return $html;
    }

    /**********************************************************************************
     *
     * Method  : get_top_blogs()
     * Purpose :
     *      Similar to get_top_posts() function above, except it is used to return a list
     *      of blogs that qualify as "top blogs".
     * Parameters:
     *      days_back 
     *          (integer) Number of days back to allow stats from. 
     *
     *              - Default to -1 which means allow all hits. 
     *
     *              - If for example you want the top posts from the last week
     *                you could call this with days_back = 7;
     *
     *      numberblogs 
     *          (integer) Number of blogs to return. Defaults to 5. 
     *
     *      offset 
     *          (integer) Offset from the "top most" post. Defaults to 0. 
     *
     *      orderby 
     *          ("string") Sort Blogs by one of various values, including: 
     *              'blog_hits'  - Sort by number of hits to the and posts on the blog (Default). 
     *              'blog_id'    - Sort by numeric Blog ID. 
     *
     *      order 
     *          (string) Sort order for options. Valid values: 
     *              'DESC' - Sort from highest to lowest (Default). 
     *              'ASC' - Sort from lowest to highest. 
     *
     *              Note: Unlike get_posts(), the default order is DESC so that the "top_blogs"
     *              has a "top" behavior where the blog with the "most hits" is listed first.
     *
     *      include/exclude
     *          similar behavior as get_posts() except that the id is the blog id to include or
     *          exclude.
     *
     */
    function get_top_blogs($args = '')
	{
	    global $wpdb, $wpmuBaseTablePrefix;
  
        /*
         * Same behavior as get_posts(), this function accepts an array of args or
         * a URL encoded list of args.
         */
         
	    if ( is_array($args) )
		    $r = &$args;
	    else
		    parse_str($args, $r);

        /*
         * default vaules array here.
         *
         * Note: This is different from get_posts() defaults as noted in the comment
         * of this function.
         */
	    $defaults = array('days_back' => -1, 'numberblogs' => 5, 'offset' => 0, 'category' => '',
		    'orderby' => 'blog_hits', 'order' => 'DESC', 'include' => '', 'exclude' => '', 'meta_key' => '', 'meta_value' =>'');
	    $r = array_merge($defaults, $r);
	    extract($r);

        /*
         * Ok, like get_posts() we will attempt to support include='' and exclude=''
         */
         
	    $inclusions = '';
	    if ( !empty($include) ) {
		    $offset = 0;	//ignore offset, category, exclude, meta_key, and meta_value params if using include
		    $category = ''; 
		    $exclude = '';  
		    $meta_key = '';
		    $meta_value = '';
		    $incposts = preg_split('/[\s,]+/',$include);
		    $numberposts = count($incposts);  // only the number of blogs included
		    if ( count($incposts) ) {
			    foreach ( $incposts as $incpost ) {
				    if (empty($inclusions))
					    $inclusions = ' AND ( blog_id = ' . intval($incpost) . ' ';
				    else
					    $inclusions .= ' OR blog_id = ' . intval($incpost) . ' ';
			    }
		    }
	    }
	    if (!empty($inclusions)) 
		    $inclusions .= ')';	

        /*
         * Like get_posts() we support exclude=''
         */
	    $exclusions = '';
	    if ( !empty($exclude) ) {
		    $exposts = preg_split('/[\s,]+/',$exclude);
		    if ( count($exposts) ) {
			    foreach ( $exposts as $expost ) {
				    if (empty($exclusions))
					    $exclusions = ' AND ( blog_id <> ' . intval($expost) . ' ';
				    else
					    $exclusions .= ' AND blog_id <> ' . intval($expost) . ' ';
			    }
		    }
	    }
	    if (!empty($exclusions)) 
		    $exclusions .= ')';
		    
	    if ($days_back != -1) 
	        $days_back_where = "AND TO_DAYS(NOW()) - TO_DAYS(FROM_UNIXTIME(hit_time)) <= $days_back";
	    else
	        $days_back_where = "";

        /*
         * Here's where we start to siginficantly diverge from get_posts(),
         * namely we are reading from a very different table, and so we 
         * have to construct a different query.
         *
         */
        $query = "SELECT blog_id, COUNT(blog_id) AS 'blog_hits'
				  FROM $this->table_hits 
				  WHERE 1 $exclusions $inclusions $days_back_where
				  GROUP BY blog_id
	              ORDER BY $orderby $order LIMIT $offset , $numberblogs ";
	              
        /*
         * Note: this doesn't actually contain a nice "posts" style array.
         * It only contains an array of blog_id, post_ids.Next we need to
         * get the actaual table details from the prefix_{blog_id}_posts tables.
         */
	    $top_blogs_mappings = $wpdb->get_results($query);

	    return $top_blogs_mappings;
    }

    function get_top_blogs_html($args = '')
    {
        $blogs = $this->get_top_blogs($args);
        
	    if ( is_array($args) )
		    $r = &$args;
	    else
		    parse_str($args, $r);

        /*
         * default vaules array here.
         *
         * Note: This is different from get_posts() defaults as noted in the comment
         * of this function.
         */
	    $defaults = array('before_list' => '<ul>', 'after_list' => '</ul>', 'before_item' => '<li>', 'after_item' => '</li>', 'include_hits' => 1, 'before_hits' => '(', 'after_hits' => ')' );
	    $r = array_merge($defaults, $r);
	    extract($r);

        $html=$before_list;
        
        foreach($blogs as $blog_mapping)
        {
            $details = get_blog_details($blog_mapping->blog_id);
			$html.="$before_item<a href='http://{$details->domain}{$details->path}'>{$details->blogname}";

			if ($include_hits) 
			    $html.="{$before_hits}{$blog_mapping->blog_hits}{$after_hits}";

			$html.="</a>$after_item";
        }
        
        $html.=$before_list;

        return $html;
    }
};

// This will be our main "tracking object" we will keep things nice and 
// object oriented by doing all of our work inside this object.
$zap_wpmutp = new zappo_wpmu_topposts();

// We should make this smarter so that we don't waste time attempting to create
// tables over and over again, but in the meantime, this will do.
$zap_wpmutp->setup();

// This hook will be called for every page.
add_action('shutdown', array(&$zap_wpmutp, 'recordhit'));


/*
 * Function: zap_get_top_posts()
 * Purpose :
 *      Similar to WP's standard get_posts() function, it is used to return a list
 *      of posts, that qualify as "top posts".
 * Parameters:
 *      See zappo_wpmu_topposts::get_top_posts()
 */
function zap_get_top_posts($args = '')
{
    global $zap_wpmutp;
    return $zap_wpmutp->get_top_posts($args);
}

/*
 * Function: zap_get_top_posts_html()
 * Purpose :
 *      Returns some HTML for get_top_posts().
 * Parameters:
 *      See zappo_wpmu_topposts::get_top_posts_html()
 */
function zap_get_top_posts_html($args = '')
{
    global $zap_wpmutp;
    return $zap_wpmutp->get_top_posts_html($args);
}

/*
 * Function: zap_top_posts_html()
 * Purpose :
 *      echos some HTML for get_top_posts().
 * Parameters:
 *      See zappo_wpmu_topposts::get_top_posts()
 */
function zap_top_posts_html($args = '')
{
    global $zap_wpmutp;
    echo $zap_wpmutp->get_top_posts_html($args);
}


/*
 * mimics the_title() except that it shows 'post_hits' as opposed to 'title' for a post
 *
 * Assumes that some zap_..._top_posts() function has been called first, or else the post_hits
 * member will not be available for $post;
 */
function zap_the_post_hits($before = '', $after = '', $echo = true) 
{
	$post_hits = zap_get_the_post_hits();
	if ( strlen($post_hits) > 0 ) {
		$post_hits = apply_filters('the_post_hits', $before . $post_hits . $after, $before, $after);
		if ( $echo )
			echo $post_hits;
		else
			return $post_hits;
	}
}


/*
 * mimics get_the_title() except that it shows 'post_hits' as opposed to 'title' for a post
 *
 * Assumes that some zap_..._top_posts() function has been called first, or else the post_hits
 * member will not be available for $post;
 */
function zap_get_the_post_hits() 
{
	global $post;
	$post_hits = $post->post_hits;
	return $post_hits;
}

/*
 * call this for each post object returned by zap_get_top_posts() to set the global variables 
 * so that the_permalink() and other template functions work properly.
 *
 * Assumes that some zap_..._top_posts() function has been called first, or else the post_hits,
 * and blog_id members will not be available for $post;
 *
 * May 25, 2007: In retrospect this isn't a good idea. The right thing to do is to use the 
 * switch_to_blog() function, but then you need to know which blog to go back to... so
 * without wrapping this call with something else, this can't be done safely. REALLY... you
 * shouldn't use this function, instead use zap_get_top_posts_html() or zap_top_posts_html()
 */
function zap_setup_post_globals($post_in) 
{
	global $post,$blog_id;
	$post = $post_in;
	$blog_id = $post_in->blog_id;
	
	// Maybe we should call this instead?!?!
	//switch_to_blog($blog_id);	
}


/*
 * Function: zap_get_top_blogs()
 * Purpose :
 *      Similar to get_top_posts() function, it is used to return a list
 *      of blogs, that qualify as "top blogs".
 * Parameters:
 *      See zappo_wpmu_topposts::get_top_blogs()
 */
function zap_get_top_blogs($args = '')
{
    global $zap_wpmutp;
    return $zap_wpmutp->get_top_blogs($args);
}

/*
 * Function: zap_get_top_blogs_html()
 * Purpose :
 *      Returns some HTML for get_top_posts().
 * Parameters:
 *      See zappo_wpmu_topposts::get_top_blogs_html()
 */
function zap_get_top_blogs_html($args = '')
{
    global $zap_wpmutp;
    return $zap_wpmutp->get_top_blogs_html($args);
}

/*
 * Function: zap_top_blogs_html()
 * Purpose :
 *      echos some HTML for get_top_blogs().
 * Parameters:
 *      See zappo_wpmu_topposts::get_top_blogs()
 */
function zap_top_blogs_html($args = '')
{
    global $zap_wpmutp;
    echo $zap_wpmutp->get_top_blogs_html($args);
}


?>
