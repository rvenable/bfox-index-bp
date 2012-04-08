<?php

class BfoxIndexController extends BfoxRootPluginController {

	var $blogDir;

	/**
	 * @var BfoxIndexQueryController
	 */
	var $query;

	/**
	 * @var BfoxIndexAdminController
	 */
	var $admin;

	/**
	 * @var BfoxPostSearchRefLinker
	 */
	var $searchLinker;

	function init() {
		parent::init();

		$this->blogDir = $this->dir . '/biblefox-blog';

		require_once $this->dir . '/bfox_post_search_ref_linker.php';
		require_once $this->dir . '/bfox_new_post_ref_linker.php';
		require_once $this->blogDir . '/biblefox-blog.php';

		// Create the controller for managing the WP Query additions
		require_once $this->dir . '/bfox_index_query_controller.php';
		$this->query = new BfoxIndexQueryController();
		$this->query->index = $this;
		$this->searchLinker = new BfoxPostSearchRefLinker();
	}

	public function wpInit() {
		$this->indexTaxonomyForAllIndexedPostTypes('post_tag');
		$this->useRefLinksForTaxonomy('post_tag');
		$this->indexPostTypeUsingTaxonomies('post', array('post_content', 'post_tag'));
	}

	function wpAdminInit() {
		require_once $this->dir . '/bfox_index_admin_controller.php';
		$this->admin = new BfoxIndexAdminController();
		$this->admin->index = $this;

		$this->admin->addRefMetaBoxForPostType('post');
		$this->admin->addRefAdminColumn('post', 'author');

		$this->scanPosts(200);
	}

	/**
	 * @var BfoxPostRefDbTable
	 */
	private $_dbTable = null;

	/**
	 * @return BfoxPostRefDbTable
	 */
	function dbTable() {
		if (is_null($this->_dbTable)) $this->resetDbTable();
		return $this->_dbTable;
	}

	function resetDbTable() {
		require_once $this->dir . '/bfox_post_ref_db_table.php';
		$this->_dbTable = new BfoxPostRefDbTable();
		$this->_dbTable->index = $this;
		$this->_dbTable->check_install();
	}

	function wpSwitchBlog() {
		if (!is_null($this->_dbTable)) {
			$this->resetDbTable();
		}
	}

	/**
	* Save the bible references for a blog post
	*
	* @param integer $post_id
	* @param object $post
	*/
	function wp2SavePost($post_id, $post) {
		// Only save the post if it supports Bible references
		if ($this->postTypeIsIndexed($post->post_type)) {
			$table = $this->dbTable();
			$table->save_post($post);
		}
	}

	/**
	 * Delete the bible references for a blog post
	 *
	 * @param integer $post_id
	 */
	function wpDeletePost($post_id) {
		$table = $this->dbTable();
		$table->delete_simple_items($post_id);
	}

	function filterPostContent($content, $taxonomy = 'post_content') {
		global $post;
		if ($this->postTypeIsIndexed($post->post_type, $taxonomy)) {
			$content = $this->core->refs->replaceInHTML($content);
		}
		return $content;
	}

	function useRefLinksForTaxonomy($taxonomy) {
		$this->addFilter("term_links-$taxonomy", 'replaceRefsInTagLinks');
	}

	function replaceRefsInTagLinks($tagLinks) {
		return $this->core->refs->replaceInTagLinks($tagLinks, $this->searchLinker->replaceCallback());
	}

	function wpTheContent($content) {
		return $this->filterPostContent($content);
	}

	function wpTheExcerpt($content) {
		return $this->filterPostContent($content);
	}

	function wpCommentText($content) {
		return $this->filterPostContent($content);
	}

	/**
	 * Default function for returning a URL for a ref string
	 *
	 * @param string $refStr
	 */
	function urlForRefStr($refStr) {
		return $this->searchLinker->urlForRefStr($refStr);
	}

	/**
	* Adds Bible Reference support for a given taxonomy
	*
	* @param string $taxonomy
	*/
	function indexTaxonomyForAllIndexedPostTypes($taxonomy) {
		$this->indexPostTypeUsingTaxonomies('', array($taxonomy));
	}

	private $postTypes = array('_bfox_all_types' => array());

	/**
	 * Adds Bible Reference support for a given post_type/taxonomy combination
	 *
	 * Bible reference support means that Bible references will be detected and indexed for the given post_type/taxonomy combo.
	 * If no taxonomies specified, the default is array('post_content')
	 *
	 * @param string $post_type
	 * @param array $taxonomies
	 */
	function indexPostTypeUsingTaxonomies($post_type = '', $taxonomies = '') {
		if (!$post_type) $post_type = '_bfox_all_types';
		if (!$taxonomies) $taxonomies = array('post_content');

		foreach ((array) $taxonomies as $taxonomy) {
			$this->postTypes[$post_type][$taxonomy] = true;
		}
	}

	/**
	 * Removes Bible Reference support for a given post_type/taxonomy combination
	 *
	 * If no taxonomies specified, all support for the post_type will be removed
	 *
	 * @param string $post_type
	 * @param array $taxonomies
	 */
	function removePostTypeAndTaxonomies($post_type = '', $taxonomies = array()) {
		if (!$post_type) $post_type = '_bfox_all_types';
		if (empty($taxonomies)) $taxonomies = array_keys((array) $this->postTypes[$post_type]);
		foreach ((array) $taxonomies as $taxonomy) $this->postTypes[$post_type][$taxonomy] = false;
	}

