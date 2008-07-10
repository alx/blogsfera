Cimy User Extra Fields

WordPress is becoming more than ever a tool to open blog/websites and CMSs in an easier way. Users are increasing day by day; one of the limits however is the restricted and predefined fields that are available in the registered users profile: it is difficult for example to ask for the acceptance of "Terms and Conditions of Use" or "Permission to use personal data".
It's also possible to set a password during registration combined to equalTO rule, only people who knows the password can register.

We have developed a WordPress plug-in to do this.

There are some plug-ins that do something similar, but this one wants to focus on giving the administrator the possibility to add all fields needed, on the rules that can be defined for each field, and in giving the possibility to both administration and the user to change the data inserted.

The plug-in adds two new menu voices in the admin for the administrator and two for users.

Two new menus are:

    * "Users-> A&U Extended" lets you show users lists with the new fields that are created
    * "Options-> Cimy User Extra Fields" lets administrators add as many new fields as are needed to the users' profile, giving the possibility to set some interesting rules.

Rules are:

    * min/exact/max length admitted
	[only for text, textarea, password, picture, picture-url]

    * field can be empty
	[only for text, textarea, password, picture, picture-url, dropdown]

    * check for e-mail address syntax
	[only for text, textarea, password]

    * field can be modified after the registration
	[only for text, textarea, password, picture, picture-url, checkbox, radio and dropdown]
	[for radio and checkbox 'edit_only_if_empty' has no effects and 'edit_only_by_admin_or_if_empty' has the same effect as edit_only_by_admin]

    * field equal to some value (for example accept terms and conditions)
	[all]

      * equal to can be or not case sensitive
	[only for text, textarea, password, dropdown]

    * field can be hidden during registration
	[all]

    * field can be hidden in user's profile
	[all]

    * field can be hidden in A&U Extended page
	[all]

New fields will be visible in the profile and in the registration.
As for now the plug-in supports: text, textarea, password, checkbox, radio and drop-down fields, future versions can have more.

Bugs or suggestions can be mailed at: cimmino.marco@gmail.com

REQUIREMENTS:
PHP >= 4.3.0
WORDPRESS >= 2.5.x
MYSQL >= 4.0


INSTALLATION:
- just copy whole Cimy_user_extra_fields subdir into your plug-in directory and activate it

UPDATE FROM A PREVIOUS VERSION:
- always deactivate the plug-in and reactivate after the update


FUNCTIONS USEFUL FOR YOUR THEMES OR TEMPLATES:

[Function get_cimyFieldValue]
NOTE 1: to use this function first you have to enable it via options page.
NOTE 2: password fields values will not be returned for security reasons

USAGE:
$value = get_cimyFieldValue($user_id, $field_name, [$field_value]);

In ALL cases if an error is occured or there are no matching results from the call then NULL is returned.


This function is all you need to retrieve extra fields values, but in order to retrieve all power from it you have to understand all different ways that can be used.


CASE 1:
get an extra field value from a specific user

PARAMETERS: pass user_id as first parameter and field_name as second
RETURNED VALUE: the function will return a string containing the value

GENERIC:
	$value = get_cimyFieldValue(<user_id>, <field_name>);
EXAMPLE:
	$value = get_cimyFieldValue(1, 'MY_FIELD');
	echo $value;


CASE 2:
get all extra fields values from a specific user

PARAMETERS: pass user_id as first parameter and a boolean set to false as second
RETURNED VALUE: the function will return an associative array containing all extra fields values from that user, this array is ordered by field order

GENERIC:
	$values = get_cimyFieldValue(<user_id>, false);
EXAMPLE:
	$values = get_cimyFieldValue(1, false);

	foreach ($values as $value) {
		echo $value['NAME'];
		echo $value['LABEL'];
		echo $value['VALUE'];
	}


CASE 3:
get value from a specific extra field and from all users

PARAMETERS: pass a boolean set to false as first parameter and field_name as second
RETURNED VALUE: the function will return an associative array containing the specific extra field value from all users, this array is ordered by user login

GENERIC:
	$values = get_cimyFieldValue(false, <field_name>);
EXAMPLE:
	$values = get_cimyFieldValue(false, 'MY_FIELD');

	foreach ($values as $value) {
		echo $value['user_login'];
		echo $value['VALUE'];
	}


