<?php

class BfoxBibleWidget extends WP_Widget {

	/**
	 * @var BfoxRefs
	 */
	private $refs;

	public function __construct($id_base = false, $name, $widget_options = array(), $control_options = array()) {
		$this->refs = new BfoxRefs('genesis 1');


		parent::__construct($id_base, $name, $widget_options, $control_options);
	}
}

class BfoxFriendsPostsWidget extends BfoxBibleWidget {

	public function __construct() {
		parent::__construct(false, 'Bible - Friends\' Posts Widget');
	}

	public function widget($args, $instance) {
		extract($args);
		if (empty($refs)) $ref = new BfoxRefs;

		echo $before_widget . $before_title . $instance['title'] . $after_title;

		// If no user, use the current user
		if (empty($user_id)) $user_id = $GLOBALS['user_ID'];

		$friends_url = bp_core_get_user_domain($user_id) . 'friends/my-friends/all-friends';

		?>
		<div class="cbox_sub">
			<div class="cbox_head">
				<a href="<?php echo $friends_url ?>">My Friends' Blog Posts</a>
			</div>
			<div class='cbox_body'>
		<?php

		$total_post_count = 0;

		$friend_ids = array();
		if (class_exists(BP_Friends_Friendship)) {
			$friend_ids = BP_Friends_Friendship::get_friend_user_ids($user_id);

			$mem_dir_url = bp_core_get_root_domain() . '/members/';

			if (!empty($friend_ids)) {
				global $wpdb;

				// Add the current user to the friends so that we get his posts as well
				$friend_ids []= $user_id;

				$user_post_ids = BfoxPosts::get_post_ids_for_users($refs, $friend_ids);

				if (!empty($user_post_ids)) foreach ($user_post_ids as $blog_id => $post_ids) {
					$posts = array();

					switch_to_blog($blog_id);

					if (!empty($post_ids)) {
						BfoxBlogQueryData::set_post_ids($post_ids);
						$query = new WP_Query(1);
						$post_count = $query->post_count;
					}
					else $post_count = 0;
					$total_post_count += $post_count;

					while(!empty($post_ids) && $query->have_posts()) :?>
						<?php $query->the_post() ?>
						<div class="cbox_sub_sub">
							<div class='cbox_head'><strong><?php the_title(); ?></strong> (<?php echo bfox_the_refs(BibleMeta::name_short) ?>) by <?php the_author() ?> (<?php the_time('F jS, Y') ?>)</div>
							<div class='cbox_body box_inside'>
								<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
								<small><?php the_time('F jS, Y') ?>  by <?php the_author() ?></small>
								<div class="post_content">
									<?php the_content('Read the rest of this entry &raquo;') ?>
									<p class="postmetadata"><?php the_tags('Tags: ', ', ', '<br />'); ?> Posted in <?php the_category(', ') ?> | <?php edit_post_link('Edit', '', ' | '); ?>  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></p>
								</div>
							</div>
						</div>
					<?php endwhile;
					restore_current_blog();
				}
				else {
					printf(__('None of your friends have written any posts about %s.
					You can %s.
					You can also find more friends using the %s.'),
					$refs->get_string(),
					"<a href='$write_url'>" . __('write your own post') . "</a>",
					"<a href='$mem_dir_url'>" . __('members directory') . "</a>");
				}
			}
			else {
				printf(__('This menu shows you any blog posts written by your friends about this passage.
				You don\'t currently have any friends. That\'s okay, because you can find some friends using our %s.'),
				"<a href='$mem_dir_url'>" . __("members directory") . "</a>");
			}
		}
		else {
			_e('This widget requires BuddyPress.');
		}

		?>
			</div>
		</div>
		<?php
		echo $after_widget;
	}
}

class BfoxUserHistoryWidget extends WP_Widget {
	public function __construct() {
		parent::__construct(false, __('User Bible History'));
	}

	public function widget($args, $instance) {
		extract($args);

		if ('passage' == $instance['type']) {
			$refs = bp_bible_the_refs();
			if (empty($instance['title'])) $instance['title'] = __('My History for %s');
			$instance['title'] = sprintf($instance['title'], $refs->get_string());
		}
		else {
			$refs = NULL;
			if (empty($instance['title'])) $instance['title'] = __('My Bible History');
		}

		if (1 > $max) $max = 10;

		echo $before_widget . $before_title . $instance['title'] . $after_title;

		global $user_ID;

		if (empty($user_ID)) $content = "<p>" . BiblefoxSite::loginout() . __(' to track the Bible passages you read.</p>');
		else {
			$history = BfoxHistory::get_history($instance['number'], 0, $refs);

			if ('table' == $instance['style']) {
				$table = new BfoxHtmlTable("class='widefat'");

				foreach ($history as $event) $table->add_row('', 5,
					$event->desc(),
					$event->ref_link(),
					BfoxUtility::nice_date($event->time),
					date('g:i a', $event->time),
					$event->toggle_link());

				$content = $table->content();
			}
			else {
				$list = new BfoxHtmlList();

				foreach ($history as $event) $list->add($event->ref_link($instance['ref_name']));

				$content = $list->content();
			}
		}

		echo $content . $after_widget;
	}

	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['ref_name'] = $new_instance['ref_name'];
		$instance['style'] = $new_instance['style'];
		$instance['type'] = $new_instance['type'];

