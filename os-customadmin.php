<?php
/*
Plugin Name: OS Custom Admin
Description: Cleans up the Wordpress admin to make more user-friendly.
Version: 0.3
Author: Oli Salisbury
*/

//hooks
if (WP_ADMIN) {
	add_action('wp_dashboard_setup', 'remove_dashboard_widgets');
	add_action('admin_head', 'remove_dashboard_discussion');
	add_action('admin_menu', 'remove_menu_items');
	add_action('admin_menu', 'remove_submenus');
	add_action('admin_init','customize_meta_boxes');
	add_filter('manage_posts_columns', 'custom_post_columns');
	add_filter('manage_pages_columns', 'custom_pages_columns');
	add_filter('manage_media_columns', 'custom_media_columns');
	add_filter('admin_footer_text', 'modify_footer_admin');
	add_action('init', 'change_post_object_label');
	add_action('admin_menu', 'change_post_menu_label');
	add_action('login_head', 'login_logo');
	add_action('admin_head', 'admin_logo');
}

//minimise admin/meta panels
//enabling pages & posts see //* to disable posts
function remove_dashboard_widgets(){
	global$wp_meta_boxes;
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);
}

function remove_dashboard_discussion() { //*, .b-posts, .posts
  echo '
  <style type="text/css">
  .table_discussion, .b-cats, .cats, .b-tags, .tags { display:none; }
  </style>';
}

function remove_menu_items() { //*, __('Posts')
	global $menu;
	$restricted = array(__('Links'), __('Comments'), __('Profile'), __('Tools'));
	end ($menu);
	while (prev($menu)){
		$value = explode(' ',$menu[key($menu)][0]);
		if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
	}
}

function remove_submenus() {
	global $submenu;
	unset($submenu['index.php'][10]); //removes updates
	unset($submenu['edit.php'][15]); //removes categories
	unset($submenu['edit.php'][16]); //removes tags
	unset($submenu['edit.php?post_type=page'][10]); //removes add new page
}

function customize_meta_boxes() {
	//removes meta boxes from posts
	remove_meta_box('postcustom','post','normal');
	remove_meta_box('trackbacksdiv','post','normal');
	remove_meta_box('commentstatusdiv','post','normal');
	remove_meta_box('commentsdiv','post','normal');
	remove_meta_box('tagsdiv-post_tag','post','normal');
	remove_meta_box('postexcerpt','post','normal');
	remove_meta_box('categorydiv','post','normal');
	remove_meta_box('authordiv','post','normal');
	remove_meta_box('revisionsdiv','post','normal');
	//remove_meta_box('slugdiv','post','normal');
	//removes meta boxes from pages
	remove_meta_box('postcustom','page','normal');
	remove_meta_box('trackbacksdiv','page','normal');
	remove_meta_box('commentstatusdiv','page','normal');
	remove_meta_box('commentsdiv','page','normal'); 
	remove_meta_box('authordiv','page','normal');
	remove_meta_box('revisionsdiv','page','normal');
	//remove_meta_box('slugdiv','page','normal');
	//remove_meta_box('pageparentdiv','page','normal');
}

function custom_post_columns($defaults) {
	unset($defaults['comments']);
	unset($defaults['author']);
	unset($defaults['categories']);
	unset($defaults['tags']);
	return $defaults;
}

function custom_pages_columns($defaults) {
	unset($defaults['comments']);
	unset($defaults['author']);
	unset($defaults['date']);
	return $defaults;
}

function custom_media_columns($defaults) {
	unset($defaults['comments']);
	unset($defaults['author']);
	return $defaults;
}

function modify_footer_admin () {
	echo 'Created by <a href="http://www.egomedia.co.uk">Ego Media</a>. ';
	echo 'Powered by <a href="http://WordPress.org">WordPress</a>.';
}

//change posts label to news
function change_post_menu_label() {
	global $menu;
	global $submenu;
	$menu[5][0] = 'News';
	$submenu['edit.php'][5][0] = 'View News';
	$submenu['edit.php'][10][0] = 'Add News';
	//$submenu['edit.php'][16][0] = 'News Tags';
	echo '';
}

function change_post_object_label() {
	global $wp_post_types;
	$labels = &$wp_post_types['post']->labels;
	$labels->name = 'News';
	$labels->singular_name = 'News';
	$labels->add_new = 'Add News';
	$labels->add_new_item = 'Add News';
	$labels->edit_item = 'Edit News';
	$labels->new_item = 'News';
	$labels->view_item = 'View News';
	$labels->search_items = 'Search News';
	$labels->not_found = 'No News found';
	$labels->not_found_in_trash = 'No News found in Trash';
}

//add egomedia logo to login
function login_logo() {
	echo '<style type="text/css">h1 a { background:url("http://109.123.106.119/admin-login.png") no-repeat !important; }</style>';
}
//add egomedia logo to admin
function admin_logo() {
	echo '<style type="text/css">#header-logo { background:url("http://109.123.106.119/admin-emblem.png") !important; width:33px !important; }</style>'; 
}
?>