<?php

function get_cimyFields($wp_fields=false) {
	global $wpdb_fields_table, $wpdb_wp_fields_table, $wpdb;
	
	if ($wp_fields)
		$table = $wpdb_wp_fields_table;
	else
		$table = $wpdb_fields_table;

	// if tables exist then read all fields else array empty, will be read after the creation
	if($wpdb->get_var("SHOW TABLES LIKE '".$table."'") == $table) {
		$sql = "SELECT * FROM ".$table." ORDER BY F_ORDER";
		$extra_fields = $wpdb->get_results($sql, ARRAY_A);
	
		if (!isset($extra_fields))
			$extra_fields = array();
		else {
			for ($i = 0; $i < count($extra_fields); $i++) {
				$extra_fields[$i]['RULES'] = unserialize($extra_fields[$i]['RULES']);
			}
			
			$extra_fields = $extra_fields;
		}
	}
	else
		$extra_fields = array();

	return $extra_fields;
}

function get_cimyFieldValue($user_id, $field_name, $field_value=false) {
	global $wpdb, $wpdb_data_table, $wpdb_fields_table, $cimy_uef_options, $is_mu;
	
	if ($is_mu)
		$options = get_site_option($cimy_uef_options);
	else
		$options = get_option($cimy_uef_options);
	
	$sql_field_value = "";
	
	if ($options['disable_cimy_fieldvalue'])
		return "Function disabled! Enable it via options page.";

	if ((!isset($user_id)) || (!isset($field_name)))
		return NULL;
	
	if ($field_name) {
		$field_name = strtoupper($field_name);
		$field_name = $wpdb->escape($field_name);
	}
	
	if ($field_value) {
		if (is_array($field_value)) {
			if (isset($field_value['value'])) {
				$sql_field_value = $wpdb->escape($field_value['value']);
				
				if ($field_value['like'])
					$sql_field_value = " AND data.VALUE LIKE '%".$sql_field_value."%'";
				else
					$sql_field_value = " AND data.VALUE='".$sql_field_value."'";
			}
		} else {
		
			$field_value = $wpdb->escape($field_value);
			$sql_field_value = " AND data.VALUE='".$field_value."'";
		}
	}

	if ($user_id) {
		$user_id = intval($user_id);
		
		if (!$user_id)
			return NULL;
	}
	
	// FIELD_NAME and USER_ID provided
	if (($field_name) && ($user_id)) {
		/*
			$sql will be:
		
			SELECT	efields.LABEL,
				efields.TYPE,
				data.VALUE
		
			FROM 	<wp users table> as users,
				<uef data table> as data
		
			JOIN	<uef fields table> as efields
		
			ON	efields.id=data.field_id
		
			WHERE	efields.name=<field_name>
				AND data.USER_ID=<user_id>
				AND efields.TYPE!='password'
				AND (efields.TYPE!='radio' OR data.VALUE!='')
				[AND data.VALUE=<field_value>]
		*/
		$sql = "SELECT efields.LABEL, efields.TYPE, data.VALUE FROM ".$wpdb->users." as users, ".$wpdb_data_table." as data JOIN ".$wpdb_fields_table." as efields ON efields.id=data.field_id WHERE efields.name='".$field_name."' AND data.USER_ID=".$user_id." AND efields.TYPE!='password' AND (efields.TYPE!='radio' OR data.VALUE!='')".$sql_field_value;
	}
	
	// only USER_ID provided
	if ((!$field_name) && ($user_id)) {
		/*
			$sql will be:
		
			SELECT	efields.LABEL,
				efields.TYPE,
				efields.NAME,
				data.VALUE
		
			FROM 	<uef data table> as data
		
			JOIN	<uef fields table> as efields
		
			ON	efields.id=data.field_id
		
			WHERE	AND data.USER_ID=<user_id>
				AND efields.TYPE!='password'
				AND (efields.TYPE!='radio' OR data.VALUE!='')
				[AND data.VALUE=<field_value>]
		
			ORDER BY efields.F_ORDER
		*/
		$sql = "SELECT efields.LABEL, efields.TYPE, efields.NAME, data.VALUE FROM ".$wpdb_data_table." as data JOIN ".$wpdb_fields_table." as efields ON efields.id=data.field_id WHERE data.USER_ID=".$user_id." AND efields.TYPE!='password' AND (efields.TYPE!='radio' OR data.VALUE!='')".$sql_field_value." ORDER BY efields.F_ORDER";
	}
	
	// only FIELD_NAME provided
	if (($field_name) && (!$user_id)) {
		/*
			$sql will be:
		
			SELECT	efields.LABEL,
				efields.TYPE,
				users.user_login,
				data.VALUE
		
			FROM 	<wp users table> as users,
				<uef data table> as data
		
			JOIN	<uef fields table> as efields
		
			ON	efields.id=data.field_id
		
			WHERE	efields.name=<field_name>
				AND data.USER_ID=users.ID
				AND efields.TYPE!='password'
				AND (efields.TYPE!='radio' OR data.VALUE!='')
				[AND data.VALUE=<field_value>]
		
			ORDER BY users.user_login
		*/
		$sql = "SELECT efields.LABEL, efields.TYPE, users.user_login, data.VALUE FROM ".$wpdb->users." as users, ".$wpdb_data_table." as data JOIN ".$wpdb_fields_table." as efields ON efields.id=data.field_id WHERE efields.name='".$field_name."' AND users.ID=data.USER_ID AND efields.TYPE!='password' AND (efields.TYPE!='radio' OR data.VALUE!='')".$sql_field_value." ORDER BY users.user_login";
	}
	
	// nothing provided
	if ((!$field_name) && (!$user_id)) {
		/*
			$sql will be:
		
			SELECT	users.user_login,
				efields.NAME,
				efields.LABEL,
				efields.TYPE,
				data.VALUE
		
			FROM 	<wp users table> as users,
				<uef data table> as data
		
			JOIN	<uef fields table> as efields
		
			ON	efields.id=data.field_id
		
			WHERE	data.USER_ID=users.ID
				AND efields.TYPE!='password'
				AND (efields.TYPE!='radio' OR data.VALUE!='')
				[AND data.VALUE=<field_value>]
		
			ORDER BY users.user_login,
				efields.F_ORDER
		*/
		$sql = "SELECT users.user_login, efields.NAME, efields.LABEL, efields.TYPE, data.VALUE FROM ".$wpdb->users." as users, ".$wpdb_data_table." as data JOIN ".$wpdb_fields_table." as efields ON efields.id=data.field_id WHERE users.ID=data.USER_ID AND efields.TYPE!='password' AND (efields.TYPE!='radio' OR data.VALUE!='')".$sql_field_value." ORDER BY users.user_login, efields.F_ORDER";
	}

	$field_data = $wpdb->get_results($sql, ARRAY_A);
	
	if (isset($field_data)) {
		if ($field_data != NULL)
			$field_data = $field_data;
	}
	else
		return NULL;
	
	$field_data = cimy_change_radio_labels($field_data);
			
	if (($field_name) && ($user_id))
		$field_data = $field_data[0]['VALUE'];
	

	return $field_data;
}