		return $instance;
	}

	public function form($instance) {
		$title = esc_attr($instance['title']);
		if ( !$number = (int) $instance['number'] )
			$number = 10;
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

		<p>
			<label for="<?php echo $this->get_field_id('ref_name'); ?>"><?php _e( 'Bible References:' ); ?></label>
			<select name="<?php echo $this->get_field_name('ref_name'); ?>" id="<?php echo $this->get_field_id('ref_name'); ?>" class="widefat">
				<option value="<?php echo BibleMeta::name_normal ?>"<?php selected( $instance['ref_name'], BibleMeta::name_normal ); ?>><?php _e('Normal'); ?></option>
				<option value="<?php echo BibleMeta::name_short ?>"<?php selected( $instance['ref_name'], BibleMeta::name_short ); ?>><?php _e('Short'); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('style'); ?>"><?php _e( 'Display Style:' ); ?></label>
			<select name="<?php echo $this->get_field_name('style'); ?>" id="<?php echo $this->get_field_id('style'); ?>" class="widefat">
				<option value="list"<?php selected( $instance['style'], 'list' ); ?>><?php _e('List'); ?></option>
				<option value="table"<?php selected( $instance['style'], 'table' ); ?>><?php _e('Table'); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('type'); ?>"><?php _e( 'History Type:' ); ?></label>
			<select name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>" class="widefat">
				<option value="all"<?php selected( $instance['type'], 'all' ); ?>><?php _e('All History'); ?></option>
				<option value="passage"<?php selected( $instance['type'], 'passage' ); ?>><?php _e('Passage History'); ?></option>
			</select>
		</p>
		<?php
    }
}

class BfoxBibleTocWidget extends WP_Widget {
	public function __construct() {
		parent::__construct(false, __('Bible Table of Contents'));
	}

	public function widget($args, $instance) {
		extract($args);

		if (empty($instance['title'])) $instance['title'] = __('%s - Table of Contents');
		$books = bp_bible_the_books();

		foreach ($books as $book) {
			$book_name = BibleMeta::get_book_name($book);
			$end_chapter = BibleMeta::end_verse_max($book);

			$title = sprintf($instance['title'], $book_name);
			echo $before_widget . $before_title . $title . $after_title;
			?>
			<ul class='flat_toc'>
			<?php for ($ch = BibleMeta::start_chapter; $ch <= $end_chapter; $ch++): ?>
				<li><a href='<?php echo BfoxQuery::ref_url("$book_name $ch") ?>'><?php echo $ch ?></a></li>
			<?php endfor ?>
			</ul>
			<?php
			echo $after_widget;
		}

	}

	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	public function form($instance) {
		$title = esc_attr($instance['title']);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<?php
    }
}

class BfoxBiblePassageWidget extends WP_Widget {
	public function __construct() {
		parent::__construct(false, __('Bible Passage'));
	}

	public function widget($args, $instance) {
		extract($args);

		$refs = BfoxRefs('John 3');

		if (empty($instance['title'])) $instance['title'] = __('My Bible History');
		if (1 > $max) $max = 10;

		echo $before_widget . $before_title . $instance['title'] . $after_title;

		global $user_ID;

		if (empty($user_ID)) $content = "<p>" . BiblefoxSite::loginout() . __(' to track the Bible passages you read.</p>');
		else {
			$history = BfoxHistory::get_history($instance['number']);
			$list = new BfoxHtmlList();

			foreach ($history as $event) $list->add($event->ref_link($instance['ref_name']));

			$content = $list->content();
		}

		echo $content . $after_widget;
	}

	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['ref_name'] = $new_instance['ref_name'];

		return $instance;
	}

	public function form($instance) {
		$title = esc_attr($instance['title']);
		if ( !$number = (int) $instance['number'] )
			$number = 10;
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

		<p>
			<label for="<?php echo $this->get_field_id('ref_name'); ?>"><?php _e( 'Bible References:' ); ?></label>
			<select name="<?php echo $this->get_field_name('ref_name'); ?>" id="<?php echo $this->get_field_id('ref_name'); ?>" class="widefat">
				<option value="<?php echo BibleMeta::name_normal ?>"<?php selected( $instance['ref_name'], BibleMeta::name_normal ); ?>><?php _e('Normal'); ?></option>
				<option value="<?php echo BibleMeta::name_short ?>"<?php selected( $instance['ref_name'], BibleMeta::name_short ); ?>><?php _e('Short'); ?></option>
			</select>
		</p>
		<?php
    }
}

function bfox_bible_widgets_init() {
	register_widget('BfoxUserHistoryWidget');
	register_widget('BfoxBibleTocWidget');
}

function bfox_bible_register_sidebars() {
	register_sidebars(1,
		array(
			'name' => 'bible-passage-sidebar',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
	        'after_widget' => '</div>',
	        'before_title' => '<h2 class="widgettitle">',
	        'after_title' => '</h2>'
		)
	);
	register_sidebars(1,
		array(
			'name' => 'bible-passage',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
	        'after_widget' => '</div>',
	        'before_title' => '<h2 class="widgettitle">',
	        'after_title' => '</h2>'
		)
	);
}

add_action('widgets_init', 'bfox_bible_widgets_init');
add_action('init', 'bfox_bible_register_sidebars');


?>