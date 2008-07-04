<?php

function cimy_register_user_extra_fields($user_id) {
	global $wpdb_data_table, $wpdb, $max_length_value, $fields_name_prefix, $wp_fields_name_prefix, $wp_hidden_fields;
	
	$extra_fields = get_cimyFields();
	$wp_fields = get_cimyFields(true);
	
	$i = 1;
	
	// do first for the WP fields then for EXTRA fields
	while ($i <= 2) {
		if ($i == 1) {
			$are_wp_fields = true;
			$fields = $wp_fields;
			$prefix = $wp_fields_name_prefix;
		}
		else {
			$are_wp_fields = false;
			$fields = $extra_fields;
			$prefix = $fields_name_prefix;
		}
		
		$i++;

		foreach ($fields as $thisField) {

			$type = $thisField["TYPE"];
			$name = $thisField["NAME"];
			$field_id = $thisField["ID"];
			$label = $thisField["LABEL"];
			$rules = $thisField["RULES"];
			$input_name = $prefix.$wpdb->escape($name);
	
			// if flag to view also in the registration is activated
			if ($rules['show_in_reg']) {
				if (isset($_POST[$input_name]))
					$data = stripslashes($_POST[$input_name]);
		
				if ($type == "picture") {
					$data = cimy_manage_upload($input_name, sanitize_user($_POST['user_login']), $rules);
				}
				else {
					if ($type == "picture-url")
						$data = str_replace('../', '', $data);
						
					if (isset($rules['max_length']))
						$data = substr($data, 0, $rules['max_length']);
					else
						$data = substr($data, 0, $max_length_value);
				}
			
				$data = $wpdb->escape($data);
	
				if (!$are_wp_fields) {
					$sql = "INSERT INTO ".$wpdb_data_table." SET USER_ID = ".$user_id.", FIELD_ID=".$field_id.", ";
		
					switch ($type) {
						case 'picture-url':
						case 'picture':
						case 'textarea':
						case 'dropdown':
						case 'password':
						case 'text':
							$field_value = $data;
							break;
			
						case 'checkbox':
							$field_value = $data == '1' ? "YES" : "NO";
							break;
			
						case 'radio':
							$field_value = $data == $field_id ? "selected" : "";
							break;
							
						case 'registration-date':
							$field_value = mktime();
							break;
					}
			
					$sql.= "VALUE='".$field_value."'";
					$wpdb->query($sql);
				}
				else {
					$f_name = strtolower($thisField['NAME']);
					
					$userdata = array();
					$userdata['ID'] = $user_id;
					$userdata[$wp_hidden_fields[$f_name]['post_name']] = $data;
					
					wp_update_user($userdata);
				}
			}
		}
	}
}

