<?php

function cimy_extract_ExtraFields() {
	global $wpdb, $user_ID, $wpdb_data_table, $start_cimy_uef_comment, $end_cimy_uef_comment, $rule_profile_value, $cimy_uef_options, $rule_maxlen_needed, $fields_name_prefix, $cuef_upload_path, $is_mu, $cimy_uef_domain;
	
	// if editing a different user (only admin)
	if (isset($_GET['user_id'])) {
		if (!current_user_can('edit_user', $get_user_id))
			return;
		
		$get_user_id = $_GET['user_id'];
	}
	//editin own profile
	else {
		if (!isset($user_ID))
			return;
		
		$get_user_id = $user_ID;
	}
	
	if ($is_mu)
		$options = get_site_option($cimy_uef_options);
	else
		$options = get_option($cimy_uef_options);
	
	$extra_fields = get_cimyFields();

	if (!empty($extra_fields)) {
		$upload_image_function = false;

		echo $start_cimy_uef_comment;

		echo '<br clear="all" />';
		echo "\n";
		echo "<h2>".__("Extra Fields", $cimy_uef_domain)."</h2>\n";
		
		foreach ($extra_fields as $thisField) {
	
			$field_id = $thisField['ID'];
	
			cimy_insert_ExtraFields_if_not_exist($get_user_id, $field_id);
		}
	
		$ef_db = $wpdb->get_results("SELECT FIELD_ID, VALUE FROM ".$wpdb_data_table." WHERE USER_ID = ".$get_user_id, ARRAY_A);

		$radio_checked = array();

		$i = 0;
		$pos = 0;
		$num_fieldset = 0;
		
		if ($options['fieldset_title'] != "")
			$fieldset_titles = explode(',', $options['fieldset_title']);
		else
			$fieldset_titles = array();
		
		echo "<fieldset>\n";
		
		if (isset($fieldset_titles[$num_fieldset]))
			echo "<legend>".$fieldset_titles[$num_fieldset]."</legend>\n";
		
		foreach ($extra_fields as $thisField) {
	
			$field_id = $thisField['ID'];
			$name = $thisField['NAME'];
			$rules = $thisField['RULES'];
			$type = $thisField['TYPE'];
			$label = $thisField['LABEL'];
			$description = $thisField['DESCRIPTION'];
			$input_name = $fields_name_prefix.attribute_escape($name);
			
			if ($rules['show_in_profile']) {
	
				foreach ($ef_db as $d_field) {
					if ($d_field['FIELD_ID'] == $field_id)
						$value = $d_field['VALUE'];
				}
	
				$old_value = $value;
				
				// if nothing is inserted and field admin default value then assign it
				if (in_array($type, $rule_profile_value)) {
					if ($value == "")
						$value = $thisField['VALUE'];
				}

				if ($i == $options['items_per_fieldset']) {
					echo "</fieldset>\n";
					
					$num_fieldset++;

					if ($pos == 1) {
						echo '<br clear="all" />';
						echo "\n";
						
						$pos = 0;
					}
					else
						$pos = 1;
					
					echo "<fieldset>\n";
					
					if (isset($fieldset_titles[$num_fieldset]))
						echo "<legend>".$fieldset_titles[$num_fieldset]."</legend>\n";
					
					$i = 0;
				}
				
				$i++;

				if ($description != "") {
					echo "\t";
					echo '<p id="'.$fields_name_prefix.'p_desc_'.$field_id.'" class="desc"><br />'.$description.'</p>';
					echo "\n";
				}
		
				echo "\t";
				echo '<p id="'.$fields_name_prefix.'p_field_'.$field_id.'">';
				echo "\n\t";
				
				$value = attribute_escape($value);
	
				switch($type) {
					case "picture-url":
					case "password":
					case "text":
						$obj_label = '<label for="'.$fields_name_prefix.$field_id.'">'.$label.'</label><br />';
						$obj_name = ' name="'.$input_name.'"';
						
						if ($type == "picture-url")
							$obj_type = ' type="text"';
						else
							$obj_type = ' type="'.$type.'"';

						$obj_value = ' value="'.$value.'"';
						$obj_value2 = "";
						$obj_checked = "";
						$obj_tag = "input";
						$obj_closing_tag = false;
						$obj_style = "";
						
						if ((($old_value != "") && ($rules['edit'] == 'edit_only_if_empty'))
						|| (($old_value != "") &&  (!current_user_can('level_10')) && ($rules['edit'] == 'edit_only_by_admin_or_if_empty'))
						|| ($rules['edit'] == 'no_edit')
						|| (($rules['edit'] == 'edit_only_by_admin') && (!current_user_can('level_10'))))
							$obj_disabled = ' disabled="disabled"';
						else
							$obj_disabled = "";
							
						break;
						
					case "textarea":
						$obj_label = '<label for="'.$fields_name_prefix.$field_id.'">'.$label.'</label><br />';
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = "";
						$obj_value = "";
						$obj_value2 = $value;
						$obj_checked = "";
						$obj_tag = "textarea";
						$obj_closing_tag = true;
						$obj_style = "";
						
						if ((($old_value != "") && ($rules['edit'] == 'edit_only_if_empty'))
						|| (($old_value != "") &&  (!current_user_can('level_10')) && ($rules['edit'] == 'edit_only_by_admin_or_if_empty'))
						|| ($rules['edit'] == 'no_edit')
						|| (($rules['edit'] == 'edit_only_by_admin') && (!current_user_can('level_10'))))
							$obj_disabled = ' disabled="disabled"';
						else
							$obj_disabled = "";
	
						break;
						
					case "dropdown":
						$ret = cimy_dropDownOptions($label, $value);
						$label = $ret['label'];
						$html = $ret['html'];
						
						$obj_label = '<label for="'.$fields_name_prefix.$field_id.'">'.$label.'</label><br />';
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = '';
						$obj_value = '';
						$obj_value2 = $html;
						$obj_checked = "";
						$obj_tag = "select";
						$obj_closing_tag = true;
						$obj_style = "";
					
						if ((($old_value != "") && ($rules['edit'] == 'edit_only_if_empty'))
						|| (($old_value != "") &&  (!current_user_can('level_10')) && ($rules['edit'] == 'edit_only_by_admin_or_if_empty'))
						|| ($rules['edit'] == 'no_edit')
						|| (($rules['edit'] == 'edit_only_by_admin') && (!current_user_can('level_10'))))
							$obj_disabled = ' disabled="disabled"';
						else
							$obj_disabled = "";
						
						break;
						
					case "checkbox":
						$obj_label = '<label for="'.$fields_name_prefix.$field_id.'">'.$label.'</label><br />';
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = ' type="'.$type.'"';
						$obj_value = ' value="1"';
						$obj_value2 = "";
						$value == "YES" ? $obj_checked = ' checked="checked"' : $obj_checked = '';
						$obj_tag = "input";
						$obj_closing_tag = false;
						$obj_style = ' style="width:auto; border:0; background:white;"';
						
						if (($rules['edit'] == 'no_edit')
						|| ((($rules['edit'] == 'edit_only_by_admin') || ($rules['edit'] == 'edit_only_by_admin_or_if_empty')) && (!current_user_can('level_10'))))
							$obj_disabled = ' disabled="disabled"';
						else
							$obj_disabled = "";
	
						break;
		
					case "radio":
						$obj_label = '<label for="'.$fields_name_prefix.$field_id.'"> '.$label.'</label><br />';
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = ' type="'.$type.'"';
						$obj_value = ' value="'.$field_id.'"';
						$obj_value2 = "";
						$obj_tag = "input";
						$obj_closing_tag = false;
						$obj_style = ' style="width:auto; border:0; background:white;"';
	
						if (($rules['edit'] == 'no_edit')
						|| ((($rules['edit'] == 'edit_only_by_admin') || ($rules['edit'] == 'edit_only_by_admin_or_if_empty')) && (!current_user_can('level_10'))))
							$obj_disabled = ' disabled="disabled"';
						else
							$obj_disabled = "";
	
						if ($value == "")
							$obj_checked = '';
						else
							$obj_checked.= ' checked="checked"';
	
						break;
						
					case "picture":
						if (!$upload_image_function)
							cimy_change_enc_type("profile", $input_name);

						$upload_image_function = true;
						
						$obj_label = '<label for="'.$fields_name_prefix.$field_id.'">'.$label.'</label><br />';
						$obj_class = '';
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = ' type="file"';
						$obj_value = ' value=""';
						$obj_value2 = '';
						$obj_checked = "";
						$obj_tag = "input";
						$obj_closing_tag = false;
						$obj_style = ' onchange="uploadPic();"';
						
						if ((($old_value != "") && ($rules['edit'] == 'edit_only_if_empty'))
						|| (($old_value != "") &&  (!current_user_can('level_10')) && ($rules['edit'] == 'edit_only_by_admin_or_if_empty'))
						|| (($rules['edit'] == 'no_edit'))
						|| (($rules['edit'] == 'edit_only_by_admin') && (!current_user_can('level_10'))))
							$obj_disabled = ' disabled="disabled"';
						else
							$obj_disabled = "";
						
						break;
						
					case "registration-date":
						if (isset($rules['equal_to']))
							$registration_date = cimy_get_formatted_date($value, $rules['equal_to']);
						else
							$registration_date = cimy_get_formatted_date($value);
					
						$obj_label = '<label>'.$label.'<br />'.$registration_date.'</label>';

						break;
				}
	
				
				$obj_id = ' id="'.$fields_name_prefix.$field_id.'"';
				$obj_class = '';
	
				$obj_maxlen = "";
	
				if ((in_array($type, $rule_maxlen_needed)) && ($type != "picture")) {
					if (isset($rules['max_length'])) {
						$obj_maxlen = ' maxlength="'.$rules['max_length'].'"';
					} else if (isset($rules['exact_length'])) {
						$obj_maxlen = ' maxlength="'.$rules['exact_length'].'"';
					}
				}
				
				if ($type == "textarea")
					$obj_rowscols = ' rows="3" cols="25"';
				else
					$obj_rowscols = '';
		
				echo "\t";
				$form_object = '<'.$obj_tag.$obj_id.$obj_class.$obj_name.$obj_type.$obj_value.$obj_checked.$obj_maxlen.$obj_rowscols.$obj_style.$obj_disabled;
				
				if ($obj_closing_tag)
					$form_object.= ">".$obj_value2."</".$obj_tag.">";
				else
					$form_object.= " />";
	
				if ($type != "radio")
					echo $obj_label;
	
				if (($type == "picture") && ($value != "")) {
					$profileuser = get_user_to_edit($get_user_id);
					$user_login = $profileuser->user_login;
					
					$value_thumb = cimy_get_thumb_path($value);
					$file_thumb = $cuef_upload_path.$user_login."/".cimy_get_thumb_path(basename($value));
					
					echo "\n\t\t";
					
					if (is_file($file_thumb)) {
						echo '<a href="'.$value.'"><img src="'.$value_thumb.'" alt="picture" /></a><br />';
						echo "\n\t\t";
					}
					else {
						echo '<img src="'.$value.'" alt="picture" /><br />';
						echo "\n\t\t";
					}
					
					// if there is no image or there is the default one then disable delete button
					if ($old_value == "") {
						$dis_delete_img = ' disabled="disabled"';
					}
					// else if there is an image and it's not the default one
					else {
						// take the "can be modified" rule just set before
						$dis_delete_img = $obj_disabled;
						
						echo '<input type="hidden" name="'.$input_name.'_oldfile" value="'.basename($value).'" />';
						echo "\n\t\t";
					}
					
					echo '<input type="checkbox" name="'.$input_name.'_del" value="1" style="width:auto; border:0; background:white;"'.$dis_delete_img.' />';
					echo " Delete the picture<br /><br />";
					
					echo "\n\t\tUpdate the picture<br />";
					echo "\n\t\t";
				}
				
				if ($type == "picture-url") {
					if ($value != "") {
						if (intval($rules['equal_to'])) {
							echo '<a href="'.$value.'">';
							echo '<img src="'.$value.'" alt="picture"'.$size.' width="'.intval($rules['equal_to']).'" height="*" />';
							echo "</a>";
						}
						else {
							echo '<img src="'.$value.'" alt="picture" />';
						}
						
						echo "<br />";
						echo "\n\t\t";
					}
					
					echo "<br />Picture URL:<br />\n\t\t";
				}

				// write to the html the form object built
				if ($type != "registration-date")
					echo $form_object;
	
				if ($type == "radio")
					echo $obj_label;
				
				echo "\n\t</p>\n";
			}
		}
		
		echo '</fieldset>';
		
		echo $end_cimy_uef_comment;
	}
}

