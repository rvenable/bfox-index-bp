<?php

class BfoxNewPostRefLinker extends BfoxRefLinker {

	var $homeUrl;

	function __construct() {
		parent::__construct();

		$this->homeUrl = get_option('home');
	}

	function urlForRefStr($refStr) {
		$refStr = parent::urlForRefStr($refStr);
		return rtrim($this->homeUrl, '/') . '/wp-admin/post-new.php?bfox_ref=' . $ref_str;
	}
}

?>