function cimy_change_radio_labels($field_data) {
	$i = 0;
	
	while ($i < count($field_data)) {
		if ($field_data[$i]['TYPE'] == "radio") {
			$field_data[$i]['VALUE'] = $field_data[$i]['LABEL'];
		}
		else if ($field_data[$i]['TYPE'] == "dropdown") {
			$ret = cimy_dropDownOptions($field_data[$i]['LABEL'], false);
			
			$field_data[$i]['LABEL'] = $ret['label'];
		}
		
		$i++;
	}
	
	return $field_data;
}

function cimy_get_formatted_date($value, $date_format="%d %B %Y @%H:%M") {
	$locale = get_locale();

	if (stristr($locale, ".") === false)
		$locale2 = $locale.".utf8";
	else
		$locale2 = "";

	setlocale(LC_TIME, $locale, $locale2);

	if (($value == "") || (!isset($value)))
		$registration_date = "";
	else
		$registration_date = strftime($date_format, intval($value));

	return $registration_date;
}

function cimy_dropDownOptions($values, $selected) {
	
	$label_pos = strpos($values, "/");
	
	if ($label_pos) {
		$label = substr($values, 0, $label_pos);
		$values = substr($values, $label_pos + 1);
	}
	else
		$label = "";
	
	$items = explode(",",$values);
	$html_options = "";
	
	foreach ($items as $item) {
		$html_options.= "\n\t\t\t";
		$html_options.= '<option value="'.$item.'"';
	
		if  (isset($selected))
			if ($selected == $item)
				$html_options.= ' selected="selected"';

		$html_options.= ">".$item."</option>";
	}
	
	$ret = array();
	$ret['html'] = $html_options;
	$ret['label'] = $label;
	
	return $ret;
}

function cimy_get_thumb_path($file_path) {
	$file_path_purename = substr($file_path, 0, strrpos($file_path, "."));
	$file_path_ext = substr($file_path, strlen($file_path_purename));
	$file_thumb_path = $file_path_purename.".thumbnail".$file_path_ext;
	
	return $file_thumb_path;
}

function cimy_check_admin($permission) {
	global $is_mu;
	
	if (!current_user_can($permission))
		return false;
	
	if ($is_mu) {
		global $blog_id;
		
		if ($blog_id != 1)
			return false;
	}
	
	return true;
}

function cimy_change_enc_type($form_name, $field_name) {
	global $cimy_uef_domain;
	?>
	
	<script type="text/javascript" language="javascript">
	<!--
	
	function uploadPic() {
		document.<?php echo $form_name; ?>.enctype = "multipart/form-data";
		var upload = document.<?php echo $form_name.".".$field_name; ?>.value;
		upload = upload.toLowerCase();
		var ext1 = upload.substring((upload.length-4),(upload.length));
		var ext2 = upload.substring((upload.length-5),(upload.length));
	
		if ((ext1 != '.gif') && (ext1 != '.png') && (ext1 != '.jpg') && (ext2 != '.jpeg') && (ext2 != '.tiff')) {
			alert('<?php _e("Please upload an image with one of the following extensions", $cimy_uef_domain);?>: gif png jpg jpeg tiff');
		}
	}
	//-->
	</script>
	
	<?php
}

function cimy_invert_selection() {

	?>
	<script type="text/javascript" language="javascript">

	var formblock;
	var forminputs;

	function invert_sel(formname, name, label) {
		formblock = document.getElementById(formname);
		forminputs = formblock.getElementsByTagName('input');
	
		for (i = 0; i < forminputs.length; i++) {
			// regex here to check name attribute
			var regex = new RegExp(name, "i");
	
			if (regex.test(forminputs[i].getAttribute('name'))) {
				
				if (forminputs[i].checked == false) {
					forminputs[i].checked = true;
				} else {
					forminputs[i].checked = false;
				}
			}
		}
	
		return label;
	}
	
	</script>
	<?php
}

?>