function cimy_update_ExtraFields() {
	global $wpdb, $wpdb_data_table, $user_ID, $max_length_value, $fields_name_prefix;

	// if editing a different user (only admin)
	if (isset($_POST['user_id'])) {
		if (!current_user_can('edit_user', $get_user_id))
			return;
		
		$get_user_id = $_POST['user_id'];
	}
	//editin own profile
	else {
		if (!isset($user_ID))
			return;

		$get_user_id = $user_ID;
	}

	$extra_fields = get_cimyFields();

	$query = "UPDATE ".$wpdb_data_table." SET VALUE=CASE FIELD_ID";
	$i = 0;

	$field_ids = "";

	foreach ($extra_fields as $thisField) {

		$field_id = $thisField["ID"];
		$name = $thisField["NAME"];
		$type = $thisField["TYPE"];
		$label = $thisField["LABEL"];
		$rules = $thisField["RULES"];
		$input_name = $fields_name_prefix.$wpdb->escape($name);
		
		cimy_insert_ExtraFields_if_not_exist($get_user_id, $field_id);

		// if flag to view also in the profile is activated
		if ($rules['show_in_profile']) {
			if ((isset($_POST[$input_name])) && ($type != "picture")) {
				$field_value = stripslashes($_POST[$input_name]);
				
				if ($type == "picture-url")
					$field_value = str_replace('../', '', $field_value);
	
				if (isset($rules['max_length']))
					$field_value = substr($field_value, 0, $rules['max_length']);
				else
					$field_value = substr($field_value, 0, $max_length_value);
	
				$field_value = $wpdb->escape($field_value);
	
				if ($i > 0)
					$field_ids.= ", ";
				else
					$i = 1;
	
				$field_ids.= $field_id;
	
				$query.= " WHEN ".$field_id." THEN ";
		
				switch ($type) {
					case 'picture-url':
					case 'textarea':
					case 'dropdown':
					case 'password':
					case 'text':
						$value = "'".$field_value."'";
						break;
	
					case 'checkbox':
						$value = $field_value == '1' ? "'YES'" : "'NO'";
						break;
	
					case 'radio':
						$value = $field_value == $field_id ? "'selected'" : "''";
						break;
				}
	
				$query.= $value;
			}
			// when a checkbox is not selected then it isn't present in $_POST at all
			// file input in html also is not present into $_POST at all so manage here
			else {
				$rules = $thisField['RULES'];
	
				if ($type == 'picture') {
					$profileuser = get_user_to_edit($get_user_id);
					$user_login = $profileuser->user_login;
					
					if (isset($_POST[$input_name.'_del']))
						$delete_file = true;
					else
						$delete_file = false;
					
					if (isset($_POST[$input_name.'_oldfile']))
						$old_file = stripslashes($_POST[$input_name.'_oldfile']);
					else
						$old_file = false;
					
					$field_value = cimy_manage_upload($input_name, $user_login, $rules, $old_file, $delete_file);
					
					if (($field_value != "") || ($delete_file)) {
						if ($i > 0)
							$field_ids.= ", ";
						else
							$i = 1;
				
						$field_ids.= $field_id;
						
						$value = "'".$field_value."'";
						
						$query.= " WHEN ".$field_id." THEN ";
						$query.= $value;
					}
				}
				
				if ($type == 'checkbox') {
					// if can be editable then write NO
					// there is no way to understand if was YES or NO previously
					// without adding other hidden inputs so write always
					if (($rules['edit'] == "ok_edit") || (($rules['edit'] == 'edit_only_by_admin') && (current_user_can('level_10')))) {
						if ($i > 0)
							$field_ids.= ", ";
						else
							$i = 1;
			
						$field_ids.= $field_id;
			
						$query.= " WHEN ".$field_id." THEN ";
						$query.= "'NO'";
					}
				}
			}
		}
	}

	if ($i > 0) {
		$query.=" ELSE FIELD_ID END WHERE FIELD_ID IN(".$field_ids.") AND USER_ID = ".$get_user_id;

		// $query WILL BE: UPDATE <table> SET VALUE=CASE FIELD_ID WHEN <field_id1> THEN <value1> [WHEN ... THEN ...] ELSE FIELD_ID END WHERE FIELD_ID IN(<field_id1>, [<field_id2>...]) AND USER_ID=<user_id>
		$wpdb->query($query);
	}
}

?>