CASE 4a:
get all users that have a specific value in a specific extra field

PARAMETERS: pass a boolean set to false as first parameter, field_name as second and field_value as third
RETURNED VALUE: the function will return an associative array containing all users that has that value in that specific extra field, this array is ordered first by user login

GENERIC:
	$values = get_cimyFieldValue(false, <field_name>, <field_value>);
EXAMPLE:
	$values = get_cimyFieldValue(false, 'COLOR', 'red');

	foreach ($values as $value) {
		echo $value['user_login'];
	}


CASE 4b:
get all users that contains (also partially) a specific value in a specific extra field

PARAMETERS: pass a boolean set to false as first parameter, field_name as second and a special array as third
RETURNED VALUE: the function will return an associative array containing all users that contains (also partially) that value in that specific extra field, this array is ordered by user login

GENERIC:
	$values = get_cimyFieldValue(false, <field_name>, <array>);
EXAMPLE:
	$field_value = array();
	$field_value['value'] = ".com";
	$field_value['like'] = true;

	$values = get_cimyFieldValue(false, 'WEBSITE', $field_value);

	foreach ($values as $value) {
		echo $value['user_login'];
	}


CASE 5:
get all users with all values

PARAMETERS: pass two boolean set to false as first and second parameter
RETURNED VALUE: the function will return an associative array containing all extra fields values for every user, this array is ordered first by user login and second by field order

GENERIC:
	$values = get_cimyFieldValue(false, false);
EXAMPLE:
	$values = get_cimyFieldValue(false, false);
	$old_name = "";

	foreach ($values as $value) {
		$new_name = $value['user_login'];

		if ($old_name != $new_name)
			echo "<br /><br />".$new_name."<br /><br />";

		echo $value['LABEL'].": ";
		echo $value['VALUE']."<br />";

		$old_name = $new_name;
	}


ADDITIONAL EXAMPLES:

This is an entire example that can be used into your theme for example to retrieve the value from SITE extra field that of course was created
If you put the example just inside an existing loop you shouldn't add it again, just use get_cimyFieldValue call and echo call.

if (have_posts()) {
	while (have_posts()) {
		the_post();

		$value = get_cimyFieldValue(get_the_author_ID(), 'SITE');

		if ($value != NULL)
			echo $value;
	}
}


If you experience duplicate printing this is due to the loop and where/how it is used; to avoid this you can use this code that has a little workaround.
REMEMBER: you cannot use get_the_author_ID() outside the loop, this because WordPress doesn't permit this.

if (have_posts()) {
	$flag = true;

	while (have_posts()) {
		the_post();

		if ($flag) {
			$value = get_cimyFieldValue(get_the_author_ID(), 'SITE');

			if ($value != NULL)
				echo $value;

			$flag = false;
		}
	}
}

PICTURE AND get_cimyFieldValue FUNCTION:

If you want to display the image in an HTML page just use IMG object like this:
<img src="<?php echo $image_url; ?>" alt="description_here" />


If you want to get the thumbnail url and you have only the image url you can use this function:
$thumb_url = cimy_get_thumb_path($image_url);


REGISTRATION-DATE AND get_cimyFieldValue FUNCTION:

Remember that the function returns the timestamp so to have the correct date printed you can use this function:
echo cimy_get_formatted_date($value);

or

echo cimy_get_formatted_date($value, $format);

where $format is the date and time format, more tags details here:
http://www.php.net/manual/en/function.strftime.php


[Function get_cimyFields]
This function returns an array containing all extra fields defined by the admin ordered by the order defined in the option page, if there are no fields an empty array is returned.

USAGE:
$allFields = get_cimyFields();

EXAMPLE:
$allFields = get_cimyFields();

if (count($allFields) > 0) {
	foreach ($allFields as $field) {
		echo "ID: ".$field['ID']." \n";
		echo "F_ORDER: ".$field['F_ORDER']." \n";
		echo "NAME: ".$field['NAME']." \n";
		echo "TYPE: ".$field['TYPE']." \n";
		echo "VALUE: ".$field['VALUE']." \n";
		echo "LABEL: ".$field['LABEL']." \n";
		echo "DESCRIPTION: ".$field['DESCRIPTION']." \n";

		echo "RULES: ";
		print_r($field['RULES']);
		echo "\n\n";
	}
}

