<?php
/*
Plugin Name: OS Custom Admin
Description: Cleans up the Wordpress admin to make more user-friendly.
Version: 0.6
Author: Oli Salisbury
*/

new os_customadmin;

class os_customadmin {
	
	function __construct() {
		add_filter('wp_handle_upload_prefilter', array($this, 'reduce_max_upload_limit'));
		add_action('right_now_content_table_end' , array($this, 'add_all_post_types_to_dashboard'));
		add_action('wp_dashboard_setup', array($this, 'remove_dashboard_widgets'));
		add_action('admin_head', array($this, 'remove_dashboard_discussion'));
		add_action('admin_menu', array($this, 'remove_menu_items'));
		add_action('admin_menu', array($this, 'remove_submenus'));
		add_action('admin_init', array($this, 'customize_meta_boxes'));
		add_filter('manage_posts_columns', array($this, 'custom_post_columns'));
		add_filter('manage_pages_columns', array($this, 'custom_pages_columns'));
		add_filter('manage_media_columns', array($this, 'custom_media_columns'));
		add_action('init', array($this, 'change_post_object_label'));
		add_action('admin_menu', array($this, 'change_post_menu_label'));
		add_action('admin_bar_menu', array($this, 'custom_admin_bar'), 1000);
		add_action('admin_head', array($this, 'hide_add_new_page_button'));
	}
	
	//limit upload size to 350kb for images
	//v1.1
	function reduce_max_upload_limit($file) {
		$size = $file['size'];
		$allowed_exts = array('pdf', 'doc');
		$ext = str_replace(".", "", strstr($file['name'], "."));
		if ($size > 350 * 1024 && !in_array($ext, $allowed_exts)) {
			 $file['error'] = 'Images larger than 350KB are prohibited. Please resize the file and try again.';
		}
		return $file;
	}
	
	//add all custom post types to the "Right Now" box on the Dashboard
	function add_all_post_types_to_dashboard() {
		$args = array('public' => true, '_builtin' => false);
		$output = 'object';
		$operator = 'and';
		$post_types = get_post_types($args, $output, $operator);
		foreach($post_types as $post_type) {
			$num_posts = wp_count_posts($post_type->name);
			$num = number_format_i18n($num_posts->publish);
			$text = _n($post_type->labels->singular_name, $post_type->labels->name, intval($num_posts->publish));
			if (current_user_can('edit_posts')) {
				$num = "<a href='edit.php?post_type=$post_type->name'>$num</a>";
				$text = "<a href='edit.php?post_type=$post_type->name'>$text</a>";
			}
			echo '<tr><td class="first b b-'.$post_type->name.'">'.$num.'</td>';
			echo '<td class="t '.$post_type->name.'">'.$text.'</td></tr>';
		}
	}
	
	//customise admin screens
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
	function remove_dashboard_discussion() { //, .b-posts, .posts
		echo '
		<style type="text/css">
		#dashboard_right_now .table_discussion, .b-cats, .cats, .b-tags, .tags { display:none !important; }
		#tab-type_url { display:none !important; }
		</style>';
	}
	function remove_menu_items() { //, __('Posts')
		global $menu;
		$restricted = array(__('Links'), __('Comments'), __('Profile'), __('Tools'));
		end($menu);
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
		//remove_meta_box('pageparentdiv','page','normal');
		//remove_meta_box('slugdiv','page','normal');
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
	function change_post_menu_label() {
		global $menu;
		global $submenu;
		$menu[5][0] = 'News';
		$submenu['edit.php'][5][0] = 'News';
		$submenu['edit.php'][10][0] = 'Add News';
		$submenu['edit.php'][16][0] = 'News Tags';
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
	
	//custom admin bar
	function custom_admin_bar($wp_admin_bar) { 
		//remove wordpress tab
		$wp_admin_bar->remove_node('wp-logo'); 
		//remove visit site tab
		$wp_admin_bar->remove_node('view-site');
		//remove comments tab
		$wp_admin_bar->remove_node('comments');
		//remove add new subtabs
		$wp_admin_bar->remove_node('new-page');
		$wp_admin_bar->remove_node('new-media');
		$wp_admin_bar->remove_node('new-link');
		$wp_admin_bar->remove_node('new-user');
		//rename new Post to News
		$wp_admin_bar->add_node(array('id'=>'new-post', 'title'=>'News'));
		//update myaccount tab
		$myaccount = $wp_admin_bar->get_node('my-account');
		$wp_admin_bar->add_node(array('id'=>'my-account', 'title'=>str_replace("Howdy", "Welcome", $myaccount->title)));
	}
	
	//hide 'add new' page button
	function hide_add_new_page_button() {
		global $post;
		if ($post->post_type == 'page') {
			echo '
			<style type="text/css">
			.add-new-h2 { display:none; }
			</style>';
		}
	}
	
}
?>