<?php

function cimy_admin_define_extra_fields() {
	global $wpdb, $wpdb_fields_table, $wpdb_wp_fields_table, $rule_canbeempty, $rule_email, $rule_maxlen, $rule_maxlen_needed, $available_types, $max_length_name, $max_length_label, $max_length_desc, $max_length_value, $max_size_file, $rule_equalto, $rule_equalto_case_sensitive, $cimy_uef_domain, $is_mu, $cuef_plugin_path;
	
	if (!cimy_check_admin('level_10'))
		return;

	$errors = Array();
	$results = Array();

	$add_caption = __("Add field", $cimy_uef_domain);
	$edit_caption = __("Update field", $cimy_uef_domain);
	$del_caption = __("Delete field", $cimy_uef_domain);
	$delSel_caption = __("Delete selected fields", $cimy_uef_domain);
	$order_caption = __("Change order", $cimy_uef_domain);
	
	$submit_msgs = array();
	$submit_msgs['add_caption'] = $add_caption;
	$submit_msgs['edit_caption'] = $edit_caption;
	$submit_msgs['del_caption'] = $del_caption;
	$submit_msgs['delSel_caption'] = $delSel_caption;
	$submit_msgs['order_caption'] = $order_caption;
	
	$min_length_caption = __("Min length", $cimy_uef_domain);
	$exact_length_caption = __("Exact length", $cimy_uef_domain);
	$max_length_caption = __("Max length", $cimy_uef_domain);
	$exact_or_max_length_capton = __("Exact or Max length", $cimy_uef_domain);
	
	$minLen = 1;
	$maxLen = $max_length_value;
	
	$submit = $_POST['submit'];

	$action = "";
	$field_order = "";
	
	// options form engine
	if (isset($_POST['cimy_options'])) {
		$action = "options";
		$res = cimy_save_options();
	}
	else if (!stristr($submit, $add_caption) === false)
		$action = "add";
	else if (!stristr($submit, $edit_caption) === false)
		$action = "edit";
	else if (!stristr($submit, $del_caption) === false)
		$action = "del";
	else if (!stristr($submit, $delSel_caption) === false)
		$action = "delSel";
	else if (!stristr($submit, $order_caption) === false)
		$action = "order";

	if (!isset($res))
		$res = array();
	
	// call options draw function
	$opt_ret = cimy_show_options($res, true);

	// needed fields count here, after options and before $actions manager! do not move!
	$num_fields = $opt_ret['db_extra_fields'];
	$num_wp_fields = $opt_ret['db_wp_fields'];

	if (isset($_POST['wp_fields'])) {
		$wp_fields_post = true;
		$fields_table = $wpdb_wp_fields_table;
		$tot_fields = $num_wp_fields;
	}
	else {
		$wp_fields_post = false;
		$fields_table = $wpdb_fields_table;
		$tot_fields = $num_fields;
	}

	// if pushed change order button
	if ($action == "order") {

		$sql = "UPDATE ".$fields_table." SET F_ORDER=CASE F_ORDER";

		$k = (-1);
		$msg = "";
		$msg_new = "";
		$arr1 = Array();
		$arr2 = Array();

		// check which fields are selected
		for ($i = 1; $i <= $tot_fields; $i++)
			if ($_POST['check'.$i]) {
				if ($k > (-1)) {
					$msg.= ", ";
					$msg_new.= ", ";
				}
				else
					$k = $i;

				$sql.= " WHEN ".$i." THEN ".$_POST['order'.$i];
				$msg.= $i;
				$msg_new.= $_POST['order'.$i];

				array_push($arr1, $i);
				array_push($arr2, $_POST['order'.$i]);
			}

		if ($k > (-1)) {
			if (count(array_diff($arr1, $arr2)) == 0) {
				$sql.= " ELSE F_ORDER END WHERE F_ORDER IN (".$msg.")";

				// $sql WILL BE: UPDATE <table> SET F_ORDER=CASE F_ORDER WHEN <oldvalue1> THEN <newvalue1> [WHEN ... THEN ...] ELSE F_ORDER END WHERE F_ORDER IN(<oldvalue1> [, <oldvalue2>... ])
				$wpdb->query($sql);

				$results['order'] = __("Fields", $cimy_uef_domain)." #".$msg." ".__("changed to", $cimy_uef_domain)." #".$msg_new;
			}
			else
				$errors['order'] = __("You cannot give an order that misses some numbers", $cimy_uef_domain);
		}
		else
			$errors['order'] = __("Nothing selected", $cimy_uef_domain);
	}

	// if pushed delete or update single button
	if (($action == "del") || ($action == "edit")) {

		$from = strpos($submit, '#') + 1;
		$field_order = substr($submit, $from);

		// if pushed the single delete button then check the relative checkbox and let delSel code to delete it
		if ($action == "del") {
			$_POST['check'.$field_order] = 1;
			$action = "delSel";
		}
	}
	
	if ($action == "delSel") {
		$sql = "DELETE FROM ".$fields_table." WHERE ";
		$sql_data_del = "";

		$k = (-1);
		$j = (-1);
		$msg = "";
		$not_del_old = "";
		$not_del_sql = "";

		// check which fields are selected for deletions
		for ($i = 1; $i <= $tot_fields; $i++)
			if ($_POST['check'.$i]) {
				if ($k > (-1)) {
					$sql.= " OR ";
					$sql_data_del.= " OR ";
					$msg.= ", ";
				}
				else {
					$k = $i;
					$j = $i;
				}

				$sql_data_del.= "FIELD_ID=".$i;
				$sql.= "F_ORDER=".$i;
				$msg.= $i;
			}
			else // field to NOT be deleted, but order probably have to change, if j==(-1) then order is ok because deletions is after it!
				if ($j > (-1)) {
					if ($not_del_old != "") {
						
						$not_del_old.= ", ";
					}

					$not_del_sql.= " WHEN ".$i." THEN ".$j." ";
					$not_del_old.= $i;
					$j++;
				}

		// if at least one field was selected
		if ($k > (-1)) {
			// $sql WILL BE: DELETE FROM <table> WHERE F_ORDER=<value1> [OR F_ORDER=<value2> ...]
			$wpdb->query($sql);
			
			// delete also all data inserted by users in this/these field/s
			cimy_delete_users_info($sql_data_del);

			if ($not_del_sql != "") {
				$not_del_sql = "UPDATE ".$fields_table." SET F_ORDER=CASE F_ORDER".$not_del_sql."ELSE F_ORDER END WHERE F_ORDER IN(".$not_del_old.")";

				// $not_del_sql WILL BE: UPDATE <table> SET F_ORDER=CASE F_ORDER WHEN <oldvalue1> THEN <newvalue1> [WHEN ... THEN ...] ELSE F_ORDER END WHERE F_ORDER IN(<oldvalue1> [, <oldvalue2>...])
				$wpdb->query($not_del_sql);
			}

			$results['delete'] = __("Field(s)", $cimy_uef_domain)." #".$msg." ".__("deleted correctly", $cimy_uef_domain);
		}
		else
			$errors['delete'] = __("Nothing selected", $cimy_uef_domain);
	}

	if (($action == "add") || ($action == "edit")) {
		$store_rule = array();
		
		// RETRIEVE DATA FROM THE FORM
		$name = substr(stripslashes($_POST['name'.$field_order]), 0, $max_length_name);
		$value = substr(stripslashes($_POST['value'.$field_order]), 0, $max_length_value);
		$desc = substr(stripslashes($_POST['description'.$field_order]), 0, $max_length_desc);
		$label = substr(stripslashes($_POST['label'.$field_order]), 0, $max_length_label);

		$name = strtoupper($name);
		$oldname = strtoupper(stripslashes($_POST['oldname'.$field_order]));
		$type = $_POST['type'.$field_order];

		$minlen = $_POST['minlen'.$field_order];
		$exactlen = $_POST['exactlen'.$field_order];
		$maxlen = $_POST['maxlen'.$field_order];
		
		// min length available
		$minLen = 1;
			
		// max length or size for picture available
		if ($type == "picture") {
			$maxLen = $max_size_file;
			
			/* overwrite previous values */
			$min_length_caption = __("Min size", $cimy_uef_domain)." (KB)";
			$exact_length_caption = __("Exact size", $cimy_uef_domain)." (KB)";
			$max_length_caption = __("Max size", $cimy_uef_domain)." (KB)";
			
			$exact_or_max_length_capton = __("Exact or Max size", $cimy_uef_domain)." (KB)";
		}
		else
			$maxLen = $max_length_value;
		/* end overwrite previous values */
		
		if ($minlen != "")
			$store_rule['min_length'] = intval($_POST['minlength'.$field_order]);
		
		if ($exactlen != "")
			$store_rule['exact_length'] = intval($_POST['exactlength'.$field_order]);

		if ($maxlen != "")
			$store_rule['max_length'] = intval($_POST['maxlength'.$field_order]);
		
		$empty = $_POST['empty'.$field_order];
		$empty == "1" ? $store_rule['can_be_empty'] = true : $store_rule['can_be_empty'] = false;

		$store_rule['edit'] = $_POST['edit'.$field_order];
		
		$email = $_POST['email'.$field_order];
		$email == "1" ? $store_rule['email'] = true : $store_rule['email'] = false;
		$equal = $_POST['equal'.$field_order];
		
		if ($equal != "") {
			$store_rule['equal_to'] = stripslashes($_POST['equalto'.$field_order]);
			
			$equalto_casesens = $_POST['equalto_casesens'.$field_order];
		}
		
		$show_in_reg = $_POST['show_in_reg'.$field_order];
		$show_in_reg == "1" ? $store_rule['show_in_reg'] = true : $store_rule['show_in_reg'] = false;
		
		$show_in_profile = $_POST['show_in_profile'.$field_order];
		$show_in_profile == "1" ? $store_rule['show_in_profile'] = true : $store_rule['show_in_profile'] = false;
		
		$show_in_aeu = $_POST['show_in_aeu'.$field_order];
		$show_in_aeu == "1" ? $store_rule['show_in_aeu'] = true : $store_rule['show_in_aeu'] = false;

		// START CHECKING FOR ERRORS
		if ($name == "")
			$errors['name'] = __("Name not specified", $cimy_uef_domain);
		else if (!stristr($name, " ") === false)
			$errors['name'] = __("Name cannot contains spaces", $cimy_uef_domain);

		if ($label == "")
			$errors['label'] = __("Label not specified", $cimy_uef_domain);

		// max or exact length rule is needed for this type
		if (in_array($type, $rule_maxlen_needed)) {
			if (($maxlen == "") && ($exactlen == ""))
				$errors['maxlength1'] = $exact_or_max_length_capton." ".__("not selected (with this type is necessary)", $cimy_uef_domain);
		}
		
		// max or exact length rule is not needed but it's available for this type
		if (in_array($type, $rule_maxlen)) {
			if ((($maxlen != "") || ($minlen != "")) && ($exactlen != ""))
				$errors['exactlength1'] = __("If you select", $cimy_uef_domain)." ".$exact_length_caption." ".__("you cannot select Min or Max", $cimy_uef_domain);

			// MIN LEN
			if ($minlen != "")
				if (($store_rule['min_length'] < $minLen) || ($store_rule['min_length'] > $maxLen))
					$errors['minlength3'] = $min_length_caption." ".__("should be in the range of", $cimy_uef_domain)." ".$minLen. "-".$maxLen;
			
			// EXACT LEN
			if ($exactlen != "")
				if (($store_rule['exact_length'] < $minLen) || ($store_rule['exact_length'] > $maxLen))
					$errors['exactlength3'] = $exact_length_caption." ".__("should be in the range of", $cimy_uef_domain)." ".$minLen. "-".$maxLen;

			// MAX LEN
			if ($maxlen != "")
				if (($store_rule['max_length'] < $minLen) || ($store_rule['max_length'] > $maxLen))
					$errors['maxlength3'] = $max_length_caption." ".__("should be in the range of", $cimy_uef_domain)." ".$minLen. "-".$maxLen;
		}
		else {
			$minlen = "";
			$exactlen = "";
			$maxlen = "";
		}

		if ($equal != "") {
			if (!isset($store_rule['equal_to']))
				$errors['equalTo'] = __("Equal TO not specified", $cimy_uef_domain);
			else if ($store_rule['equal_to'] == "")
				$errors['equalTo'] = __("Equal TO not specified", $cimy_uef_domain);
			else if ((strtoupper($store_rule['equal_to']) != "YES") && (strtoupper($store_rule['equal_to']) != "NO")) {
				if ($type == "checkbox")
					$errors['equalTo2'] = __("With checkbox type Equal TO can only be", $cimy_uef_domain).": [Yes, No]";

				if ($type == "radio")
					$errors['equalTo2'] = __("With radio type Equal TO can only be", $cimy_uef_domain).": [Yes, No]";
			}
			
			if (($equalto_casesens != "") && (in_array($type, $rule_equalto_case_sensitive)))
				$store_rule['equal_to_case_sensitive'] = true;
			else
				$store_rule['equal_to_case_sensitive'] = false;
		}

		if (($value != "") && (strtoupper($value) != "YES") && (strtoupper($value) != "NO")) {
			if ($type == "checkbox")
				$errors['value'] = __("With checkbox type Value can only be", $cimy_uef_domain).": [Yes, No]";

			if ($type == "radio")
				$errors['value'] = __("With radio type Value can only be", $cimy_uef_domain).": [Yes, No]";
		}

		// IF THERE ARE NO ERRORS THEN GO ON
		if (count($errors) == 0) {
			$exist = array();

			if ($type != "radio") {
				$sql1 = "SELECT id FROM ".$fields_table." WHERE name='".$wpdb->escape($name)."' LIMIT 1";
				$exist = $wpdb->get_row($sql1);
			}

			// SEARCH THE NAME IN THE DATABASE, GO ON ONLY IF DURING EDIT IT WAS THE SAME FIELD
			if ((count($exist) == 0) || (($action == "edit") && ($oldname == $name))) {
				
				// MIN LEN
				if (!in_array($type, $rule_maxlen))
					unset($store_rule['min_length']);

				// EXACT LEN
				if (!in_array($type, $rule_maxlen))
					unset($store_rule['exact_length']);

				// MAX LEN
				if (!in_array($type, $rule_maxlen))
					unset($store_rule['max_length']);
				
				if (!in_array($type, $rule_email))
					$store_rule['email'] = false;
				
				if (!in_array($type, $rule_canbeempty))
					$store_rule['can_be_empty'] = true;

				if (($type == "checkbox") || ($type == "radio"))
					$value = strtoupper($value);
				
				$data = array();
				$data['name'] = $name;
				$data['value'] = $value;
				$data['desc'] = $desc;
				$data['label'] = $label;
				$data['type'] = $type;
				$data['store_rule'] = $store_rule;
				$data['field_order'] = $field_order;
				$data['num_fields'] = $num_fields;
				
				cimy_save_field($action, $fields_table, $data);

				if ($action == "add")
					$results['inserted'] = __("Field inserted correctly", $cimy_uef_domain);
				else if ($action == "edit")
					$results['edit'] = __("Field #", $cimy_uef_domain).$field_order." ".__("updated correctly", $cimy_uef_domain);
			}
			else {
				$errors['namedup'] = __("Name inserted is just in the database, change to another one", $cimy_uef_domain);
			}
		}
	}
	
	// if extra fields table is not present
	if ($num_fields == -1)
		exit;
	
	// do NOT move this line, it's here because should shows also fields just added to the database
	$allFields = get_cimyFields();
	
	?>

	<div class="wrap" id="addfield">
	<h2><?php _e("Add a new Field", $cimy_uef_domain); ?></h2>

	<?php

	// print errors if there are some
	if (count($errors) > 0) {
	?>
		<div class="error">
		<h3><?php _e("ERROR", $cimy_uef_domain); ?></h3>
		<ul>
			<?php 
			foreach ($errors as $error)
				echo "<li>".$error."</li>";
			?>
		</ul>
		<br />
		</div>
	<?php
	}
	?>

	<?php

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
	
	if ($store_rule['min_length'] == 0)
		unset($store_rule['min_length']);
	
	if ($store_rule['exact_length'] == 0)
		unset($store_rule['exact_length']);

	if ($store_rule['max_length'] == 0)
		unset($store_rule['max_length']);
	
	if ($action == "add") {
		// CAN BE MODIFIED OR NOT
		$selected_input[$store_rule['edit']] = ' selected="selected"';

		// NAME
		if ($name != "")
			$selected_input["name"] = $name;
		else
			$selected_input["name"] = '';
	
		// LABEL
		if ($label != "")
			$selected_input["label"] = $label;
		else
			$selected_input["label"] = '';
		
		// VALUE
		if ($value != "")
			$selected_input["value"] = $value;
		else
			$selected_input["value"] = '';
		
		// DESCRIPTION
		if ($desc != "")
			$selected_input["desc"] = $desc;
		else
			$selected_input["desc"] = '';
		
		// TYPE
		if ($type != "")
			$selected_input[$type] = ' selected="selected"';
		else
			$selected_input["text"] = ' selected="selected"';
	
		// MIN LEN
		if ($minlen != "")
			$selected_input["minlen"] = ' checked="checked"';
		else
			$selected_input["minlen"] = '';
		
		if (isset($store_rule['min_length']))
			$selected_input["min_length"] = $store_rule['min_length'];
		else
			$selected_input["min_length"] = '';
		
		// EXACT LEN
		if ($exactlen != "")
			$selected_input["exactlen"] = ' checked="checked"';
		else
			$selected_input["exactlen"] = '';
		
		if (isset($store_rule['exact_length']))
			$selected_input["exact_length"] = $store_rule['exact_length'];
		else
			$selected_input["exact_length"] = '';

		// MAX LEN
		if ($maxlen != "")
			$selected_input["maxlen"] = ' checked="checked"';
		else
			$selected_input["maxlen"] = '';
		
		if (isset($store_rule['max_length']))
			$selected_input["max_length"] = $store_rule['max_length'];
		else
			$selected_input["max_length"] = '';
		
		// EQUAL TO
		if (isset($equal))
			$selected_input["equal"] = ' checked="checked"';
		else
			$selected_input["equal"] = '';
	
		if (isset($store_rule['equal_to']))
			$selected_input["equal_to"] = $store_rule['equal_to'];
		else
			$selected_input["equal_to"] = '';
		
		if (isset($equalto_casesens))
			$selected_input["equal_to_case_sensitive"] = ' checked="checked"';
		else
			$selected_input["equal_to_case_sensitive"] = '';

		// CHECK EMAIL SYNTAX
		if ($store_rule['email'] == true)
			$selected_input["email"] = ' checked="checked"';
		else
			$selected_input["email"] = '';
	}
	// action is not "add"
	else
		$selected_input["ok_edit"] = ' selected="selected"';

	// CAN BE EMPTY
	if (($store_rule['can_be_empty'] == true) || ($action != "add"))
		$selected_input["empty"] = ' checked="checked"';
	else
		$selected_input["empty"] = '';

	// SHOW IN REGISTRATION
	if ((!isset($store_rule['show_in_reg'])) || ($store_rule['show_in_reg'] == true) || ($action != "add"))
		$selected_input["show_in_reg"] = ' checked="checked"';
	else
		$selected_input["show_in_reg"] = '';
	
	// SHOW IN PROFILE
	if ((!isset($store_rule['show_in_profile'])) || ($store_rule['show_in_profile'] == true) || ($action != "add"))
		$selected_input["show_in_profile"] = ' checked="checked"';
	else
		$selected_input["show_in_profile"] = '';


	// SHOW IN AUTHORS AND USERS EXTENDED
	if ((!isset($store_rule['show_in_aeu'])) || ($store_rule['show_in_aeu'] == true) || ($action != "add"))
		$selected_input["show_in_aeu"] = ' checked="checked"';
	else
		$selected_input["show_in_aeu"] = '';
	
	$selected_input["name"] = attribute_escape($selected_input["name"]);
	$selected_input["value"] = attribute_escape($selected_input["value"]);
	$selected_input["label"] = attribute_escape($selected_input["label"]);
	$selected_input["desc"] = attribute_escape($selected_input["desc"]);
	$selected_input["equal_to"] = attribute_escape($selected_input["equal_to"]);
	?>
	
	<form method="post" action="#addfield">
		<p><?php _e("To add a new field you have to choose a name, type and label; optional are value and description. Rules are applied during user registration.", $cimy_uef_domain); ?></p>
		<ul>
			<li><?php _e("With <strong>radio</strong> and <strong>checkbox</strong>: <em>Value</em> and <em>equal TO</em> can only be 'Yes' or 'No' that means 'selected' or 'not selected'", $cimy_uef_domain); ?></li>
			<li><?php _e("With <strong>drop-down</strong>: you have to add all options into label for example: label/item1,item2,item3", $cimy_uef_domain); ?></li>
			<li><?php _e("With <strong>picture</strong>: you can preload a default image putting url in <em>Value</em>; 'min,exact,max size' are in KB; <em>equal TO</em> means max pixel size (width or height) for thumbnail", $cimy_uef_domain); ?></li>
			<li><?php _e("With <strong>picture-url</strong>: you can preload a default image putting url in <em>Value</em>; <em>equal TO</em> means max width pixel size (height will be proportional)", $cimy_uef_domain); ?></li>
			<li><?php _e("With <strong>registration-date</strong>: <em>equal TO</em> means date and time format", $cimy_uef_domain); ?></li>
		</ul>
		<br />

		<table  class="widefat" cellpadding="10">
		<thead align="center">
		<tr>
			<td><h3><?php _e("Name"); ?> - <?php _e("Value"); ?></h3></td>
			<td><h3><?php _e("Type", $cimy_uef_domain); ?></h3></td>
			<td><h3><?php _e("Label", $cimy_uef_domain); ?> - <?php _e("Description"); ?></h3></td>
			<td><h3><?php _e("Rules", $cimy_uef_domain); ?></h3></td>
			<td><h3><?php _e("Actions"); ?></h3></td>
		</tr>
		</thead>
		<tbody id="plugins">
		<tr class="active">
		<td style="vertical-align: middle;">
			<label><strong><?php _e("Name"); ?></strong><br /><input name="name" type="text" value="<?php echo $selected_input["name"]; ?>" maxlength="<?php echo $max_length_name; ?>" /></label><br /><br />
			<label><strong><?php _e("Value"); ?></strong><br /><textarea name="value" rows="2" cols="17"><?php echo $selected_input["value"]; ?></textarea></label>
		</td>
		<td style="vertical-align: middle;">
			<label><strong><?php _e("Type", $cimy_uef_domain); ?></strong><br />
			<select name="type">
			<?php
				foreach($available_types as $this_type) {
					echo '<option value="'.$this_type.'"'.$selected_input[$this_type].'>'.$this_type.'</option>';
					echo "\n";
					
					if (isset($selected_input[$this_type]))
						unset($selected_input[$this_type]);
				}
			?>
			</select>
			</label>
		</td>
		<td style="vertical-align: middle;">
			<label><strong><?php _e("Label", $cimy_uef_domain); ?></strong><br /><textarea name="label" rows="2" cols="18"><?php echo $selected_input["label"]; ?></textarea></label><br /><br />
			<label><strong><?php _e("Description"); ?></strong><br /><textarea name="description" rows="4" cols="18"><?php echo $selected_input["desc"]; ?></textarea></label>
		</td>
		<td style="vertical-align: middle;">
			<!-- MIN LENGTH -->
			<input type="checkbox" name="minlen" value="1"<?php echo $selected_input["minlen"]; ?> /> <?php echo $min_length_caption; ?> [1-<?php echo $maxLen; ?>]: &nbsp;&nbsp;&nbsp;<input type="text" name="minlength" value="<?php echo $selected_input["min_length"]; ?>" maxlength="5" size="5" /><br />
			
			<!-- EXACT LENGTH -->
			<input type="checkbox" name="exactlen" value="1"<?php echo $selected_input["exactlen"]; ?> /> <?php echo $exact_length_caption; ?> [1-<?php echo $maxLen; ?>]: <input type="text" name="exactlength" value="<?php echo $selected_input["exact_length"]; ?>" maxlength="5" size="5" /><br />

			<!-- MAX LENGTH -->
			<input type="checkbox" name="maxlen" value="1"<?php echo $selected_input["maxlen"]; ?> /> <?php echo $max_length_caption; ?> [1-<?php echo $maxLen; ?>]: &nbsp;&nbsp;<input type="text" name="maxlength" value="<?php echo $selected_input["max_length"]; ?>" maxlength="5" size="5" /><br />
			
			<input type="checkbox" name="empty" value="1"<?php echo $selected_input["empty"]; ?> /> <?php _e("Can be empty", $cimy_uef_domain); ?><br />
			<input type="checkbox" name="email" value="1"<?php echo $selected_input["email"]; ?> /> <?php _e("Check for E-mail syntax", $cimy_uef_domain); ?><br />
			
			<select name="edit">
				<option value="ok_edit"<?php echo $selected_input["ok_edit"]; ?>><?php _e("Can be modified", $cimy_uef_domain); ?></option>
				<option value="edit_only_if_empty"<?php echo $selected_input["edit_only_if_empty"]; ?>><?php _e("Can be modified only if empty", $cimy_uef_domain); ?></option>
				<option value="edit_only_by_admin"<?php echo $selected_input["edit_only_by_admin"]; ?>><?php _e("Can be modified only by admin", $cimy_uef_domain); ?></option>
				<option value="edit_only_by_admin_or_if_empty"<?php echo $selected_input["edit_only_by_admin_or_if_empty"]; ?>><?php _e("Can be modified only by admin or if empty", $cimy_uef_domain); ?></option>
				<option value="no_edit"<?php echo $selected_input["no_edit"]; ?>><?php _e("Cannot be modified", $cimy_uef_domain); ?></option>
			<?php
				if (isset($selected_input[$edit]))
					unset($selected_input[$edit]);
			?>
			</select>
			<br />
			<!-- EQUAL TO -->
			<input type="checkbox" name="equal" value="1"<?php echo $selected_input["equal"]; ?> /> <?php _e("Should be equal TO", $cimy_uef_domain); ?>: <input type="text" name="equalto" maxlength="50" value="<?php echo $selected_input["equal_to"]; ?>"/><br />
			<!-- CASE SENSITIVE -->
			&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="equalto_casesens" value="1"<?php echo $selected_input["equal_to_case_sensitive"]; ?> /> <?php _e("Case sensitive", $cimy_uef_domain); ?><br />
			
			<!-- SHOW IN REGISTRATION -->
			<input type="checkbox" name="show_in_reg" value="1"<?php echo $selected_input["show_in_reg"]; ?> /> <?php _e("Show the field in the registration", $cimy_uef_domain); ?><br />
			
			<!-- SHOW IN PROFILE -->
			<input type="checkbox" name="show_in_profile" value="1"<?php echo $selected_input["show_in_profile"]; ?> /> <?php _e("Show the field in User's profile", $cimy_uef_domain); ?><br />
			
			<!-- SHOW IN A&U EXTENDED -->
			<input type="checkbox" name="show_in_aeu" value="1"<?php echo $selected_input["show_in_aeu"]; ?> /> <?php _e("Show the field in A&amp;U Extended menu", $cimy_uef_domain); ?><br />
		</td>
		<td align="center" style="vertical-align: middle;">
			<p class="submit" style="border-width: 0px;">
			<input name="reset" type="reset" value="<?php _e("Clear", $cimy_uef_domain); ?>" /><br /><br />
			<input name="submit" type="submit" value="<?php echo $add_caption ?>" />
			</p>
		</td>
		</tr>
		</tbody>
		</table>
		<br /><br />
	</form>

	</div>

	<script language="JavaScript" type="text/javascript" src="<?php echo bloginfo("url")."/wp-content/plugins/".$cuef_plugin_path; ?>js/invert_sel.js"></script>

<?php
	$wp_fields = get_cimyFields(true);

	cimy_admin_show_extra_fields($wp_fields, $submit_msgs, true);
	cimy_admin_show_extra_fields($allFields, $submit_msgs, false);
}

