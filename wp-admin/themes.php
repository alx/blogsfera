<?php
require_once('admin.php');

$themes = get_themes();
$ct = current_theme_info();
$allowed_themes = apply_filters("allowed_themes", get_site_allowed_themes() ); 
if( $allowed_themes == false )
	$allowed_themes = array();

$blog_allowed_themes = wpmu_get_blog_allowedthemes();
if( is_array( $blog_allowed_themes ) )
	$allowed_themes = array_merge( $allowed_themes, $blog_allowed_themes );
if( $blog_id != 1 )
	unset( $allowed_themes[ "h3" ] );

if( isset( $allowed_themes[ wp_specialchars( $ct->stylesheet ) ] ) == false )
    $allowed_themes[ wp_specialchars( $ct->stylesheet ) ] = true;

reset( $themes );
foreach( $themes as $key => $theme ) {
    if( isset( $allowed_themes[ wp_specialchars( $theme[ 'Stylesheet' ] ) ] ) == false ) {
		unset( $themes[ $key ] );
    }
}
reset( $themes );

if ( isset($_GET['action']) ) {
	check_admin_referer('switch-theme_' . $_GET['template']);

	if ('activate' == $_GET['action']) {
		switch_theme($_GET['template'], $_GET['stylesheet']);
		wp_redirect('themes.php?activated=true');
		exit;
	}
}

$title = __('Manage Themes');
$parent_file = 'themes.php';

add_thickbox();
wp_enqueue_script( 'theme-preview' );

require_once('admin-header.php');
if( is_site_admin() ) {
	?><div id="message0" class="updated fade"><p><?php _e('Administrator: new themes must be activated in the <a href="wpmu-themes.php">Themes Admin</a> page before they appear here.'); ?></p></div><?php
}
?>

<?php if ( ! validate_current_theme() ) : ?>
<div id="message1" class="updated fade"><p><?php _e('The active theme is broken.  Reverting to the default theme.'); ?></p></div>
<?php elseif ( isset($_GET['activated']) ) : ?>
<div id="message2" class="updated fade"><p><?php printf(__('New theme activated. <a href="%s">Visit site</a>'), get_bloginfo('url') . '/'); ?></p></div>
<?php endif; ?>

<?php
ksort( $themes );
$theme_total = count( $themes );
$per_page = 15;

if ( isset( $_GET['pagenum'] ) )
	$page = absint( $_GET['pagenum'] );

if ( empty($page) )
	$page = 1;

$start = $offset = ( $page - 1 ) * $per_page;

$page_links = paginate_links( array(
	'base' => add_query_arg( 'pagenum', '%#%' ) . '#themenav',
	'format' => '',
	'total' => ceil($theme_total / $per_page),
	'current' => $page
));

$themes = array_slice( $themes, $start, $per_page );

?>

<div class="wrap">
<h2><?php _e('Current Theme'); ?></h2>
<div id="current-theme">
<?php if ( $ct->screenshot ) : ?>
<img src="<?php echo WP_CONTENT_URL . $ct->stylesheet_dir . '/' . $ct->screenshot; ?>" alt="<?php _e('Current theme preview'); ?>" />
<?php endif; ?>
<h3><?php printf(_c('%1$s %2$s by %3$s|1: theme title, 2: theme version, 3: theme author'), $ct->title, $ct->version, $ct->author) ; ?></h3>
<p class="description"><?php echo $ct->description; ?></p>
<?php if ( $ct->tags ) : ?>
<p><?php _e('Tags:'); ?> <?php echo join(', ', $ct->tags); ?></p>
<?php endif; ?>
</div>

<h2><?php _e('Available Themes'); ?></h2>
<br class="clear" />

<?php if ( $page_links ) : ?>
<div class="tablenav">
<?php echo "<div class='tablenav-pages'>$page_links</div>"; ?>
<br class="clear" />
</div>
<br class="clear" />
<?php endif; ?>

