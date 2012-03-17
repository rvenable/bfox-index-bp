<?php


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