function cimy_admin_show_extra_fields($allFields, $submit_msgs, $wp_fields) {
	global $cimy_uef_domain, $rule_maxlen, $rule_email, $rule_canbeempty, $rule_equalto, $rule_equalto_case_sensitive, $available_types, $max_length_name, $max_length_label, $max_length_desc, $max_length_value, $max_size_file;
	
	if (!cimy_check_admin("level_10"))
		return;
	
	if ((count($allFields) == 0) && ($wp_fields))
		return;
	
	if ($wp_fields) {
		$disable_it = ' disabled="disabled"';
		$div_id = "wp_extrafields";
		$form_id = "form_wp_fields";
	}
	else {
		$div_id = "extrafields";
		$form_id = "form_extra_fields";
		$disable_it = '';
	}
	
	$add_caption = $submit_msgs['add_caption'];
	$edit_caption = $submit_msgs['edit_caption'];
	$del_caption = $submit_msgs['del_caption'];
	$delSel_caption = $submit_msgs['delSel_caption'];
	$order_caption = $submit_msgs['order_caption'];
	
?>
	<div class="wrap" id="<?php echo $div_id; ?>">
	<h2><?php
		if ($wp_fields)
			_e("WordPress Fields", $cimy_uef_domain);
		else
			_e("Extra Fields", $cimy_uef_domain); ?>
	</h2>

	<form method="post" action="#addfield" id="<?php echo $form_id; ?>">

	<?php
	
	if ($wp_fields)
		echo '<input type="hidden" name="wp_fields" value="1" />';

	if (count($allFields) == 0)
		_e("None!", $cimy_uef_domain);
	else {
		?>
		<p class="submit" style="border-width: 0px; margin-top: 0px;">
		<input type="button" value="<?php _e("Invert selection", $cimy_uef_domain); ?>" onclick="this.value=invert_sel('<?php echo $form_id; ?>', 'check', '<?php _e("Invert selection", $cimy_uef_domain); ?>')" />
		<input name="submit" type="submit" value="<?php echo $order_caption ?>" />
		
		<?php if (!$wp_fields) { ?>
			<input name="submit" type="submit" value="<?php echo $delSel_caption ?>" onclick="return confirm('<?php _e("Are you sure you want to delete field(s) and all data inserted into by users?", $cimy_uef_domain); ?>');" />
		<?php } ?>
		</p>
		<p></p>

		<table class="widefat" cellpadding="10">
		<thead align="center">
		<tr>
			<td><h3><?php _e("Order", $cimy_uef_domain); ?></h3></td>
			<td><h3><?php _e("Name"); ?> - <?php _e("Value"); ?> - <?php _e("Type", $cimy_uef_domain); ?></h3></td>
			<td><h3><?php _e("Label", $cimy_uef_domain); ?> - <?php _e("Description"); ?></h3></td>
			<td><h3><?php _e("Rules", $cimy_uef_domain); ?></h3></td>
			<td><h3><?php _e("Actions"); ?></h3></td>
		</tr>
		</thead>
		<tbody>
		<?php

		$style = "";
		
		foreach ($allFields as $field) {

			$id = $field['ID'];
			$order = $field['F_ORDER'];
			$name = attribute_escape($field['NAME']);
			$value = attribute_escape($field['VALUE']);
			$desc = attribute_escape($field['DESCRIPTION']);
			$label = attribute_escape($field['LABEL']);
			$type = $field['TYPE'];
			$rules = $field['RULES'];
			
			$text = "";
			$checkbox = "";
			$radio = "";

			$dis_maxlength = "";
			$dis_canbeempty = "";
			$dis_checkemail = "";
			$dis_equalto = "";
			$dis_equalto_casesens = "";
			$dis_value = "";

			// disable rules for certain fields
			if (!in_array($type, $rule_maxlen))
				$dis_maxlength = ' disabled="disabled"';
			
			if (!in_array($type, $rule_email))
				$dis_checkemail = ' disabled="disabled"';
			
			if (!in_array($type, $rule_canbeempty))
				$dis_canbeempty = ' disabled="disabled"';
			
			if (!in_array($type, $rule_equalto))
				$dis_equalto = ' disabled="disabled"';

			if (!in_array($type, $rule_equalto_case_sensitive))
				$dis_equalto_casesens = ' disabled="disabled"';

			// set selected type for every field
			$selected_type[$type] = ' selected="selected"';
	
			// MIN LEN
			if (isset($rules['min_length'])) {
				$minlen = ' checked="checked"';
				$minLength = $rules['min_length'];
			}
			else {
				$minlen = "";
				$minLength = "";
			}
			
			// EXACT LEN
			if (isset($rules['exact_length'])) {
				$exactlen = ' checked="checked"';
				$exactLength = $rules['exact_length'];
			}
			else {
				$exactlen = "";
				$exactLength = "";
			}
			
			// MAX LEN
			if (isset($rules['max_length'])) {
				$maxlen = ' checked="checked"';
				$maxLength = $rules['max_length'];
			}
			else {
				$maxlen = "";
				$maxLength = "";
			}
	
			if ($rules['can_be_empty'])
				$emp = ' checked="checked"';
			else
				$emp = "";
	
			if ($rules['email'])
				$email = ' checked="checked"';
			else
				$email = "";
	
			if ($rules['edit'] == 'ok_edit')
				$okedit = ' selected="selected"';
			else
				$okedit = "";
	
			if ($rules['edit'] == 'edit_only_if_empty')
				$editonlyifempty = ' selected="selected"';
			else
				$editonlyifempty = "";
			
			if ($rules['edit'] == 'edit_only_by_admin')
				$editonlybyadmin = ' selected="selected"';
			else
				$editonlybyadmin = "";
			
			if ($rules['edit'] == 'edit_only_by_admin_or_if_empty')
				$editonlybyadminorifempty = ' selected="selected"';
			else
				$editonlybyadminorifempty = "";
	
			if ($rules['edit'] == 'no_edit')
				$noedit = ' selected="selected"';
			else
				$noedit = "";
			
			if (isset($rules['equal_to'])) {
				$equal = ' checked="checked"';
				$equalTo = $rules['equal_to'];
				
				if ($rules['equal_to_case_sensitive'])
					$equalto_casesens = ' checked="checked"';
			}
			else {
				$equal = "";
				$equalTo = "";
				$equalto_casesens = "";
			}
			
			$equalTo = attribute_escape($equalTo);

			if ($rules['show_in_reg'])
				$show_in_reg = ' checked="checked"';
			else
				$show_in_reg = "";

			if ($rules['show_in_profile'])
				$show_in_profile = ' checked="checked"';
			else
				$show_in_profile = "";

			if ($rules['show_in_aeu'])
				$show_in_aeu = ' checked="checked"';
			else
				$show_in_aeu = "";
			
			if ($type == "picture") {
				$min_length_caption = __("Min size", $cimy_uef_domain)." (KB)";
				$exact_length_caption = __("Exact size", $cimy_uef_domain)." (KB)";
				$max_length_caption = __("Max size", $cimy_uef_domain)." (KB)";
				
				// overwrite max length with max size
				$max_length_value = $max_size_file;
			}
			else {
				$min_length_caption = __("Min length", $cimy_uef_domain);
				$exact_length_caption = __("Exact length", $cimy_uef_domain);
				$max_length_caption = __("Max length", $cimy_uef_domain);
			}

			$style = ('class="alternate"' == $style) ? '' : 'class="alternate"';
			?>
			
			<tr <?php echo $style; ?>>
			<td align="center" style="vertical-align: middle;">
				<input name="check<?php echo $order ?>" type="checkbox" value="1" /><br /><br />
				<label><strong><?php _e("Order", $cimy_uef_domain); ?></strong><br />
				<input name="order<?php echo $order ?>" type="text" value="<?php echo $order ?>" maxlength="4" size="3" /></label>
			</td>
			<td style="vertical-align: middle;">
			<?php
			if ($wp_fields) {
			?>
				<input name="name<?php echo $order ?>" type="hidden" value="<?php echo $name ?>" />
				<input name="type<?php echo $order ?>" type="hidden" value="<?php echo $type ?>" />
			<?php
			}
			?>
				<label><strong><?php _e("Name"); ?></strong><br />
				<input name="name<?php echo $order ?>" type="text" value="<?php echo $name ?>" maxlength="<?php echo $max_length_name ?>"<?php echo $disable_it; ?> /></label><br /><br />
				<input name="oldname<?php echo $order ?>" type="hidden" value="<?php echo $name ?>" />
				<label><strong><?php _e("Value"); ?></strong><br />
				<textarea name="value<?php echo $order ?>" rows="2" cols="17"><?php echo $value; ?></textarea></label>
				<br /><br />
				<label><strong><?php _e("Type", $cimy_uef_domain); ?></strong><br />
				<select name="type<?php echo $order ?>"<?php echo $disable_it; ?>>
				<?php 
					foreach($available_types as $this_type) {
						echo '<option value="'.$this_type.'"'.$selected_type[$this_type].'>'.$this_type.'</option>';
						
						if (isset($selected_type[$this_type]))
							unset($selected_type[$this_type]);
						echo "\n";
					}
				?>
				</select>
				</label>
			</td>
			<td style="vertical-align: middle;">
				<label><strong><?php _e("Label", $cimy_uef_domain); ?></strong><br />
				<textarea name="label<?php echo $order ?>" rows="2" cols="18"><?php echo $label; ?></textarea>
				</label><br /><br />
				<label><strong><?php _e("Description"); ?></strong><br />
				<textarea name="description<?php echo $order ?>" rows="4" cols="18"><?php echo $desc ?></textarea>
				</label>
			</td>
			<td style="vertical-align: middle;">
				<!-- MIN LENGTH -->
				<input type="checkbox" name="minlen<?php echo $order ?>" value="1"<?php echo $minlen.$dis_maxlength ?> /> <?php echo $min_length_caption; ?> [1-<?php echo $max_length_value; ?>]: &nbsp;&nbsp;&nbsp;<input type="text" name="minlength<?php echo $order ?>" value="<?php echo $minLength ?>" maxlength="5" size="5"<?php echo $dis_maxlength ?> /><br />

				<!-- EXACT LENGTH -->
				<input type="checkbox" name="exactlen<?php echo $order ?>" value="1"<?php echo $exactlen.$dis_maxlength ?> /> <?php echo $exact_length_caption; ?> [1-<?php echo $max_length_value; ?>]: <input type="text" name="exactlength<?php echo $order ?>" value="<?php echo $exactLength ?>" maxlength="5" size="5"<?php echo $dis_maxlength ?> /><br />
				
				<!-- MAX LENGTH -->
				<input type="checkbox" name="maxlen<?php echo $order ?>" value="1"<?php echo $maxlen.$dis_maxlength ?> /> <?php echo $max_length_caption; ?> [1-<?php echo $max_length_value; ?>]: &nbsp;&nbsp;<input type="text" name="maxlength<?php echo $order ?>" value="<?php echo $maxLength ?>" maxlength="5" size="5"<?php echo $dis_maxlength ?> /><br />
				
				<input type="checkbox" name="empty<?php echo $order ?>" value="1"<?php echo $emp.$dis_canbeempty ?> /> <?php _e("Can be empty", $cimy_uef_domain); ?><br />
				<input type="checkbox" name="email<?php echo $order ?>" value="1"<?php echo $email.$dis_checkemail ?> /> <?php _e("Check for E-mail syntax", $cimy_uef_domain); ?><br />

				<select name="edit<?php echo $order ?>">
				<option value="ok_edit"<?php echo $okedit ?>><?php _e("Can be modified", $cimy_uef_domain); ?></option>
				<option value="edit_only_if_empty"<?php echo $editonlyifempty ?>><?php _e("Can be modified only if empty", $cimy_uef_domain); ?></option>
				<option value="edit_only_by_admin"<?php echo $editonlybyadmin ?>><?php _e("Can be modified only by admin", $cimy_uef_domain); ?></option>
				<option value="edit_only_by_admin_or_if_empty"<?php echo $editonlybyadminorifempty ?>><?php _e("Can be modified only by admin or if empty", $cimy_uef_domain); ?></option>
				<option value="no_edit"<?php echo $noedit ?>><?php _e("Cannot be modified", $cimy_uef_domain); ?></option>
				</select>
				<br />
				
				<!-- EQUAL TO -->
				<input type="checkbox" name="equal<?php echo $order ?>" value="1"<?php echo $equal.$dis_equalto ?> /> <?php _e("Should be equal TO", $cimy_uef_domain); ?>: <input type="text" name="equalto<?php echo $order ?>" maxlength="50" value="<?php echo $equalTo ?>"<?php echo $dis_equalto ?> /><br />
				<!-- CASE SENSITIVE -->
				&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="equalto_casesens<?php echo $order ?>" value="1"<?php echo $equalto_casesens.$dis_equalto_casesens; ?> /> <?php _e("Case sensitive", $cimy_uef_domain); ?><br />

				
				<!-- SHOW IN REGISTRATION -->
				<input type="checkbox" name="show_in_reg<?php echo $order ?>" value="1"<?php echo $show_in_reg ?> /> <?php _e("Show the field in the registration", $cimy_uef_domain); ?><br />
				
				<!-- SHOW IN PROFILE -->
				<input type="checkbox" name="show_in_profile<?php echo $order ?>" value="1"<?php echo $show_in_profile ?> /> <?php _e("Show the field in User's profile", $cimy_uef_domain); ?><br />
				
				<!-- SHOW IN A&U EXTENDED -->
				<input type="checkbox" name="show_in_aeu<?php echo $order ?>" value="1"<?php echo $show_in_aeu ?> /> <?php _e("Show the field in A&amp;U Extended menu", $cimy_uef_domain); ?><br />
			</td>
			<td align="center" style="vertical-align: middle;">
				<p class="submit" style="border-width: 0px;">
				<input name="reset" type="reset" value="<?php _e("Reset", $cimy_uef_domain); ?>" /><br /><br />
				<input name="submit" type="submit" value="<?php echo $edit_caption." #".$order ?>" /><br /><br />
				
				<?php if (!$wp_fields) { ?>
					<input name="submit" type="submit" value="<?php echo $del_caption." #".$order ?>" onclick="return confirm('<?php _e("Are you sure you want to delete field(s) and all data inserted into by users?", $cimy_uef_domain); ?>');" />
				<?php } ?>
				</p>
			</td>
			</tr>
		<?php
		}
		?>
		</tbody>
		</table>
		<?php
	}

	?>
	</form>

	</div>

	<?php
}

