<?php

class BfoxIndexQueryController extends BfoxPluginController {

	/**
	 * @var BfoxIndexController
	 */
	var $index;

	/*
	 * Post Query Functions
	*/

	/**
	 * Prepares a blog post query to look for bible references
	 *
	 * @param WP_Query $wp_query
	 */
	function wpParseQuery($wp_query) {

		// Bible Reference tags should redirect to a ref search
		if (!empty($wp_query->query_vars['tag']) &&
			isset($wp_query->query_vars['post_type']) &&
			$this->index->postTypeIsIndexed($wp_query->query_vars['post_type'], 'post_tag')) {
			$ref = $this->index->core->refs->refFromTag($wp_query->query_vars['tag']);
			if ($ref->is_valid()) {
				wp_redirect($this->index->urlForRefStr($wp_query->query_vars['tag']));
				die();
			}
		}

		// Check to see if the search string is a bible reference
		if (!empty($wp_query->query_vars['s'])) {
			// TODO: use leftovers
			$ref = $this->index->core->refs->refFromTag(urldecode($wp_query->query_vars['s']));
			if ($ref->is_valid()) {
				// Store the ref in the WP_Query
				$wp_query->bfox_ref = $ref;
			}
		}
	}

	function wp2PostsJoin($join, $query) {
		global $wpdb;
		if (isset($query->bfox_ref)) {
			$table = $this->index->dbTable();
			$join .= ' ' . $table->join_sql("$wpdb->posts.ID");
		}
		return $join;
	}

	function wp2PostsSearch($search, $query) {
		$table = $this->index->dbTable();
		if (isset($query->bfox_ref)) $search = ' AND ' . $table->seqs_where($query->bfox_ref);
		return $search;
	}

	function wp2PostsGroupby($sql, $query) {
		global $wpdb;
		// Bible references searches need to group on the post ID
		if (isset($query->bfox_ref)) {
			// We will try to add the post ID as a column to group by
			$new_column = "$wpdb->posts.ID";

			// If the SQL is blank, just add the new column
			// Otherwise we want to make sure that it isn't already there,
			// because it might have been added by other parts of the query
			$sql = trim($sql);
			if (empty($sql)) $sql = $new_column;
			else if (false === stripos($sql, $new_column)) $sql .= ", $new_column";
		}
		return $sql;
	}
}

?>