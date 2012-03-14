<?php

class BfoxIndexController extends BfoxPluginController {

	var $blogDir;

	function init() {
		parent::init();

		$this->blogDir = $this->dir . '/biblefox-blog';

		require_once $this->blogDir . '/biblefox-blog.php';
		require_once $this->blogDir . '/posts.php';
	}

	function wpInit() {
		bfox_add_taxonomy_ref_support('post_tag');
		bfox_add_post_type_ref_support('post', array('post_content', 'post_tag'));
		bfox_add_ref_links_to_taxonomy('post_tag');
		bfox_add_ref_admin_column('post', 'author');
	}

	function wpAdminMenu() {
		// Add the quick view meta box
		bfox_add_quick_view_meta_box('post');

		if (is_multisite() || !get_site_option('bfox-ms-allow-blog-options'))
			return;

		require_once $this->blogDir . '/admin.php';

		add_options_page(
			__('Bible Settings', 'bfox'), // Page title
			__('Bible Settings', 'bfox'), // Menu title
			'manage_options', // Capability
			'bfox-blog-settings', // Menu slug
			'bfox_blog_admin_page' // Function
		);

		add_settings_section('bfox-admin-settings-main', 'Settings', 'bfox_bp_admin_settings_bible_directory', 'bfox-admin-settings');

		add_settings_section('bfox-blog-admin-settings-main', __('Settings', 'bfox'), 'bfox_blog_admin_settings_main', 'bfox-blog-admin-settings');

		add_settings_field('bfox-tooltips', __('Disable Bible Reference Tooltips', 'bfox'), 'bfox_blog_admin_setting_tooltips', 'bfox-blog-admin-settings', 'bfox-blog-admin-settings-main', array('label_for' => 'bfox-toolips'));
		register_setting('bfox-blog-admin-settings', 'bfox-blog-options');

		do_action('bfox_blog_admin_menu');
	}

	function wpNetworkAdminMenu() {
		require_once $this->blogDir . '/ms-admin.php';
		require_once $this->blogDir . '/admin.php'; // We need to load this for the blog options functions (for instance, bfox_blog_admin_setting_tooltips())

		add_submenu_page(
			'settings.php', // Parent slug
			__('Biblefox', 'bfox'), // Page title
			__('Biblefox', 'bfox'), // Menu title
			10, // Capability
			'bfox-ms', // Menu slug
			'bfox_ms_admin_page' // Function
		);

		add_settings_section('bfox-ms-admin-settings-main', __('Settings', 'bfox'), 'bfox_ms_admin_settings_main', 'bfox-ms-admin-settings');
		add_settings_field('bfox-ms-allow-blog-options', __('Allow Biblefox Blog Options', 'bfox'), 'bfox_ms_admin_setting_allow_blog_options', 'bfox-ms-admin-settings', 'bfox-ms-admin-settings-main', array('label_for' => 'bfox-ms-allow-blog-options'));

		// Blog settings (found in admin.php, not ms-admin.php)
		add_settings_field('bfox-tooltips', __('Disable Bible Reference Tooltips', 'bfox'), 'bfox_blog_admin_setting_tooltips', 'bfox-ms-admin-settings', 'bfox-ms-admin-settings-main', array('label_for' => 'bfox-tooltips'));

		do_action('bfox_ms_admin_menu');
	}
}

?>