HOW TO CHANGE REGISTRATION DATE FORMAT:
You can change the format of the registration date putting your own format in equal to rule using tags from strftime php function:
http://www.php.net/manual/en/function.strftime.php

default used if not specified:
%d %B %Y @%H:%M

Month and weekday names and other language dependent strings respect the current locale set in your WordPress installation.


HOW TO USE PICTURE SUPPORT:
You have two possibilities for user picture support: "picture-url" and "picture"

[PICTURE-URL]
User will provide only a link and the image will be linked from that site, it will NOT be copied into the server

[PICTURE]
User will upload the image that is stored into his/her computer and the image will be copied into the server

First of all you need a directory where all pictures will be stored, the directory MUST HAVE the same identical name of the plug-in directory and MUST BE placed under <wp_dir>/wp-content/
So if you for example didn't change the default plug-in directory then you must create <wp_dir>/wp-content/Cimy_User_Extra_Fields directory and give it 777 permissions if you are under Linux (or 770 and group to "www-data" if you are under Ubuntu Linux).


KNOWN BUGS/BEHAVIORS:
- if you add too many fields in the "A&U Extended" menu they will go out of frame
- some rules are applied only during registration (apart editable and visibility rules and max length for text and password fields only)
- registration date cannot be modified


FAQ:
Q: Cimy User Extra Fields is not compatible with "Themed Login", how can I do?

A: The reality is this plug-in IS compatible with WordPress 2.1 or greater and "Themed Login" NOT, so it's NOT a Cimy User Extra Field's bug! However I have tried with a little success a workaround to make it works, but first please understand that this is totally untested and unsupported hack, if you want a better one ask the author of that plug-in to support new WordPress!
If you still want *my* personal and unsupported hack edit the plug-in "Themed Login" and do these 3 modifications:

1) at line 773, after "global $wpdb, $wp_query;" add this:
global $errors;

2) at line 811, before "if ( 0 == count($errors) ) {" add this:
do_action('register_post');

3) at line 871, before "A password will be emailed to you." add this:
<?php do_action('register_form'); ?>


Q: get_cimyFieldValue function doesn't work, why?

A: From v0.9.1 I have added a security option to disable that function. If you don't really use it then avoid to enable it, this is because this function can be hacked to retrieve all personal data inserted by subscribers in all extra fields.
Enable and use this function only if extra fields does not contains personal informations.


Q1: I got "Fatal error: Allowed memory size of 8388608 bytes exhausted [..]", why?
Q2: I got blank pages after activating this plug-in, why?

A: Because your memory limit is too low, to fix it edit your php.ini and search memory_limit key and put at least to 12M


Q: Your plug-in is great, but when you will add support to add more than one choice in radio and dropdown fields?

A: This feature is here since ages, for radio field just use the same name, for dropdown field read instructions in the add field area (in the plug-in).


Q: When feature XYZ will be added?

A: I don't know, remember that this is a 100% free project so answer is "When I have time to..." visit the homepage and read the news, if there are no new news then back another day :)


Q: Can I hack this plug-in and hope to see my code in the next release?

A: For sure, this is just happened and can happen again if you write useful new features and good code. Try to see how I maintain the code and try to do the same (or even better of course), I have rules on how I write it, don't want "spaghetti code", I'm Italian and I want spaghetti only on my plate.
There is no guarantee that your patch will reach Cimy User Extra Fields, but feel free to do a fork of this project and distribuite it, this is GPL!


Q1: I have found a bug what can I do?
Q2: Something does not work as expected, why?

A: The first thing is to download the latest version of the plug-in and see if you still have the same issue.
If yes please write me an email or write a comment but give as more details as you can, like:
- Plug-in version
- WordPress version
- MYSQL version
- PHP version
- exact error that is returned (if any)

after describe what you did, what you expected and what instead the plug-in did :)
Then the MOST important thing is: DO NOT DISAPPEAR!
A lot of times I cannot reproduce the problem and I need more details, so if you don't check my answer then 80% of the times bug (if any) will NOT BE FIXED!


CHANGELOG:
v1.1.1 - 15/05/2008
- Added Swedish translation (Peter)
- Fixed problems with special characters (may need resave the content)
- Fixed two untranslated strings in the options (thanx to Peter)