function cimy_registration_check() {
	global $wpdb,$errors, $rule_canbeempty, $rule_email, $rule_maxlen, $fields_name_prefix, $wp_fields_name_prefix, $rule_equalto_case_sensitive, $apply_equalto_rule, $cimy_uef_domain;

	$extra_fields = get_cimyFields();
	$wp_fields = get_cimyFields(true);
	
	$i = 1;

	// do first for the WP fields then for EXTRA fields
	while ($i <= 2) {
		if ($i == 1) {
			$fields = $wp_fields;
			$prefix = $wp_fields_name_prefix;
		}
		else {
			$fields = $extra_fields;
			$prefix = $fields_name_prefix;
		}
		
		$i++;

		foreach ($fields as $thisField) {
	
			$field_id = $thisField['ID'];
			$name = $thisField['NAME'];
			$rules = $thisField['RULES'];
			$type = $thisField['TYPE'];
			$label = $thisField['LABEL'];
			$description = $thisField['DESCRIPTION'];
			$input_name = $prefix.$wpdb->escape($name);
			
			if (isset($_POST[$input_name]))
				$value = stripslashes($_POST[$input_name]);
	
			if ($type == "dropdown") {
				$ret = cimy_dropDownOptions($label, $value);
				$label = $ret['label'];
				$html = $ret['html'];
			}
			
			if ($type == "picture") {
				// filesize in Byte transformed in KiloByte
				$file_size = $_FILES[$input_name]['size'] / 1024;
				$file_type = $_FILES[$input_name]['type'];
				$value = $_FILES[$input_name]['name'];
			}
	
			// if flag to view also in the registration is activated
			if ($rules['show_in_reg']) {
	
				switch ($type) {
					case 'checkbox':
						$value == 1 ? $value = "YES" : $value = "NO";
						break;
					case 'radio':
						intval($value) == intval($field_id) ? $value = "YES" : $value = "NO";
						break;
				}
	
				// if the flag can be empty is NOT set OR the field is not empty then other check can be useful, otherwise skip all
				if ((!$rules['can_be_empty']) || ($value != "")) {
					if (($rules['email']) && (in_array($type, $rule_email))) {
						if (!is_email($value))
							$errors['checkemail'.$name] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.' '.__('hasn&#8217;t a correct email syntax.', $cimy_uef_domain);
					}
			
					if ((!$rules['can_be_empty']) && (in_array($type, $rule_canbeempty))) {
						if ($value == '')
							$errors['empty'.$name] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.' '.__('couldn&#8217;t be empty.', $cimy_uef_domain);
					}
			
					if ((isset($rules['equal_to'])) && (in_array($type, $apply_equalto_rule))) {
						
						$equalTo = $rules['equal_to'];
						
						if ((!in_array($type, $rule_equalto_case_sensitive)) || (!$rules['equal_to_case_sensitive'])) {
							
							$value = strtoupper($value);
							$equalTo = strtoupper($equalTo);
						}
	
						if ($value != $equalTo) {
							
							if (($type == "radio") || ($type == "checkbox"))
								$equalTo == "YES" ? $equalTo = __("YES", $cimy_uef_domain) : __("NO", $cimy_uef_domain);
							
							if ($type == "password")
								$equalmsg = " ".__("isn&#8217;t correct", $cimy_uef_domain);
							else
								$equalmsg = ' '.__("should be", $cimy_uef_domain).' '.$equalTo;
							
							$errors['equalto'.$name.$field_id] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.$equalmsg.'.';
						}
					}
					
					// CHECK IF IT IS A REAL PICTURE
					if ($type == "picture") {
						if (stristr($file_type, "image/") === false) {
							$errors['filetype'.$name] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.' '.__('should be an image.', $cimy_uef_domain);
						}
					}
					
					// MIN LEN
					if (isset($rules['min_length'])) {
						$minlen = intval($rules['min_length']);
	
						if ($type == "picture") {
							if ($file_size < $minlen) {
								
								$errors['minlength'.$name] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.' '.__('couldn&#8217;t have size less than', $cimy_uef_domain).' '.$minlen.' KB.';
							}
						}
						else {
							if (strlen($value) < $minlen) {
								
								$errors['minlength'.$name] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.' '.__('couldn&#8217;t have length less than', $cimy_uef_domain).' '.$minlen.'.';
							}
						}
					}
					
					// EXACT LEN
					if (isset($rules['exact_length'])) {
						$exactlen = intval($rules['exact_length']);
						
						if ($type == "picture") {
							if ($file_size != $exactlen) {
								
								$errors['exactlength'.$name] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.' '.__('couldn&#8217;t have size different than', $cimy_uef_domain).' '.$exactlen.' KB.';
							}
						}
						else {
							if (strlen($value) != $exactlen) {
								
								$errors['exactlength'.$name] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.' '.__('couldn&#8217;t have length different than', $cimy_uef_domain).' '.$exactlen.'.';
							}
						}
					}
					
					// MAX LEN
					if (isset($rules['max_length'])) {
						$maxlen = intval($rules['max_length']);
						
						if ($type == "picture") {
							if ($file_size > $maxlen) {
								
								$errors['maxlength'.$name] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.' '.__('couldn&#8217;t have size more than', $cimy_uef_domain).' '.$maxlen.' KB.';
							}
						}
						else {
							if (strlen($value) > $maxlen) {
								
								$errors['maxlength'.$name] = '<strong>'.__("ERROR", $cimy_uef_domain).'</strong>: '.$label.' '.__('couldn&#8217;t have length more than', $cimy_uef_domain).' '.$maxlen.'.';
							}
						}
					}
				}
			}
		}
	}
}

