<?php


/*
 * Content Filters
 */

// Replace bible references with bible links
add_filter('the_content', 'bfox_ref_replace_html');
add_filter('comment_text', 'bfox_ref_replace_html');
add_filter('the_excerpt', 'bfox_ref_replace_html');

/**
 * Replaces taxonomy links with Bible reference links
 *
 * @param string $taxonomy
 */
function bfox_add_ref_links_to_taxonomy($taxonomy) {
	add_filter("term_links-$taxonomy", 'bfox_add_tag_ref_tooltips');
}

/**
 * Finds any bible references in an array of tag links and adds tooltips to them
 *
 * Should be used to filter 'term_links-post_tag', called in get_the_term_list()
 *
 * @param array $tag_links
 * @return array
 */
function bfox_add_tag_ref_tooltips($tag_links) {
	if (!empty($tag_links)) foreach ($tag_links as &$tag_link) if (preg_match('/<a.*>(.*)<\/a>/', $tag_link, $matches)) {
		$tag = $matches[1];
		$ref = bfox_ref_from_tag($tag);
		if ($ref->is_valid()) {
			$tag_link = bfox_ref_link($ref->get_string(), array('text' => $tag));
		}
	}
	return $tag_links;
}

/*
 * Admin Page Functions
 */

/**
 * Bible post write link handling
 *
 * Pretty hacky, but better than previous javascript hack
 * HACK necessary until WP ticket 10544 is fixed: http://core.trac.wordpress.org/ticket/10544
 *
 * @param string $page
 * @param string $context
 * @param object $post
 */
function bfox_bible_post_link_setup($page, $context, $post) {
	if ((!$post->ID || 'auto-draft' == $post->post_status) && 'post' == $page && 'side' == $context && !empty($_REQUEST['bfox_ref'])) {
		$hidden_ref = new BfoxRef($_REQUEST['bfox_ref']);
		if ($hidden_ref->is_valid()) {
			global $wp_meta_boxes;
			// Change the callback function
			$wp_meta_boxes[$page][$context]['core']['tagsdiv-post_tag']['callback'] = 'bfox_post_tags_meta_box';

			function bfox_post_tags_meta_box($post, $box) {
				function bfox_wp_get_object_terms($terms) {
					$hidden_ref = new BfoxRef($_REQUEST['bfox_ref']);
					if ($hidden_ref->is_valid()) {
						$term = new stdClass;
						$term->name = $hidden_ref->get_string();
						$terms = array($term);
					}
					return $terms;
				}

				// We need our filter on wp_get_object_terms to get called, but it won't be if post->ID is 0, so we set it to -1
				add_action('wp_get_object_terms', 'bfox_wp_get_object_terms');
				post_tags_meta_box($post, $box);
				remove_action('wp_get_object_terms', 'bfox_wp_get_object_terms');
			}
		}
	}
}
add_action('do_meta_boxes', 'bfox_bible_post_link_setup', 10, 3);


?>