v1.1.0 - 07/05/2008
- Fixed thumbnails were broken with WordPress 2.5.x (thanx to Rita)
- Fixed thumbnails were broken when an image have an upper-case extension (due to a WordPress issue)
- Updated German translation (Rita)

v1.1.0 release candidate 1 - 28/04/2008
- Fixed a regression with WordPress 2.5.x user's without admin privileges cannot edit extra fields at all
- Fixed pages in A&U Extended page pointed to non Extended page
- Fixed some hidden text in "Add field" area for certain configurations (thanx to Rik)

v1.1.0 beta2 - 05/04/2008
- Changed theme for: user's Profile, A&U Extended page, Options and Fields management
- Code cleanup
- Updated Italian translation
- Readme file updated

v1.1.0 beta1 - 31/03/2008
- Added initial support to WordPress 2.5
- Added custom css for registration fields

v1.0.2 - 24/03/2008
- Added Russian translation (mikolka)
- Added Danish translation (Rune)
- Fixed a bad bug that in some cases checkbox fields were saved wrongly as checked (thanx to Dana Rockel for the patch)
- Fixed picture file attributes for broken server (thanx to Chris Adams for the partial patch)
- Fixed picture url when WordPress URL and Blog URL are different (thanx to Neil Stead for the patch)
- Fixed picture upload with Internet Explorer, due to a bug in it probably :( (thanx to Nicola aka ala_747 for the partial patch)
- Fixed picture upload with some localized WordPress (like French) (thanx to buzz)
- Removed an obsolete part in the Readme file (thanx to Mark)

v1.0.1 - 22/11/2007
- Added better directory creation handling for images uploader
- Added French translation (Sev)
- Updated Brazilian Portuguese translation (Sher)
- Moved invert selection javascript to a stand-alone file so admin page is XHTML 1.0 Transitional compliant again
- Fixed a rare image upload failure during registration, can happen if at least one WordPress hidden field is present
- Fixed warning pop-up for image extension, shown wrongly in certain cases

v1.0.0 - 16/10/2007
- Added hidden WordPress fields support (First name, Last name, Nickname, Website, AIM, Yahoo IM and Jabber / Google Talk)
- Added initial WordPress MU compatibility! (Thanx to Martin Cleaver and Beau Lebens for explaining me how MU works)
  - I need more hours of work to finish MU support, if someone want to sponsor it then will be faster, email me :)
- Added force tables creation option
- Added Can be modified by admin or if empty rule
- Added Can be empty (or not) rule also to dropdown
- Added Cimy Plug-in Series support
- Added capabilities to upload pictures without modifying WordPress files anymore (Thanx to j5)
- Added invert selection button useful when there are a lot of fields
- Added a warning pop-up before deleting fields
- Added a warning pop-up if a file that haven't a valid image extension is chosen
- Added German translation (Rita)
- Added Brazilian Portuguese translation (Sher)
- Updated Italian translation
- Changed the way how data are escaped, this has the effect that now html is allowed in label and description for example
- Fixed data inserted in the extra field by users were never deleted if the extra field was deleted
- Fixed maxlength attribute wrongly added to input file element during registration
- Fixed various problems with special characters present in some languages
- Removed break after label in the registration form (not for checkbox fields)
- Dropped magic_quotes_gpc_off function
- Lot of code cleanup (all the plug-in is now divided into different files)

v0.9.9 - 04/09/2007
- Added possibility to translate the plug-in
- Added Italian translation
- Fixed user's number of posts in A&U Extended page

v0.9.8 - 03/09/2007
- Added registration-date support
- Added rule to let modify extra fields content only by administrator
- Added database options: [empty, delete] extra fields and users data tables; [set to default, delete] options
- Added field's LABEL and TYPE to the array returned by get_cimyFieldValue (apart the case when providing both user_id and field_name)

v0.9.7 - 23/07/2007
- Added to get_cimyFieldValue partial results in search over user's extra fields values, see CASE 4b
- Fixed a bug introduced in v0.9.4 in get_cimyFieldValue that affected all MYSQL 4.x users
- Changed array order returned by get_cimyFieldValue function, see updated examples for details
- Home page url updated
- Readme file updated

