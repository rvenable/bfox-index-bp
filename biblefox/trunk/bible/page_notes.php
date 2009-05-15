<?php

class BfoxPageNotes extends BfoxPage {

	const var_submit = 'submit';
	const var_note_id = 'note_id';
	const var_content = 'content';

	public function page_load() {

		if (isset($_POST[self::var_submit])) {
			$note = BfoxNotes::get_note($_POST[self::var_note_id]);
			$note->set_content(strip_tags(stripslashes($_POST[self::var_content])));
			BfoxNotes::save_note($note);
			wp_redirect(self::edit_note_url($note->id));
		}
	}

	public static function edit_note_url($note_id) {
		return add_query_arg(self::var_note_id, $note_id, BfoxQuery::page_url(BfoxQuery::page_notes));
	}

	public function content() {
		$notes = BfoxNotes::get_notes();

		$notes_table = new BfoxHtmlTable();
		foreach ($notes as $note) {
			$refs = $note->get_refs();
			$ref_str = $refs->get_string();

			$notes_table->add_row('', 3,
				$note->get_modified(),
				$note->get_title() . " (<a href='" . self::edit_note_url($note->id) . "'>edit</a>)",
				"<a href='" . BfoxQuery::passage_page_url($ref_str, $this->translation) . "'>$ref_str</a>");
		}

		echo "<h2>My Notes</h2>\n";
		echo $notes_table->content();

		$note = BfoxNotes::get_note($_GET[self::var_note_id]);

		if (empty($note->id)) $edit_header = __('Create a Note');
		else $edit_header = __('Edit Note');

		echo "<h3>$edit_header</h3>\n";
		self::edit_note($note);
	}

	public static function edit_note(BfoxNote $note) {
		$table = new BfoxHtmlOptionTable("class='form-table'", "action='" . BfoxQuery::page_url(BfoxQuery::page_notes) . "' method='post'",
			BfoxUtility::hidden_input(self::var_note_id, $note->id),
			"<p><input type='submit' name='" . self::var_submit . "' value='" . __('Save') . "' class='button'/></p>");

		$content = $note->get_content();

		if (!empty($content)) $table->add_option(__('Note'), '', $note->get_display_content(), '');

		// Note Content
		$table->add_option(__('Edit'), '', $table->option_textarea(self::var_content, $content, 15, 50), '');

		echo $table->content();
	}
}

?>