<?php

class BfoxIndexAdminController extends BfoxPluginController {

	/**
	 * @var BfoxIndexController
	 */
	var $index;

	private $postTypeRefColumnPositions = array();

	/**
	 * Add a column on the post_type admin page for Bible References
	 *
	 * The position parameter specified the name of the column that this new column will come immediately after
	 *
	 * @param string $post_type
	 * @param string $position
	 */
	function addRefAdminColumn($post_type, $position = false) {
		$this->postTypeRefColumnPositions[$post_type] = $position;
	}

	/**
	 * Filter function for adding biblefox columns to the edit posts list
	 *
	 * @param $columns
	 * @param $post_type (Default is 'page' because the 'manage_pages_columns' filter doesn't pass the post type)
	 * @return array
	 */
	function wp2ManagePostsColumns($columns, $post_type = 'page') {
		if (isset($this->postTypeRefColumnPositions[$post_type])) {
			$position = $this->postTypeRefColumnPositions[$post_type];
			$column_text = __('Bible References', 'bfox');

			// If a position was set, place the column after that position
			// Otherwise just add the column
			if ($position) {
				// Create a new columns array with our new columns, and in the specified order
				// See wp_manage_posts_columns() for the list of default columns
				$new_columns = array();
				foreach ($columns as $key => $column) {
					$new_columns[$key] = $column;

					// Add the bible verse column right after 'author' column
					if ($position == $key) $new_columns['bfox_col_ref'] = $column_text;
				}
				$columns = $new_columns;
			}
			else {
				$columns['bfox_col_ref'] = $column_text;
			}
		}
		return $columns;
	}

	function wpManagePagesColumns($columns) {
		return $this->wpManagePostsColumns($columns, 'page');
	}

	/**
	 * Action function for displaying bible reference information in the edit posts list
	 *
	 * @param string $column_name
	 * @param integer $post_id
	 * @return none
	 */
	function wp2ManagePostsCustomColumn($column_name, $post_id) {
		if ('bfox_col_ref' == $column_name) {
			global $post;
			$ref = $this->index->refForPost($post);
			if ($ref->is_valid()) {
				$refStr = $ref->get_string(BibleMeta::name_short);
				$url = admin_url('edit.php?tag=' . urlencode($refStr));
				echo "<a href='$url'>$refStr</a>";
			}
		}
	}

	function wp2ManagePagesCustomColumn($column_name, $post_id) {
		$this->wpManagePostsCustomColumn($column_name, $post_id);
	}

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
	function wp3DoMetaBoxes($page, $context, $post) {
		if (!empty($_REQUEST['bfox_ref']) && !empty($post) && (!$post->ID || 'auto-draft' == $post->post_status) && 'post' == $page && 'side' == $context) {
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
}

?>