v0.9.6 - 15/07/2007
- Added support for user picture-url, user can simply put an url of an existing image, no need to hack WordPress like picture support that anyway is a cool different feature
- Added ability in get_cimyFieldValue to retrieve all extra fields values from all users
- Pictures are now all resized according to equalTO rule also in A&U Extended page
- Fixed missing check for spaces presence on extra fields names
- Fixed cimy_rfr() redeclaration error
- Fixed wrong message error for picture field refers to size in MegaByte instead of KiloByte
- Fixed equalTo rule wrongly applied to picture fields during registration
- Fixed wp_create_thumbnail() missing function during registration for picture fields with equalTo rule specified
- Fixed default checked item with radio fields was broken in some cases
- Readme file updated
- Code cleanup

v0.9.5 - 09/07/2007
- Added support for user picture!
- Added id attribute to paragraph elements in the registration and user's profile
- Changed get_cimyFieldValue return a warning string when it's used but not enabled
- Fixed a bad bug when deselecting "Show in the registration" or "Show in user's profile" produced unexpected value inserted in that field
- Fixed warning message when updated the plug-in disappears just saving options and not de-activating and re-activating the plug-in
- Fixed some error messages not displayed when adding wrong numbers in [min,exact,max] length with textarea fields
- Fixed new row in "A&U Extended" wrong added when you have 9 or more fields
- Code cleanup

v0.9.4 - 25/06/2007
- Added a checkbox to manage equalTO rule and make it case sensitive or not
- Rewritten get_cimyFieldValue function:
  - now can accept also only one parameter: FIELD_NAME or USER_ID
  - added a third optional parameter called FIELD_VALUE
  - never return values from password fields for security reasons
  - updated README file to reflect changes to this function
  - note that this new version is backward compatible with previous calls
- Fixed equalTO rule that worked only if text was written in upper case
- Fixed equalTO error message for drop-down fields included also all choices and not only real label
- Fixed all HTML code, now it's XHTML 1.0 Transitional compliant
- Code cleanup

v0.9.3 - 10/06/2007
- Added min length and exact length rules
- Code cleanup

v0.9.2 - 06/06/2007
- Fixed radio and checkbox fields were too large under Internet Explorer 6.0 or lower, I know I have said that I will never fix this, but I lied!
- Removed warning and relative option for MSIE 6.0 (or lower) users introduced in 0.9.1
- Removed in user's profile border and grey background in radio and checkbox fields that were visible in some browsers like: Opera and Internet Explorer
- Fixed fields order, was totally broken for newer MYSQL versions (at least in mine 5.0.38, now should be ok for every one)
- Added a check that shows a warning in the options page when an user has updated the plug-in but forgot to de-activate and re-activate it

v0.9.1 - 05/06/2007
- Added Options page:
  - enable/disable get_cimyFieldValue() function to avoid unwanted use of this function by third parties
  - show/not a warning in the user's profile for who uses Microsoft Internet Explorer 6.0 or lower
  - add titles to fieldset
  - choose how many extra fields to show per one fieldset
  - hide/show some columns in "A&U Extended" page
- User's profile is now reorganized: checkbox and radio fields are back into fieldset; due to a WordPress CSS I made a workaround to avoid bigger inputs, however this workaround doesn't work under Microsoft Internet Explorer 6.0 or lower, this will never be fixed so don't ask about it!
- Do not include php_compat_mqgpc_unescape() function if already included by some other plug-in
- Code cleanup

v0.9.0 final - 15/05/2007
- Added some checks to make plug-in more secure and avoid admin functions to be used by non-admin (more security patches with next releases)
- Fixed own profile saved twice
- Re-added subdir in the package, removed by a mistake in 0.9.0-rc2
- Some Readme changes

v0.9.0 release candidate 2 - 15/04/2007
- Added extra fields data deletions when a user is deleted
- Added a lot of checks for string length inserted by user
- Added max length rule also to textarea field
- Changed max length up to 5000 characters for: value, label and description
- Changed "value" and "label" fields to textarea in the admin menu