	/**
	 * Returns whether a given post_type/taxonomy combination support bible references
	 *
	 * If no taxonomy given, returns whether the post_type supports refs with at least one taxonomy
	 * If no post_type given, returns whether the taxonomy supports refs by default with any post type (ie. taxonomies that are Bible specific would do this)
	 * If both given, returns whether the post_type/taxonomy combo supports refs
	 * If neither given, returns false
	 *
	 * @param string $post_type
	 * @param string $taxonomy
	 * @return bool
	 */
	function postTypeIsIndexed($post_type = '', $taxonomy = '') {
		if ($taxonomy) {
			if (!$post_type) $post_type = '_bfox_all_types';
			return $this->postTypes[$post_type][$taxonomy] || ($this->postTypes['_bfox_all_types'][$taxonomy] && false !== $this->postTypes[$post_type][$taxonomy]);
		}
		else {
			return $post_type && isset($this->postTypes[$post_type]) && in_array(true, (array) $this->postTypes[$post_type]);
		}
	}

	/**
	 * Returns an array of taxonomies that support refs
	 *
	 * @param $post_type
	 * @return array
	 */
	function indexedTaxonomiesForPostType($post_type) {
		$taxonomies = array();

		// Add the taxonomies for this post type
		if (isset($this->postTypes[$post_type])) {
			foreach ((array) $this->postTypes[$post_type] as $taxonomy => $is_supported) {
				if ($is_supported) {
					$taxonomies []= $taxonomy;
				}
			}
		}

		// Add the taxonomies that support all post types
		foreach ((array) $this->postTypes['_bfox_all_types'] as $taxonomy => $is_supported) {
			if ($is_supported && (!isset($this->postTypes[$post_type][$taxonomy]) || false !== $this->postTypes[$post_type][$taxonomy])) {
				$taxonomies []= $taxonomy;
			}
		}

		return $taxonomies;
	}

	/**
	 * Returns an array of post_types that support Bible references
	 *
	 * @return array
	 */
	function indexedPostTypes() {
		$post_types = array();
		foreach ($this->postTypes as $post_type => $taxonomies) if ('_bfox_all_types' != $post_type) {
			if (in_array(true, $taxonomies)) $post_types []= $post_type;
		}

		return $post_types;
	}

	/**
	 * Return the bible references for a given blog post
	 *
	 * @param $post
	 * @return BfoxRef
	 */
	function refsForTaxonomiesOfPost($post) {
		if (!is_object($post)) $post = get_post($post);

		$refs_for_taxonomies = array();

		if (!$post) {
			return $refs_for_taxonomies;
		}

		$taxonomies = $this->indexedTaxonomiesForPostType($post->post_type);
		foreach ($taxonomies as $taxonomy) {
			if ('post_content' == $taxonomy) {
				$ref = $this->core->refs->refFromPostContent($post->post_content);
			}
			else {
				$ref = new BfoxRef;
				$terms = wp_get_post_terms($post->ID, $taxonomy, array('fields' => 'names'));
				foreach ($terms as $term) $ref->add_ref($this->core->refs->refFromTag($term));
			}

			if ($ref && $ref->is_valid()) {
				$refs_for_taxonomies[$taxonomy] = $ref;
			}
			unset($ref);
		}

		return $refs_for_taxonomies;
	}

	/**
	 * Return the bible references for a given blog post
	 *
	 * @param $post
	 * @param string $taxonomy
	 * @return BfoxRef
	 */
	function refForPost($post, $taxonomy = '') {
		$total_ref = new BfoxRef;

		$refs_for_taxonomies = $this->refsForTaxonomiesOfPost($post);

		if (!empty($taxonomy)) {
			if (isset($refs_for_taxonomies[$taxonomy])) $total_ref = $total_ref->add_ref($refs_for_taxonomies[$taxonomy]);
		}
		else {
			foreach ($refs_for_taxonomies as $ref) $total_ref->add_ref($ref);
		}

		return $total_ref;
	}

	/**
	 * Scans a given number of blog posts for Bible references
	 *
	 * @param unknown_type $limit
	 */
	private function scanPosts($limit) {
		$indexedPostTypes = $this->options->value('indexedPostTypes');
		if ($indexedPostTypes != $this->postTypes) {
			// If the configuration has changed, reset the indexes
			$this->options->setValue('indexStatus', array());
			$this->options->setValue('indexedPostTypes', $this->postTypes);
		}

		$status = $this->options->value('indexStatus');
		if (!isset($status['scanned'])) {
			$status = array('scanned' => 0, 'indexed' => 0, 'total' => 0);
		}

		if ($status['scanned'] < $status['total']) {
			extract(BfoxRefDbTable::simple_refresh($table, 'ID', '', $limit, $status['scanned']));
			$status['scanned'] += $scanned;
			$status['indexed'] += $indexed;
			$status['total'] = $total;
			$this->options->setValue('indexStatus', $status);
		}
	}
}

?>