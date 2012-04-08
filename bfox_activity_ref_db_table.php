<?php

define('BFOX_POST_REF_TABLE_VERSION', 2);

/**
 * Manages a DB table with a list of bible references for blog posts
 *
 * @author richard
 *
 */
class BfoxPostRefDbTable extends BfoxRefDbTable {

	/**
	 * @var BfoxIndexController
	 */
	var $index;

		public function __construct() {
		global $wpdb;
		parent::__construct($wpdb->posts);
		$this->set_item_id_definition(array('item_id' => '%d', 'taxonomy' => '%s'));
	}

	public function check_install($version = BFOX_POST_REF_TABLE_VERSION) {
		$old_version = get_option($this->table_name . '_version');
		if ($old_version < $version) {
			require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
			dbDelta("CREATE TABLE $this->table_name (
					item_id BIGINT(20) NOT NULL,
					start MEDIUMINT UNSIGNED NOT NULL,
					end MEDIUMINT UNSIGNED NOT NULL,
					taxonomy VARCHAR(32) NOT NULL DEFAULT '',
					KEY item_id (item_id),
					KEY sequence (start,end)
				);"
			);

			if ($old_version < 2) {
				require_once BFOX_DIR . '/upgrade.php';
				bfox_upgrade_post_ref_table_2($this->table_name);
			}

			update_option($this->table_name . '_version', $version);
		}
	}

	public function save_post($post) {
		$post_id = $post->ID;

		$saved = false;
		if (!empty($post_id)) {
			$refs_for_taxonomies = $this->index->refsForTaxonomiesOfPost($post);
			foreach ($refs_for_taxonomies as $taxonomy => $ref) {
				$saved = $this->save_item(array('item_id' => $post->ID, 'taxonomy' => $taxonomy), $ref) || $saved;
			}
		}
		return $saved;
	}

	public function refresh_select($id_col, $content_col, $limit = 0, $offset = 0) {
		$post_types = $this->index->indexedPostTypes();
		if (!empty($post_type)) $where = 'WHERE post_type IN (' . implode(',', $wpdb->escape($post_types)) . ')';

		return "* FROM $this->data_table_name $where ORDER BY $id_col ASC LIMIT $offset, $limit";
	}

	public function save_data_row($data_row, $id_col, $content_col) {
		return $this->save_post($data_row);
	}
}

?>