v0.9.0 release candidate 1 - 01/04/2007
- Added drop-down support thanx to Raymond Elferink that hacked my plug-in, I have made only some small enhancements to his code
- Added a rule to set/unset field visibility in User's profile page
- Added a rule to set/unset field visibility in "A&U Extended" page
- Changed the way rules are saved, with php serialize seems a better way for code maintainability
- Label can now have length up to 1024 characters
- Updated get_cimyFields example in README file
- Fixed a bug that prevents changing options to a field with special characters in the name
- Some cosmetic changes to A&U Extended page
- Emulate magic_quotes_gpc=off if they are turned on
- Database changes: added indexes in both tables, if you have a lot of data probably this will increase speed
- Database changes: changed field 'LABEL' to TEXT in wp_cimy_fields table
- Code cleanup

v0.9.0 beta8 - 23/03/2007
- Finally fixed the very boring bug that for some people plug-in never creates tables when activated! Thanx to ysjack for helping me reproduce the problem
- Dropped using $table_prefix variable since WordPress 2.1 deprecated it
- Updated get_cimyFieldValue example in README file, now it is a complete example
- Added a subdir in the package

v0.9.0 beta7 - 17/03/2007
- Added password support, now you can set a password to register to your site!
- Added stripslashes also to get_cimyFieldValue function so no more backslashes are returned with certain characters
- Fixed get_cimyFieldValue function never returns NULL when some parameter was wrong, now it does!

v0.9.0 beta6 - 16/03/2007
- Fixed value field wasn't applied in user's profile when a text or textarea was empty
- Fixed a bug (introduced in beta5) that fill some data in the new field form when you just update an existing field
- Fixed editable rule that was broken probably in beta3 or 4 during some code cleanup :(
- Changed plug-in link, now it points to the specific blog page

v0.9.0 beta5 - 15/03/2007
- Added textarea support (up to 1024 characters)
- Added class attribute in the registration form so now all extra fields have the same look like built-in ones
- Added tabindex attribute in the registration form so now when you press tab you have a normal sequence
- Now in the option page when you fill the form for a new field all data are kept in memory every time you press "Add" button
- Fixed equalTO rule for text fields that any value entered was to be all in upper case format to make error message go away
- Code cleanup

v0.9.0 beta4 - 13/03/2007 later
- Forgot to change also tables creation with new modification made in beta3, if you had problem please update to this version and activate the plug-in again, all will back to normal
- Performance improvement: now extra fields are read from database only when they are needed
- Added get_cimyFields() function to retrieve all extra fields information useful for templates/themes
- Fixed equalTO rule in radio fields, was completely broken
- Code cleanup

v0.9.0 beta3 - 13/03/2007
- Added radio input support
- Added value field, you can now pre-enter characters in your text fields or pre-select checkbox and radio fields
- Added a check that disable all rules unrelated to a certain field type
- Added some login-bkg-tile.gif with bigger height to workaround fields out of frame during registration
- Fixed checkbox fields in edit profile were very large, to fix this all radio and checkbox fields are now out of fieldset
- Some cosmetic changes to option and profile pages
- All html transformable characters are now transformed in UTF-8 html format
- Added a stripslashes before showing data so these characters are now allowed: ', ", <, >, (, ), [, ], #
- Database changes: new field 'VALUE', changed fields 'RULES' and 'DESCRIPTION' to TEXT in wp_cimy_fields table
- Database changes: changed field 'VALUE' to TEXT in wp_cimy_data table
- Code cleanup

v0.9.0 beta2 - 27/02/2007
- Fixed a bug that returns MYSQL error when deleting extra fields in certain circumstances
- Fixed a bug that returns MYSQL errors in Users->Your Profile and when adding a new user from administration, these errors were shown only when there were no extra fields defined
- Added a control to prevent from creating/deleting tables to users without enough privileges

v0.9.0 beta1 - 12/02/2007
- Plug-in now supports only WordPress 2.1!
- Removed wp-register.php, with WP 2.1 is not needed anymore, form registration extra fields are now built-in
- Added get_cimyFieldValue function that can be used in your themes
- Fixed a bug that prevents to save unchecked checkbox in user's profile
- Fixed a bug that returns MYSQL error during user's profile update and all fields were set to "Cannot be edited"
- Moved all these infos to a readme file

v0.8.7 - 28/12/2006
- Fixed a bug with PHP<5.0 that in the "equalTo" field saves some strange characters

v0.8.6
- First public release