<?php if ( 1 < $theme_total ) { ?>
<table id="availablethemes" cellspacing="0" cellpadding="0">
<?php
$style = '';

$theme_names = array_keys($themes);
natcasesort($theme_names);

$rows = ceil(count($theme_names) / 3);
for ( $row = 1; $row <= $rows; $row++ )
	for ( $col = 1; $col <= 3; $col++ )
		$table[$row][$col] = array_shift($theme_names);

foreach ( $table as $row => $cols ) {
?>
<tr>
<?php
foreach ( $cols as $col => $theme_name ) {
	$class = array('available-theme');
	if ( $row == 1 ) $class[] = 'top';
	if ( $col == 1 ) $class[] = 'left';
	if ( $row == $rows ) $class[] = 'bottom';
	if ( $col == 3 ) $class[] = 'right';
?>
	<td class="<?php echo join(' ', $class); ?>">
<?php if ( !empty($theme_name) ) :
	$template = $themes[$theme_name]['Template'];
	$stylesheet = $themes[$theme_name]['Stylesheet'];
	$title = $themes[$theme_name]['Title'];
	$version = $themes[$theme_name]['Version'];
	$description = $themes[$theme_name]['Description'];
	$author = $themes[$theme_name]['Author'];
	$screenshot = $themes[$theme_name]['Screenshot'];
	$stylesheet_dir = $themes[$theme_name]['Stylesheet Dir'];
	$preview_link = clean_url( get_option('home') . '/');
	$preview_link = add_query_arg( array('preview' => 1, 'template' => $template, 'stylesheet' => $stylesheet, 'TB_iframe' => 'true', 'width' => 600, 'height' => 400 ), $preview_link );
	$preview_text = attribute_escape( sprintf( __('Preview of "%s"'), $title ) );
	$tags = $themes[$theme_name]['Tags'];
	$thickbox_class = 'thickbox';
	$activate_link = wp_nonce_url("themes.php?action=activate&amp;template=".urlencode($template)."&amp;stylesheet=".urlencode($stylesheet), 'switch-theme_' . $template);
	$activate_text = attribute_escape( sprintf( __('Activate "%s"'), $title ) );
?>
		<a href="<?php echo $activate_link; ?>" class="<?php echo $thickbox_class; ?> screenshot">
<?php if ( $screenshot ) : ?>
			<img src="<?php echo WP_CONTENT_URL . $stylesheet_dir . '/' . $screenshot; ?>" alt="" />
<?php endif; ?>
		</a>
		<h3><a class="<?php echo $thickbox_class; ?>" href="<?php echo $activate_link; ?>"><?php echo $title; ?></a></h3>
		<p><?php echo $description; ?></p>
<?php if ( $tags ) : ?>
		<p><?php _e('Tags:'); ?> <?php echo join(', ', $tags); ?></p>
		<noscript><p class="themeactions"><a href="<?php echo $preview_link; ?>" title="<?php echo $preview_text; ?>"><?php _e('Preview'); ?></a> <a href="<?php echo $activate_link; ?>" title="<?php echo $activate_text; ?>"><?php _e('Activate'); ?></a></p></noscript>
<?php endif; ?>
		<div style="display:none;"><a class="previewlink" href="<?php echo $preview_link; ?>"><?php echo $preview_text; ?></a> <a class="activatelink" href="<?php echo $activate_link; ?>"><?php echo $activate_text; ?></a></div>
<?php endif; // end if not empty theme_name ?>
	</td>
<?php } // end foreach $cols ?>
</tr>
<?php } // end foreach $table ?>
</table>
<?php } ?>

<br class="clear" />

<?php if ( $page_links ) : ?>
<div class="tablenav">
<?php echo "<div class='tablenav-pages'>$page_links</div>"; ?>
<br class="clear" />
</div>
<?php endif; ?>

<br class="clear" />

<?php
// List broken themes, if any.
$broken_themes = get_broken_themes();
if ( is_site_admin() && count($broken_themes) ) {
?>

<h2><?php _e('Broken Themes'); ?> (Site admin only)</h2>
<p><?php _e('The following themes are installed but incomplete.  Themes must have a stylesheet and a template.'); ?></p>

<table width="100%" cellpadding="3" cellspacing="3">
	<tr>
		<th><?php _e('Name'); ?></th>
		<th><?php _e('Description'); ?></th>
	</tr>
<?php
	$theme = '';

	$theme_names = array_keys($broken_themes);
	natcasesort($theme_names);

	foreach ($theme_names as $theme_name) {
		$title = $broken_themes[$theme_name]['Title'];
		$description = $broken_themes[$theme_name]['Description'];

		$theme = ('class="alternate"' == $theme) ? '' : 'class="alternate"';
		echo "
		<tr $theme>
			 <td>$title</td>
			 <td>$description</td>
		</tr>";
	}
?>
</table>
<?php
}
?>
<br clear="all" />
</div>

<?php require('admin-footer.php'); ?>
