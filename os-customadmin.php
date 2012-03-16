<?php
/*
Plugin Name: OS Custom Admin
Description: Cleans up the Wordpress admin to make more user-friendly for corporate clients.
Version: 1.0
Author: Oli Salisbury
*/

//you need to initialise the class in your functions.php file
//include_once('scripts/os-customadmin.php');
//new OS_CustomAdmin();


class OS_CustomAdmin {
	
	//construct
	function __construct() {
		add_filter('wp_handle_upload_prefilter', array($this, 'reduce_max_upload_limit'));
		add_action('right_now_content_table_end' , array($this, 'add_all_post_types_to_dashboard'));
		add_action('wp_dashboard_setup', array($this, 'remove_dashboard_widgets'));
		add_action('admin_head', array($this, 'remove_dashboard_discussion'));
		add_action('admin_menu', array($this, 'remove_surpless_menus'));
		//add_action('admin_menu', array($this, 'remove_surpless_submenus')); //causing debug errors
		add_filter('manage_posts_columns', array($this, 'custom_post_columns'));
		add_filter('manage_pages_columns', array($this, 'custom_pages_columns'));
		add_filter('manage_media_columns', array($this, 'custom_media_columns'));
		add_action('wp_before_admin_bar_render', array($this, 'custom_admin_bar'));
		add_action('admin_menu', array($this, 'hide_updates_nag'));
		add_filter('tiny_mce_before_init', array($this, 'custom_tiny_mce'));
		add_action('admin_init', array($this, 'custom_meta_boxes'));
		$this->rename_post_object();
	}
	
	//TOP LEVEL FUNCTIONS
	
	//limit upload size to 350kb for images
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
	
