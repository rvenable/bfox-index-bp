<?php

/**
 * Returns a url for the search page using a Bible Reference as the search filter
 *
 * Should be used whenever we want to link to the Bible search archive, as opposed to the Bible reader
 *
 * @param string $ref_str
 * @return string
 */
function bfox_ref_blog_url($ref_str) {
	$ref_str = urlencode(strtolower($ref_str));

	// NOTE: This function imitates the WP get_tag_link() function, but instead of getting a tag slug, we use $ref_str
	global $wp_rewrite;
	$taglink = $wp_rewrite->get_search_permastruct();

	if (empty($taglink)) $taglink = get_option('home') . '/?s=' . $ref_str;
	else {
		$taglink = str_replace('%search%', $ref_str, $taglink);
		$taglink = get_option('home') . '/' . user_trailingslashit($taglink, 'category');
	}

	return $taglink;
}

function bfox_blog_ref_write_url($ref_str, $home_url = '') {
	if (empty($home_url)) $home_url = get_option('home');

	return rtrim($home_url, '/') . '/wp-admin/post-new.php?bfox_ref=' . urlencode($ref_str);
}

function bfox_blog_ref_write_link($ref_str, $text = '', $home_url = '') {
	if (empty($text)) $text = $ref_str;

	return "<a href='" . bfox_blog_ref_write_url($ref_str, $home_url) . "'>$text</a>";
}

/**
 * Filters tags for bible references and changes their slugs to be bible reference friendly
 *
 * @param $term
 * @return object $term
 */
function bfox_blog_get_post_tag($term) {
	if ($ref = BfoxRefParser::no_leftovers($term->name)) $term->slug = urlencode($ref->get_string());
	return $term;
}
add_filter('get_post_tag', 'bfox_blog_get_post_tag', 10, 2);

/**
 * Returns a WP_Query object with the posts that contain the given BfoxRef
 *
 * @param BfoxRef $ref
 * @return WP_Query
 */
function bfox_blog_query_for_ref(BfoxRef $ref) {
	return new WP_Query('s=' . urlencode($ref->get_string()));
}


// Add "Settings" link on plugins menu
function bfox_blog_admin_add_action_link($links, $file) {
	if ('biblefox-for-wordpress/biblefox.php' != $file) return $links;

	if (!is_multisite() || get_site_option('bfox-ms-allow-blog-options')) array_unshift($links, '<a href="' . menu_page_url('bfox-blog-settings', false) . '">' . __('Settings', 'bfox') . '</a>');
	if (is_multisite()) array_unshift($links, '<a href="' . menu_page_url('bfox-ms', false) . '">' . __('Network Settings', 'bfox') . '</a>');

	return $links;
}
add_filter('plugin_action_links', 'bfox_blog_admin_add_action_link', 10, 2);

function bfox_blog_options() {
	global $_bfox_blog_options;

	if (!isset($_bfox_blog_options)) {
		// Get the options using get_site_option() first (which will be get_option() if not multisite anyway)
		$_bfox_blog_options = (array) get_site_option('bfox-blog-options');

		// If we are allowing the blog to set options, use them to overwrite the defaults
		if (is_multisite() && get_site_option('bfox-ms-allow-blog-options'))
			$_bfox_blog_options = array_merge($_bfox_blog_options, (array) get_option('bfox-blog-options'));
	}

	return $_bfox_blog_options;
}

function bfox_blog_option($key) {
	$options = bfox_blog_options();
	return $options[$key];
}

?>