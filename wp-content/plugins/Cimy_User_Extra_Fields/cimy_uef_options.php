<?php

function cimy_save_options() {
	global $wpdb, $cimy_uef_version, $cimy_uef_options, $wpdb_wp_fields_table, $max_length_fieldset_value, $cimy_uef_domain, $is_mu, $wp_hidden_fields;

	if (!cimy_check_admin('level_10'))
		return;
		
	if (isset($_POST['force_activation']))
		cimy_plugin_install();
	
	$results = array();
	$do_not_update_options = false;
	
	if ($is_mu)
		$options = get_site_option($cimy_uef_options);
	else
		$options = get_option($cimy_uef_options);
	
	$old_wp_hidden_fields = $options['wp_hidden_fields'];
	
	$options['aue_hidden_fields'] = array();
	$options['wp_hidden_fields'] = array();
	
	$items_per_fieldset = intval($_POST['items_per_fieldset']);
	
	if ($items_per_fieldset > 0)
		$options['items_per_fieldset'] = $items_per_fieldset;
	else
		$options['items_per_fieldset'] = 1;
	
	$options['fieldset_title'] = stripslashes($_POST['fieldset_title']);
	$options['fieldset_title'] = substr($options['fieldset_title'], 0, $max_length_fieldset_value);

	
	if (isset($_POST['disable_cimy_fieldvalue']))
		$options['disable_cimy_fieldvalue'] = true;
	else
		$options['disable_cimy_fieldvalue'] = false;
	
	if (isset($_POST['db_wp_fields_check'])) {
		switch ($_POST['db_wp_fields']) {
			case 'empty':
				cimy_manage_db('empty_wp_fields');
				$results['empty_wp_fields'] = __("WordPress Fields table emptied", $cimy_uef_domain);
				break;
				
			case 'delete':
				cimy_manage_db('drop_wp_fields');
				$results['empty_wp_fields'] = __("WordPress Fields table deleted", $cimy_uef_domain);
				break;
		}
	}
	
	if (isset($_POST['db_extra_fields_check'])) {
		switch ($_POST['db_extra_fields']) {
			case 'empty':
				cimy_manage_db('empty_extra_fields');
				$results['empty_extra_fields'] = __("Extra Fields table emptied", $cimy_uef_domain);
				break;
				
			case 'delete':
				cimy_manage_db('drop_extra_fields');
				$results['empty_extra_fields'] = __("Extra Fields table deleted", $cimy_uef_domain);
				break;
		}
	}

	if (isset($_POST['db_data_check'])) {
		switch ($_POST['db_data']) {
			case 'empty':
				cimy_manage_db('empty_data');
				$results['empty_data'] = __("Users Data table emptied", $cimy_uef_domain);
				break;
				
			case 'delete':
				cimy_manage_db('drop_data');
				$results['empty_data'] = __("Users Data table deleted", $cimy_uef_domain);
				break;
		}
	}

	if (isset($_POST['db_options_check'])) {
		
		switch ($_POST['db_options']) {
			case 'default':
				cimy_manage_db('default_options');
				$do_not_save_options = true;
				$results['results'] = __("Options set to default values", $cimy_uef_domain);
				break;
				
			case 'delete':
				cimy_manage_db('drop_options');
				$do_not_save_options = true;
				$results['results'] = __("Options deleted", $cimy_uef_domain);
				break;
		}
	}
	
	if (isset($_POST['do_not_save_options']))
		$do_not_save_options = true;

	if (isset($_POST['hide_username']))
		array_push($options['aue_hidden_fields'], 'username');

	if (isset($_POST['hide_name']))
		array_push($options['aue_hidden_fields'], 'name');

	if (isset($_POST['hide_posts']))
		array_push($options['aue_hidden_fields'], 'posts');
	
	if (isset($_POST['hide_email']))
		array_push($options['aue_hidden_fields'], 'email');
	
	if (isset($_POST['hide_website']))
		array_push($options['aue_hidden_fields'], 'website');
	
	if (isset($_POST['hide_role']))
		array_push($options['aue_hidden_fields'], 'role');
	
	$tot_wp_hidden_fields = count($old_wp_hidden_fields);
	$action = "add";
	
	if (!isset($results['empty_wp_fields'])) {
		if (isset($_POST['show_wp_firstname'])) {
			array_push($options['wp_hidden_fields'], 'firstname');
			
			if (!in_array("firstname", $old_wp_hidden_fields)) {
				$data = $wp_hidden_fields['firstname'];
				
				$data['num_fields'] = $tot_wp_hidden_fields;
				$tot_wp_hidden_fields++;
				
				cimy_save_field($action, $wpdb_wp_fields_table, $data);
			}
		}
		
		if (isset($_POST['show_wp_lastname'])) {
			array_push($options['wp_hidden_fields'], 'lastname');
			
			if (!in_array("lastname", $old_wp_hidden_fields)) {
				$data = $wp_hidden_fields['lastname'];
				
				$data['num_fields'] = $tot_wp_hidden_fields;
				$tot_wp_hidden_fields++;
				
				cimy_save_field($action, $wpdb_wp_fields_table, $data);
			}
		}
	
		if (isset($_POST['show_wp_nickname'])) {
			array_push($options['wp_hidden_fields'], 'nickname');
			
			if (!in_array("nickname", $old_wp_hidden_fields)) {
				$data = $wp_hidden_fields['nickname'];
				
				$data['num_fields'] = $tot_wp_hidden_fields;
				$tot_wp_hidden_fields++;
				
				cimy_save_field($action, $wpdb_wp_fields_table, $data);
			}
		}
		
		if (isset($_POST['show_wp_email'])) {
			array_push($options['wp_hidden_fields'], 'email');
			
			if (!in_array("email", $old_wp_hidden_fields)) {
				$data = $wp_hidden_fields['email'];
				
				$data['num_fields'] = $tot_wp_hidden_fields;
				$tot_wp_hidden_fields++;
				
				cimy_save_field($action, $wpdb_wp_fields_table, $data);
			}
		}
		
		if (isset($_POST['show_wp_website'])) {
			array_push($options['wp_hidden_fields'], 'website');
			
			if (!in_array("website", $old_wp_hidden_fields)) {
				$data = $wp_hidden_fields['website'];
				
				$data['num_fields'] = $tot_wp_hidden_fields;
				$tot_wp_hidden_fields++;
				
				cimy_save_field($action, $wpdb_wp_fields_table, $data);
			}
		}
		
		if (isset($_POST['show_wp_aim'])) {
			array_push($options['wp_hidden_fields'], 'aim');
			
			if (!in_array("aim", $old_wp_hidden_fields)) {
				$data = $wp_hidden_fields['aim'];
				
				$data['num_fields'] = $tot_wp_hidden_fields;
				$tot_wp_hidden_fields++;
				
				cimy_save_field($action, $wpdb_wp_fields_table, $data);
			}
		}
		
		if (isset($_POST['show_wp_yahoo'])) {
			array_push($options['wp_hidden_fields'], 'yahoo');
			
			if (!in_array("yahoo", $old_wp_hidden_fields)) {
				$data = $wp_hidden_fields['yahoo'];
				
				$data['num_fields'] = $tot_wp_hidden_fields;
				$tot_wp_hidden_fields++;
				
				cimy_save_field($action, $wpdb_wp_fields_table, $data);
			}
		}
		
		if (isset($_POST['show_wp_jgt'])) {
			array_push($options['wp_hidden_fields'], 'jgt');
			
			if (!in_array("jgt", $old_wp_hidden_fields)) {
				$data = $wp_hidden_fields['jgt'];
				
				$data['num_fields'] = $tot_wp_hidden_fields;
				$tot_wp_hidden_fields++;
				
				cimy_save_field($action, $wpdb_wp_fields_table, $data);
			}
		}
	}

	$all_wp_fields = get_cimyFields(true);
	$sql = "DELETE FROM ".$wpdb_wp_fields_table." WHERE ";

	$k = (-1);
	$j = (-1);
	$msg = "";
	$not_del_old = "";
	$not_del_sql = "";

	foreach ($all_wp_fields as $wp_field) {
		$f_name = strtolower($wp_field['NAME']);
		$f_order = intval($wp_field['F_ORDER']);
		
		if (!isset($_POST['show_wp_'.$f_name])) {
			if (in_array($f_name, $old_wp_hidden_fields)) {
				if ($k > (-1)) {
					$sql.= " OR ";
					$msg.= ", ";
				}
				else {
					$k = $f_order;
					$j = $f_order;
				}
	
				$sql.= "F_ORDER=".$f_order;
				$msg.= $f_order;
			}
		}
		// field to NOT be deleted, but order probably have to change, if j==(-1) then order is ok because deletions is after it!
		else {
			if ($j > (-1)) {
				if ($not_del_old != "") {
				
					$not_del_old.= ", ";
				}

				$not_del_sql.= " WHEN ".$f_order." THEN ".$j." ";
				$not_del_old.= $f_order;
				$j++;
			}
		}
	}

	// if at least one field was selected
	if ($k > (-1)) {
		// $sql WILL BE: DELETE FROM <table> WHERE F_ORDER=<value1> [OR F_ORDER=<value2> ...]
		$wpdb->query($sql);

		if ($not_del_sql != "") {
			$not_del_sql = "UPDATE ".$wpdb_wp_fields_table." SET F_ORDER=CASE F_ORDER".$not_del_sql."ELSE F_ORDER END WHERE F_ORDER IN(".$not_del_old.")";

			// $not_del_sql WILL BE: UPDATE <table> SET F_ORDER=CASE F_ORDER WHEN <oldvalue1> THEN <newvalue1> [WHEN ... THEN ...] ELSE F_ORDER END WHERE F_ORDER IN(<oldvalue1> [, <oldvalue2>...])
			$wpdb->query($not_del_sql);
		}
	}
	
	if (!$do_not_save_options) {
		if ($is_mu)
			update_site_option($cimy_uef_options, $options);
		else
			update_option($cimy_uef_options, $options);
		
		$results['results'] = __("Options changed", $cimy_uef_domain);
	}
	
	return $results;
}