	//remove dashboard widgets
	function remove_dashboard_widgets(){
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);
	}
	
	//remove dashboard discussion, cats, and tags
	function remove_dashboard_discussion() {
		echo '
		<style type="text/css">
		#dashboard_right_now .table_discussion, 
		#dashboard_right_now .b-cats, 
		#dashboard_right_now .cats, 
		#dashboard_right_now .b-tags, 
		#dashboard_right_now .tags,
		#tab-type_url 
		{ display:none !important; }
		</style>';
	}
	
	//remove surpless menu items
	function remove_surpless_menus() { 
		global $menu;
		$restricted = array(__('Links'), __('Comments'), __('Profile'), __('Tools'));
		end($menu);
		while (prev($menu)){
			$value = explode(' ',$menu[key($menu)][0]);
			if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
		}
	}
	
	//remove surpless submenus
	function remove_surpless_submenus() {
		global $submenu;
		unset($submenu['index.php'][10]); //removes updates
		unset($submenu['edit.php'][15]); //removes categories
		unset($submenu['edit.php'][16]); //removes tags
	}
	
	//tidy up admin archive pages
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
	
	//customise admin bar
	function custom_admin_bar() { 
		global $wp_admin_bar;
		//remove wordpress tab
		$wp_admin_bar->remove_node('wp-logo'); 
		//remove visit site tab
		$wp_admin_bar->remove_node('view-site');
		//remove updates tab
		$wp_admin_bar->remove_node('updates');
		//remove comments tab
		$wp_admin_bar->remove_node('comments');
		//remove add new subtabs
		$wp_admin_bar->remove_node('new-media');
		$wp_admin_bar->remove_node('new-link');
		$wp_admin_bar->remove_node('new-user');
		//update myaccount tab
		$myaccount = $wp_admin_bar->get_node('my-account');
		$wp_admin_bar->add_node(array('id'=>'my-account', 'title'=>str_replace("Howdy", "Welcome", $myaccount->title), 'meta'=>''));
	}
	
	//hide updates nag from non admins
	function hide_updates_nag() {
		if (!current_user_can('administrator')) {
			remove_action('admin_notices', 'update_nag', 3);
		}
	}
	
	//custom tinymce editor
	function custom_tiny_mce($in) {
		if (!current_user_can('administrator')) {
			$in['theme_advanced_buttons1']='formatselect,|,bold,italic,|,link,unlink,|,bullist,numlist,|,undo,redo,|,wp_more';//,|,wp_adv';
			$in['theme_advanced_buttons2']='justifyleft,justifycenter,justifyright,|,forecolor,|,removeformat,|,charmap,|,wp_fullscreen';
			$in['theme_advanced_buttons3']='';
			$in['theme_advanced_buttons4']='';
		}
		return $in;
	}
	
	//customise default meta boxes
	function custom_meta_boxes() {
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
	
	//RENAME POSTS
	function rename_post_object($label_single='News', $label_plural='News') {
		$this->label_single = $label_single;
		$this->label_plural = $label_plural;
		add_action('init', array($this, 'change_post_object_labels'));
		add_action('admin_menu', array($this, 'change_post_menu_label'));
		add_action('wp_before_admin_bar_render', array($this, 'change_post_admin_bar_label'));
	}
	
	function change_post_object_labels() {
		global $wp_post_types;
		$labels = &$wp_post_types['post']->labels;
		$labels->name = $this->label_plural;
		$labels->singular_name = $this->label_single;
		$labels->add_new = 'Add '.$this->label_single;
		$labels->add_new_item = 'Add '.$this->label_single;
		$labels->edit_item = 'Edit '.$this->label_single;
		$labels->new_item = 'New '.$this->label_single;
		$labels->view_item = 'View '.$this->label_single;
		$labels->search_items = 'Search '.$this->label_plural;
		$labels->not_found = 'No '.$this->label_plural.' found';
		$labels->not_found_in_trash = 'No '.$this->label_plural.' found in Trash';
	}
	
	function change_post_menu_label() {
		global $menu;
		global $submenu;
		$menu[5][0] = $this->label_plural;
		$submenu['edit.php'][5][0] = 'View '.$this->label_plural;
		$submenu['edit.php'][10][0] = 'Add '.$this->label_single;
		$submenu['edit.php'][16][0] = $this->label_single.' Tags';
	}
	
	function change_post_admin_bar_label() { 
		global $wp_admin_bar;
		$wp_admin_bar->add_node(array('id'=>'new-post', 'title'=>$this->label_single));
	}
	
	
	//DISABLE POSTS
	function disable_posts() {
		add_action('admin_menu', array($this, 'remove_posts_menu'));
		add_action('wp_before_admin_bar_render', array($this, 'remove_posts_admin_bar'));
		add_action('admin_head', array($this, 'remove_posts_dashboard'));
	}
	
	function remove_posts_menu() { 
		global $menu;
		$restricted = array('edit_posts');
		end($menu);
		while (prev($menu)){
			$value = explode(' ',$menu[key($menu)][1]);
			if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
		}
	}

	function remove_posts_admin_bar() { 
		global $wp_admin_bar;
		$wp_admin_bar->remove_node('new-post');
		$wp_admin_bar->add_node(array('id'=>'new-content', 'href'=>false));
	}
	
	function remove_posts_dashboard() {
		echo '
		<style type="text/css">
		#dashboard_right_now .b-posts,
		#dashboard_right_now .posts
		{ display:none !important; }
		</style>';
	}
	
	
	//DISABLE ADD NEW PAGE
	function disable_add_new_page() {
		add_action('admin_head', array($this, 'hide_add_new_page_button'));
		add_action('wp_before_admin_bar_render', array($this, 'remove_pages_admin_bar'));
		add_action('admin_menu', array($this, 'remove_add_page_submenu'));
	}
	
	function remove_add_page_submenu() {
		global $submenu;
		unset($submenu['edit.php?post_type=page'][10]);
	}

	function remove_pages_admin_bar() { 
		global $wp_admin_bar;
		$wp_admin_bar->remove_node('new-page');
	}
	
	function hide_add_new_page_button() {
		global $post;
		if ($post->post_type == 'page') {
			echo '
			<style type="text/css">
			.add-new-h2 { display:none; }
			</style>';
		}
	}
	
	
	//OTHER FUNCTIONS
	
	//hide page attributes
	function hide_page_attributes() {
		add_action('admin_init', array($this, 'remove_pageparent_div'));
	}
	function remove_pageparent_div() {
		remove_meta_box('pageparentdiv', 'page', 'normal');
	}
	
	
}
?>