function cimy_registration_form() {
	global $wpdb, $start_cimy_uef_comment, $end_cimy_uef_comment, $rule_maxlen_needed, $fields_name_prefix, $wp_fields_name_prefix, $is_mu;

	$extra_fields = get_cimyFields();
	$wp_fields = get_cimyFields(true);
	
	$tabindex = 21;
	
	echo $start_cimy_uef_comment;
	echo "\t";
	echo '<input type="hidden" name="cimy_post" value="1" />';
	echo "\n";

	$radio_checked = array();

	$i = 1;
	$upload_image_function = false;

	// do first for the WP fields then for EXTRA fields
	while ($i <= 2) {
		if ($i == 1) {
			$fields = $wp_fields;
			$prefix = $wp_fields_name_prefix;
		}
		else {
			$fields = $extra_fields;
			$prefix = $fields_name_prefix;
		}
		
		$i++;
	
		foreach ($fields as $thisField) {
	
			$field_id = $thisField['ID'];
			$name = $thisField['NAME'];
			$rules = $thisField['RULES'];
			$type = $thisField['TYPE'];
			$label = $thisField['LABEL'];
			$description = $thisField['DESCRIPTION'];
			$input_name = $prefix.attribute_escape($name);
			$post_input_name = $prefix.$wpdb->escape($name);
			$maxlen = 0;

			if (isset($_POST[$post_input_name]))
				$value = stripslashes($_POST[$post_input_name]);
			else if (!isset($_POST["cimy_post"])) {
				$value = $thisField['VALUE'];
				
				switch($type) {
	
					case "radio":
						if ($value == "YES")
							$value = $field_id;
						else
							$value = "";
						
						break;
		
					case "checkbox":
						if ($value == "YES")
							$value = "1";
						else
							$value = "";
						
						break;
				}
			}
			else
				$value = "";
			
			$value = attribute_escape($value);
			
			// if flag to view also in the registration is activated
			if ($rules['show_in_reg']) {
				
				if ($is_mu) {
					echo "\t<tr>";
				}
	
				if (($description != "") && ($type != "registration-date")) {
					if ($is_mu) {
						echo '<td colspan="2">';
						echo "\n\t";
					}
						
					echo "\t";
					echo '<p id="'.$prefix.'p_desc_'.$field_id.'" class="desc"><br />'.$description.'</p>';
					echo "\n";
					
					if ($is_mu)
						echo "\t</td></tr>\n\t<tr>";
				}
				
				if ($is_mu)
					echo "<th>\n\t";
		
				echo "\t";
				echo '<p id="'.$prefix.'p_field_'.$field_id.'">';
				echo "\n\t";
	
				switch($type) {
					case "picture-url":
					case "password":
					case "text":
						$obj_label = '<label for="'.$prefix.$field_id.'">'.$label.'</label>';
						$obj_class = ' class="input"';
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
						break;
						
					case "dropdown":
						$ret = cimy_dropDownOptions($label, $value);
						$label = $ret['label'];
						$html = $ret['html'];
						
						$obj_label = '<label for="'.$prefix.$field_id.'">'.$label.'</label>';
						$obj_class = ' class="input"';
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = '';
						$obj_value = '';
						$obj_value2 = $html;
						$obj_checked = "";
						$obj_tag = "select";
						$obj_closing_tag = true;
						break;
						
					case "textarea":
						$obj_label = '<label for="'.$prefix.$field_id.'">'.$label.'</label>';
						$obj_class = ' class="input"';
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = "";
						$obj_value = "";
						$obj_value2 = $value;
						$obj_checked = "";
						$obj_tag = "textarea";
						$obj_closing_tag = true;
						break;
		
					case "checkbox":
						$obj_label = '<label for="'.$prefix.$field_id.'">'.$label.'</label><br />';
						$obj_class = "";
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = ' type="'.$type.'"';
						$obj_value = ' value="1"';
						$obj_value2 = "";
						$value == "1" ? $obj_checked = ' checked="checked"' : $obj_checked = '';
						$obj_tag = "input";
						$obj_closing_tag = false;
						break;
		
					case "radio":
						$obj_label = '<label for="'.$prefix.$field_id.'"> '.$label.'</label>';
						$obj_class = "";
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = ' type="'.$type.'"';
						$obj_value = ' value="'.$field_id.'"';
						$obj_value2 = "";
						$obj_tag = "input";
						$obj_closing_tag = false;
	
						// do not check if another check was done
						if ((intval($value) == intval($field_id)) && (!in_array($name, $radio_checked))) {
							$obj_checked = ' checked="checked"';
							$radio_checked += array($name => true);
						}
						else {
							$obj_checked = '';
						}
						
						break;
						
					case "picture":
						if (!$upload_image_function)
							cimy_change_enc_type("registerform", $input_name);
						
						$upload_image_function = true;
						
						$obj_label = '<label for="'.$prefix.$field_id.'">'.$label.'</label>';
						$obj_class = '';
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = ' type="file"';
						$obj_value = ' value="'.$value.'"';
						$obj_value2 = "";
						$obj_checked = ' onchange="uploadPic();"';
						$obj_tag = "input";
						$obj_closing_tag = false;
						break;
						
					case "registration-date":
						$obj_label = '';
						$obj_class = '';
						$obj_name = ' name="'.$input_name.'"';
						$obj_type = ' type="hidden"';
						$obj_value = ' value="'.$value.'"';
						$obj_value2 = "";
						$obj_checked = "";
						$obj_tag = "input";
						$obj_closing_tag = false;
						break;
				}
	
				$obj_id = ' id="'.$prefix.$field_id.'"';
				$obj_tabindex = ' tabindex="'.strval($tabindex).'"';
				$tabindex++;
	
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
				$form_object = '<'.$obj_tag.$obj_id.$obj_class.$obj_name.$obj_type.$obj_value.$obj_checked.$obj_maxlen.$obj_rowscols.$obj_tabindex;
				
				if ($obj_closing_tag)
					$form_object.= ">".$obj_value2."</".$obj_tag.">";
				else
					$form_object.= " />";
	
				if (($type != "radio") || ($is_mu))
					echo $obj_label;
				
				if ($is_mu) {
					echo "\n\t\t</p>";
					echo "\n\t</th>\n\t<td>\n\t\t";
				}
	
				// write to the html the form object built
				echo $form_object;
	
				if (($type == "radio") && (!$is_mu))
					echo $obj_label;
	
				if ($is_mu)
					echo "\n\t</td></tr>\n";
				else
					echo "\n\t</p>\n";
			}
		}
	}

	echo $end_cimy_uef_comment;
}

?>