function cimy_show_options_notembedded() {
	$results = array();
	
	cimy_show_options($results, false);
}

function cimy_show_options($results, $embedded) {
	global $wpdb, $wpdb_wp_fields_table, $wpdb_fields_table, $wpdb_data_table, $cimy_uef_options, $max_length_fieldset_value, $cimy_uef_name, $cimy_uef_url, $cimy_uef_version, $cimy_uef_domain, $is_mu, $cimy_top_menu;

	if (!cimy_check_admin('level_10'))
		return;
		
	// save options engine
	if ((isset($_POST['cimy_options'])) && (isset($cimy_top_menu)))
		$results = cimy_save_options();
	
	if ($is_mu)
		$options = get_site_option($cimy_uef_options);
	else
		$options = get_option($cimy_uef_options);
	
	$options['disable_cimy_fieldvalue'] ? $disable_cimy_fieldvalue = ' checked="checked"' : $disable_cimy_fieldvalue = '';
	
	$options['fieldset_title'] = attribute_escape($options['fieldset_title']);
	
	if ($options) {
		in_array('username', $options['aue_hidden_fields']) ? $aue_hide_username = ' checked="checked"' : $aue_hide_username = '';
		in_array('name', $options['aue_hidden_fields']) ? $aue_hide_name = ' checked="checked"' : $aue_hide_name = '';
		in_array('email', $options['aue_hidden_fields']) ? $aue_hide_email = ' checked="checked"' : $aue_hide_email = '';
		in_array('website', $options['aue_hidden_fields']) ? $aue_hide_website = ' checked="checked"' : $aue_hide_website = '';
		in_array('posts', $options['aue_hidden_fields']) ? $aue_hide_posts = ' checked="checked"' : $aue_hide_posts = '';
		in_array('role', $options['aue_hidden_fields']) ? $aue_hide_role = ' checked="checked"' : $aue_hide_role = '';
		
		in_array('firstname', $options['wp_hidden_fields']) ? $show_wp_firstname = ' checked="checked"' : $show_wp_firstname = '';
		in_array('lastname', $options['wp_hidden_fields']) ? $show_wp_lastname = ' checked="checked"' : $show_wp_lastname = '';
		in_array('nickname', $options['wp_hidden_fields']) ? $show_wp_nickname = ' checked="checked"' : $show_wp_nickname = '';
		in_array('website', $options['wp_hidden_fields']) ? $show_wp_website = ' checked="checked"' : $show_wp_website = '';
		in_array('aim', $options['wp_hidden_fields']) ? $show_wp_aim = ' checked="checked"' : $show_wp_aim = '';
		in_array('yahoo', $options['wp_hidden_fields']) ? $show_wp_yahoo = ' checked="checked"' : $show_wp_yahoo = '';
		in_array('jgt', $options['wp_hidden_fields']) ? $show_wp_jgt = ' checked="checked"' : $show_wp_jgt = '';
		
		$db_options = true;
	}
	else {
		$db_options = false;
		
		$aue_hide_username = '';
		$aue_hide_name = '';
		$aue_hide_email = '';
		$aue_hide_website = '';
		$aue_hide_posts = '';
		$aue_hide_role = '';
		
		$show_wp_firstname = '';
		$show_wp_secondname = '';
		$show_wp_nickname = '';
		$show_wp_website = '';
		$show_wp_aim = '';
		$show_wp_yahoo = '';
		$show_wp_jgt = '';
	}
	
	if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb_wp_fields_table'") == $wpdb_wp_fields_table) {
		$sql = "SELECT id, COUNT(*) FROM ".$wpdb_wp_fields_table." GROUP BY id";
		$db_wp_fields = $wpdb->query($sql);
	}
	else
		$db_wp_fields = -1;
	
	if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb_fields_table'") == $wpdb_fields_table) {
		$sql = "SELECT id, COUNT(*) FROM ".$wpdb_fields_table." GROUP BY id";
		$db_extra_fields = $wpdb->query($sql);
	}
	else
		$db_extra_fields = -1;
	
	if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb_data_table'") == $wpdb_data_table)
		$db_users_data = true;
	else
		$db_users_data = false;
	
	$ret = array();
	
	$ret['db_options'] = $db_options;
	$ret['db_extra_fields'] = $db_extra_fields;
	$ret['db_wp_fields'] = count($options['wp_hidden_fields']);
	$ret['db_users_data'] = $db_users_data;

	if ((isset($cimy_top_menu)) && ($embedded))
		return $ret;
	
	?>
	
	<div class="wrap" id="options">
	<h2><?php _e("Options");
	
	if (!isset($cimy_top_menu)) {
		?>- <a href="#addfield"><?php _e("Add a new Field", $cimy_uef_domain); ?></a> - <a href="#extrafields"><?php _e("Extra Fields", $cimy_uef_domain); ?></a><?php
	}
	?></h2><?php
	
	// print successes if there are some
	if (count($results) > 0) {
	?>
		<div class="updated">
		<h3><?php _e("SUCCESSFUL", $cimy_uef_domain); ?></h3>
		<ul>
			<?php 
			foreach ($results as $result)
				echo "<li>".$result."</li>";
			?>
		</ul>
		<br />
		</div>
	<?php
	}

	?><form method="post" action="#options">
	<p class="submit" style="border-width: 0px;"><input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" /></p>
	<h3><?php _e("General"); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" width="40%">
				<strong><a href="<?php echo $cimy_uef_url; ?>"><?php echo $cimy_uef_name; ?></a></strong>
			</th>
			<td width="60%">v<?php echo $options['version'];
				if ($cimy_uef_version != $options['version']) {
					?> (<?php _e("installed is", $cimy_uef_domain); ?> v<?php echo $cimy_uef_version; ?>)<?php
				}
				
				if (!$db_options) {
					?><br /><h4><?php _e("OPTIONS DELETED!", $cimy_uef_domain); ?></h4>
					<input type="hidden" name="do_not_save_options" value="1" /><?php
				}
				else if ($cimy_uef_version != $options['version']) {
					?><br /><h4><?php _e("VERSIONS MISMATCH! This because you haven't de-activated and re-activated the plug-in after the update! This could give problems...", $cimy_uef_domain); ?></h4><?php
				}
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="disable_cimy_fieldvalue" value="1"<?php echo $disable_cimy_fieldvalue; ?> /> <?php _e("Disable get_cimyFieldValue function", $cimy_uef_domain); ?></th>
			<td><?php _e("leave disabled if you don't know what to do", $cimy_uef_domain); ?></td>
		</tr>
	</table>
	<br />
	<h3><?php _e("Database", $cimy_uef_domain); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" width="40%"><input type="checkbox" name="db_options_check" value="1" /> Cimy User Extra Fields <?php _e("Options"); ?></th>
			<td width="60%">
				<?php
				if ($db_options) {
					?>
					<select name="db_options">
						<option value="none">- <?php _e("select action", $cimy_uef_domain); ?> -</option>
						<option value="default"><?php _e("Default values", $cimy_uef_domain); ?></option>
						<option value="delete"><?php _e("Delete"); ?></option>
					</select><?php
				}
				else
					echo "<strong>".__("NOT PRESENT", $cimy_uef_domain)."</strong>";
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="db_wp_fields_check" value="1" /> <?php _e("WordPress Fields table", $cimy_uef_domain); ?></th>
			<td>
				<?php
				if ($db_wp_fields >= 0) {
					$dis_wp_fields = "";
					?>
					<select name="db_wp_fields">
						<option value="none">- <?php _e("select action", $cimy_uef_domain); ?> -</option>
						<option value="empty"><?php _e("Empty", $cimy_uef_domain); ?></option>
						<option value="delete"><?php _e("Delete"); ?></option>
					</select><?php
				}
				else {
					$dis_wp_fields = ' disabled="disabled"';
					echo "<strong>".__("NOT PRESENT", $cimy_uef_domain)."</strong>";
				}
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="db_extra_fields_check" value="1" /> <?php _e("Extra Fields table", $cimy_uef_domain); ?></th>
			<td>
				<?php
				if ($db_extra_fields >= 0) {
					?>
					<select name="db_extra_fields">
						<option value="none">- <?php _e("select action", $cimy_uef_domain); ?> -</option>
						<option value="empty"><?php _e("Empty", $cimy_uef_domain); ?></option>
						<option value="delete"><?php _e("Delete"); ?></option>
					</select><?php
				}
				else
					echo "<strong>".__("NOT PRESENT", $cimy_uef_domain)."</strong>";
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="db_data_check" value="1" /> <?php _e("Users Data table", $cimy_uef_domain); ?></th>
			<td>
				<?php
				if ($db_users_data) {
					?>
					<select name="db_data">
						<option value="none">- <?php _e("select action", $cimy_uef_domain); ?> -</option>
						<option value="empty"><?php _e("Empty", $cimy_uef_domain); ?></option>
						<option value="delete"><?php _e("Delete"); ?></option>
					</select> <?php
					_e("all data inserted by users in all and only extra fields", $cimy_uef_domain);
				}
				else
					echo "<strong>".__("NOT PRESENT", $cimy_uef_domain)."</strong>";
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="force_activation" value="1" /> <?php _e("Force tables creation", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
	</table>
	<br />
	<h3><?php _e("User Profile", $cimy_uef_domain); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" width="40%"><?php _e("Items per fieldset", $cimy_uef_domain); ?></th>
			<td width="60%"><input type="text" name="items_per_fieldset" value="<?php echo $options['items_per_fieldset']; ?>" size="3" maxlength="3" /></td>
		</tr>
		<tr>
			<th scope="row"><?php _e("Fieldset's titles, separates with comma", $cimy_uef_domain); ?><br /><?php _e("example: title1,title2,title3", $cimy_uef_domain); ?></th>
			<td><input type="text" name="fieldset_title" value="<?php echo $options['fieldset_title']; ?>" size="35" maxlength="<?php echo $max_length_fieldset_value; ?>" /></td>
		</tr>

	</table>
	<br />
	<h3><?php _e("Authors &amp; Users Extended", $cimy_uef_domain); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" width="40%">
				<input type="checkbox" name="hide_username" value="1"<?php echo $aue_hide_username; ?> /> <?php _e("Hide username field", $cimy_uef_domain); ?>
			</th>
			<td width="60%"></td>
		</tr>
		<tr>
			<th>
				<input type="checkbox" name="hide_name" value="1"<?php echo $aue_hide_name; ?> /> <?php _e("Hide name field", $cimy_uef_domain); ?>
			</th>
			<td></td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="hide_email" value="1"<?php echo $aue_hide_email; ?> /> <?php _e("Hide email field", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="hide_role" value="1"<?php echo $aue_hide_role; ?> /> <?php _e("Hide role field", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="hide_website" value="1"<?php echo $aue_hide_website; ?> /> <?php _e("Hide website field", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
		<tr>
			<th><input type="checkbox" name="hide_posts" value="1"<?php echo $aue_hide_posts; ?> /> <?php _e("Hide n. posts field", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
	</table>
	<br />
	<h3><?php _e("WordPress hidden fields", $cimy_uef_domain); ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row" width="40%"><input type="checkbox" name="show_wp_firstname" value="1"<?php echo $show_wp_firstname.$dis_wp_fields; ?> /> <?php _e("Show first name", $cimy_uef_domain); ?></th>
			<td width="60%"></td>
		</tr>
		<tr>
			<th><input type="checkbox" name="show_wp_lastname" value="1"<?php echo $show_wp_lastname.$dis_wp_fields; ?> /> <?php _e("Show last name", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
		<tr>
			<th><input type="checkbox" name="show_wp_nickname" value="1"<?php echo $show_wp_nickname.$dis_wp_fields; ?> /> <?php _e("Show nickname", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="show_wp_website" value="1"<?php echo $show_wp_website.$dis_wp_fields; ?> /> <?php _e("Show website", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="show_wp_aim" value="1"<?php echo $show_wp_aim.$dis_wp_fields; ?> /> <?php _e("Show AIM", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="show_wp_yahoo" value="1"<?php echo $show_wp_yahoo.$dis_wp_fields; ?> /> <?php _e("Show Yahoo IM", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
		<tr>
			<th scope="row"><input type="checkbox" name="show_wp_jgt" value="1"<?php echo $show_wp_jgt.$dis_wp_fields; ?> /> <?php _e("Show Jabber / Google Talk", $cimy_uef_domain); ?></th>
			<td></td>
		</tr>
	</table>
	<input type="hidden" name="cimy_options" value="1" />
	<p class="submit"><input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" /></p>
	</form>
	</div>
	<br />
	<?php
	
	return $ret;
}

?>