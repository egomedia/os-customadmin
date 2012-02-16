<?php
/*
Plugin Name: OS Custom Admin
Description: Cleans up the Wordpress admin to make more user-friendly for corporate clients.
Version: 0.8
Author: Oli Salisbury
*/

if (WP_ADMIN) {
	$os_customadmin = new OS_CustomAdmin();
	//$os_customadmin->rename_post_object('Car', 'Cars');
}

class OS_CustomAdmin {
	
	//construct
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
		add_action('admin_bar_menu', array($this, 'custom_admin_bar'), 1000);
		add_action('admin_head', array($this, 'hide_add_new_page_button'));
		add_action('admin_menu', array($this, 'hide_updates_nag'));
		add_filter('tiny_mce_before_init', array($this, 'custom_tiny_mce'));
		$this->rename_post_object();
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
	
	//rename_post_object
	function rename_post_object($label_single='News', $label_plural='News') {
		$this->label_single = $label_single;
		$this->label_plural = $label_plural;
		add_action('init', array($this, 'change_post_object_labels'));
		add_action('admin_menu', array($this, 'change_post_menu_label'));
		add_action('admin_bar_menu', array($this, 'change_post_admin_bar_label'), 1000);
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
	
	function change_post_admin_bar_label($wp_admin_bar) { 
		$wp_admin_bar->add_node(array('id'=>'new-post', 'title'=>$this->label_single));
	}
	
	//custom admin bar
	function custom_admin_bar($wp_admin_bar) { 
		//remove wordpress tab
		$wp_admin_bar->remove_node('wp-logo'); 
		//remove visit site tab
		$wp_admin_bar->remove_node('view-site');
		//remove updates tab
		$wp_admin_bar->remove_node('updates');
		//remove comments tab
		$wp_admin_bar->remove_node('comments');
		//remove add new subtabs
		$wp_admin_bar->remove_node('new-page');
		$wp_admin_bar->remove_node('new-media');
		$wp_admin_bar->remove_node('new-link');
		$wp_admin_bar->remove_node('new-user');
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
	
}
?>