function cimy_admin_users_list_page() {
	global $wpdb, $wp_roles, $wpdb_data_table, $cimy_uef_options, $cuef_upload_path, $cimy_uef_domain, $is_mu;

	if (!cimy_check_admin('level_10'))
		return;
	
	if ($is_mu)
		$options = get_site_option($cimy_uef_options);
	else
		$options = get_option($cimy_uef_options);
	
	$extra_fields = get_cimyFields();

	// Query the users
	$wp_user_search = new WP_User_Search($_POST['usersearch'], $_GET['userspage'], $_GET['role']);

	?>
	<div class="wrap">
	
	<form id="posts-filter" action="" method="post">
	<?php if ( $wp_user_search->is_search() ) : ?>
	<h2><?php printf(__('Users Matching "%s"'), wp_specialchars($wp_user_search->search_term)); ?></h2>
	<?php else : ?>
	<h2><?php _e("Authors &amp; Users Extended List", $cimy_uef_domain); ?></h2>
	<?php endif; ?>

	<ul class="subsubsub">
	<?php
	$role_links = array();
	$avail_roles = array();
	$users_of_blog = get_users_of_blog();
	
	//var_dump($users_of_blog);
	foreach ( (array) $users_of_blog as $b_user ) {
		$b_roles = unserialize($b_user->meta_value);
		foreach ( (array) $b_roles as $b_role => $val ) {
			if ( !isset($avail_roles[$b_role]) )
				$avail_roles[$b_role] = 0;
			$avail_roles[$b_role]++;
		}
	}
	
	unset($users_of_blog);

	$current_role = false;
	$class = empty($_GET['role']) ? ' class="current"' : '';
	$role_links[] = "<li><a href=\"users.php?page=au_extended\"$class>" . __('All Users') . "</a>";
	
	foreach ( $wp_roles->get_names() as $role => $name ) {
		if ( !isset($avail_roles[$role]) )
			continue;

		$class = '';

		if ( $role == $_GET['role'] ) {
			$current_role = $_GET['role'];
			$class = ' class="current"';
		}

		$name = translate_with_context($name);
		$name = sprintf(_c('%1$s (%2$s)|user role with count'), $name, $avail_roles[$role]);
		
		$tmp_link = clean_url(add_query_arg('role', $role));
		$role_links[] = "<li><a href=\"$tmp_link\"$class>" . $name . '</a>';
	}
	
	echo implode(' |</li>', $role_links) . '</li>';
	unset($role_links);
?>
	</ul>

	<p id="post-search">
	<input type="text" id="post-search-input" name="usersearch" value="<?php echo attribute_escape($wp_user_search->search_term); ?>" />
	<input type="submit" value="<?php _e( 'Search Users' ); ?>" class="button" />
	</p>
	
	<div class="tablenav">
		<?php if ( $wp_user_search->results_are_paged() ) : ?>
			<div class="tablenav-pages"><?php echo str_replace("?", "?page=au_extended&", $wp_user_search->paging_text); ?></div>
		<?php endif; ?>
	
		<br class="clear" />
	</div>
	
	<br class="clear" />
	<?php if ( is_wp_error( $wp_user_search->search_errors ) ) : ?>
		<div class="error">
			<ul>
			<?php
				foreach ( $wp_user_search->search_errors->get_error_messages() as $message )
					echo "<li>$message</li>";
			?>
			</ul>
		</div>
	
	<?php endif; ?>
	

	<?php if ( $wp_user_search->get_results() ) : ?>
		<?php if ( $wp_user_search->is_search() ) : ?>
			<p><a href="users.php?page=au_extended"><?php _e('&laquo; Back to All Users'); ?></a></p>
		<?php endif;
		
		$wp_scripts = new WP_Scripts();
		$wp_scripts->print_scripts('admin-forms');
		?>

		<table class="widefat" cellpadding="3" cellspacing="3" width="100%">
		
		<tr class="thead">
		<th scope="col" class="check-column"><input type="checkbox" onclick="checkAll(document.getElementById('posts-filter'));" /> </th>
	
		<?php
		if (!in_array("username", $options['aue_hidden_fields'])) {
			echo '<th>'.__("Username").'</th>';
		}
	
		if (!in_array("name", $options['aue_hidden_fields'])) {
			echo '<th>'.__("Name").'</th>';
		}
	
		if (!in_array("email", $options['aue_hidden_fields'])) {
			echo '<th>'.__("E-mail").'</th>';
		}
		
		if (!in_array("role", $options['aue_hidden_fields'])) {
			echo '<th>'.__("Role").'</th>';
		}
	
		if (!in_array("website", $options['aue_hidden_fields'])) {
			echo '<th>'.__("Website").'</th>';
		}
	
		if (!in_array("posts", $options['aue_hidden_fields'])) {
			echo '<th>'.__("Posts").'</th>';
		}
			
		$i = 0;
		if (count($extra_fields) > 0)
			foreach ($extra_fields as $thisField) {
				$rules = $thisField['RULES'];
				if ($rules['show_in_aeu']) {
		
					$i++;
					
					$label = $thisField['LABEL'];
					
					if ($thisField['TYPE'] == "dropdown") {
						$ret = cimy_dropDownOptions($label, $value);
						$label = $ret['label'];
					}
					
					echo "<th>".$label."</th>";
				}
			}
		?>
		
		</tr>
		<?php
		$style = '';
	
		foreach ( $wp_user_search->get_results() as $userid ) {
			$user_object = new WP_User($userid);
				
			$email = $user_object->user_email;
			$url = $user_object->user_url;
			$short_url = str_replace('http://', '', $url);
			$short_url = str_replace('www.', '', $short_url);
				
			if ('/' == substr($short_url, -1))
				$short_url = substr($short_url, 0, -1);
				
			if (strlen($short_url) > 35)
				$short_url =  substr($short_url, 0, 32).'...';
				
			$style = ('class="alternate"' == $style) ? '' : 'class="alternate"';
			$numposts = get_usernumposts( $user_object->ID );
				
			if (0 < $numposts) $numposts = "<a href='edit.php?author=$user_object->ID' title='" . __( 'View posts by this author' ) . "'>$numposts</a>";
			echo "
			<tr $style>
			
			<th scope='row' class='check-column'><input type='checkbox' name='users[]' id='user_{$user_object->ID}' class='$role' value='{$user_object->ID}' /></th>";
			
			if (!in_array("username", $options['aue_hidden_fields'])) {
				
				// produce username clickable
				if ( current_user_can( 'edit_user', $user_object->ID ) ) {
					$current_user = wp_get_current_user();
					
					if ($current_user->ID == $user_object->ID) {
						$edit = 'profile.php';
					} else {
						$edit = clean_url( add_query_arg( 'wp_http_referer', urlencode( clean_url( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), "user-edit.php?user_id=$user_object->ID" ) );
					}
					$edit = "<a href=\"$edit\">$user_object->user_login</a>";
				} else {
					$edit = $user_object->user_login;
				}
				echo "<td><strong>$edit</strong></td>";
			}
	
			if (!in_array("name", $options['aue_hidden_fields'])) {
				echo "<td><label for='user_{$user_object->ID}'>$user_object->first_name $user_object->last_name</label></td>";
			}
	
			if (!in_array("email", $options['aue_hidden_fields'])) {
				echo "<td><a href='mailto:$email' title='" . sprintf(__('e-mail: %s'), $email) . "'>$email</a></td>";
			}
			
			if (!in_array("role", $options['aue_hidden_fields'])) {
				$roles = $user_object->roles;
				$role = array_shift($roles);
				$role_name = translate_with_context($wp_roles->role_names[$role]);
				
				echo "<td>";
				echo $role_name;
				echo '</td>';
			}
	
			if (!in_array("website", $options['aue_hidden_fields'])) {
				echo "<td><a href='$url' title='website: $url'>$short_url</a></td>";
			}
	
			if (!in_array("posts", $options['aue_hidden_fields'])) {
				echo "<td align='right'>$numposts</td>";
			}
	
			// if user has not yet fields in the data table then create them
			if (count($extra_fields) > 0)
				foreach ($extra_fields as $thisField) {
	
					$field_id = $thisField['ID'];
	
					cimy_insert_ExtraFields_if_not_exist($user_object->ID, $field_id);
				}
	
			$ef_db = $wpdb->get_results("SELECT FIELD_ID, VALUE FROM ".$wpdb_data_table." WHERE USER_ID = ".$user_object->ID, ARRAY_A);
	
			$i = 0;
			// print all the content of extra fields if there are some
			if (count($extra_fields) > 0)
				foreach ($extra_fields as $thisField) {
					
					$rules = $thisField['RULES'];
					$type = $thisField['TYPE'];
					$value = $thisField['VALUE'];
						
					if ($rules['show_in_aeu']) {
						$field_id = $thisField['ID'];
	
						foreach ($ef_db as $d_field)
							if ($d_field['FIELD_ID'] == $field_id)
								$field = $d_field['VALUE'];
							
						echo "<td>";
							
						if ($type == "picture-url") {
							if ($field == "")
								$field = $value;
								
							if ($field != "") {
								if (intval($rules['equal_to'])) {
									echo '<a href="'.$field.'">';
									echo '<img src="'.$field.'" alt="picture"'.$size.' width="'.intval($rules['equal_to']).'" height="*" />';
									echo "</a>";
								}
								else {
									echo '<img src="'.$field.'" alt="picture" />';
								}
							
								echo "<br />";
								echo "\n\t\t";
							}
						}
						else if ($type == "picture") {
							if ($field == "")
								$field = $value;
							
							if ($field != "") {
								//$profileuser = get_user_to_edit($user_object->ID);
								//$user_login = $profileuser->user_login;
								
								$user_login = $user_object->user_login;
							
								$value_thumb = cimy_get_thumb_path($field);
								$file_thumb = $cuef_upload_path.$user_login."/".cimy_get_thumb_path(basename($field));
							
								echo "\n\t\t";
							
								if (is_file($file_thumb)) {
									echo '<a href="'.$field.'"><img src="'.$value_thumb.'" alt="picture" /></a><br />';
									echo "\n\t\t";
								}
								else {
									echo '<img src="'.$field.'" alt="picture" /><br />';
										echo "\n\t\t";
								}
							}
						}
						else if ($type == "registration-date") {
							if (isset($rules['equal_to']))
								$registration_date = cimy_get_formatted_date($field, $rules['equal_to']);
							else
								$registration_date = cimy_get_formatted_date($field);
								
							echo $registration_date;
						}
						else
							echo $field;
							
						echo "&nbsp;"."</td>";
					}
				$i++;
				}
			echo '</tr>';
		}
	
		?>
		</table>
				
		<div class="tablenav">
		
			<?php if ( $wp_user_search->results_are_paged() ) : ?>
				<div class="tablenav-pages"><?php echo str_replace("?", "?page=au_extended&", $wp_user_search->paging_text); ?></div>
			<?php endif; ?>
		
			<br class="clear" />
		</div>
	
	<?php endif; ?>
	
	</form>
	
	</div>
	
	<?php
}

function cimy_save_field($action, $table, $data) {
	global $wpdb, $wpdb_wp_fields_table;
	
	if (!cimy_check_admin("level_10"))
		return;
	
	if ($table == $wpdb_wp_fields_table)
		$wp_fields = true;
	
	$name = $wpdb->escape($data['name']);
	$value = $wpdb->escape($data['value']);
	$desc = $wpdb->escape($data['desc']);

	if ($wp_fields)
		$label = $wpdb->escape(__($data['label']));
	else
		$label = $wpdb->escape($data['label']);
	
	$type = $wpdb->escape($data['type']);
	$store_rule = $wpdb->escape(serialize($data['store_rule']));
	$field_order = $wpdb->escape($data['field_order']);
	$num_fields = $wpdb->escape($data['num_fields']);
	
	if ($action == "add")
		$sql = "INSERT INTO ".$table." ";
	else if ($action == "edit")
		$sql = "UPDATE ".$table." ";

	$sql.= "SET name='".$name."', value='".$value."', description='".$desc."', label='".$label."', type='".$type."', rules='".$store_rule."'";

	if ($action == "add")
		$sql.= ", F_ORDER=".($num_fields + 1);
	else if ($action == "edit")
		$sql.= " WHERE F_ORDER=".$field_order;

	$wpdb->query($sql);
}

?>