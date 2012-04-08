<?php

class BfoxPostSearchRefLinker extends BfoxRefLinker {

	function urlForRefStr($refStr) {
		$refStr = parent::urlForRefStr($refStr);

		// NOTE: This function imitates the WP get_tag_link() function, but instead of getting a tag slug, we use $refStr
		global $wp_rewrite;
		$taglink = $wp_rewrite->get_search_permastruct();

		if (empty($taglink)) $taglink = get_option('home') . '/?s=' . $refStr;
		else {
			$taglink = str_replace('%search%', $refStr, $taglink);
			$taglink = get_option('home') . '/' . user_trailingslashit($taglink, 'category');
		}

		return $taglink;
	}
}

?>