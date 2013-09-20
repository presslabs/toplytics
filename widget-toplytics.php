<?php
class Toplytics_WP_Widget_Most_Visited_Posts extends WP_Widget {

	function Toplytics_WP_Widget_Most_Visited_Posts() {
		$widget_ops = array(
			'classname' => 'widget_most_visited_posts', 
			'description' => __("Toplytics - The most visited posts on your site from Google Analytics", TOPLYTICS_TEXTDOMAIN)
		);
		$this->WP_Widget('most-visited-posts', __('Most Visited Posts', TOPLYTICS_TEXTDOMAIN), $widget_ops);
		$this->alt_option_name = 'widget_most_visited_posts';
	}

	function widget($args, $instance) {
		ob_start();
		extract($args);

		$title = apply_filters('widget_title', 
			empty($instance['title']) ? __('Most Visited Posts', TOPLYTICS_TEXTDOMAIN) : $instance['title'], 
			$instance, $this->id_base);
		
		$period = $instance['period'];
		if (!in_array($period,array('today','week','month'))) $period = 'month';
	  
		$thumbnail = $instance['thumbnail'];
		if (!in_array($thumbnail,array('none','featuredimage','firstimage','anyimage'))) $thumbnail = 'featuredimage';

		$show_views = $instance['show_views'] ? 1 : 0;
	  
		// Get the info from transient
		$results = get_transient('toplytics.cache');

	  	$number = $instance['number'];
	  	$counter= $number;
		foreach ($results[$period] as $post_id => $pv) {
			if ($number <= 0) break;
			$toplytics_results[$post_id] = $pv;
			$number--;
		}

		if (!empty($results[$period])) {
			echo $before_widget;
			include toplytics_get_template_path($instance['list_type']);
			echo $after_widget;

			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();
		} else {
			_e("The statistics found in GA account doesn't match with your posts/pages.", TOPLYTICS_TEXTDOMAIN);
		}
		ob_get_flush();
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
	  
		if ( !$number = (int) $new_instance['number'] )
			$number = TOPLYTICS_DEFAULT_POSTS;
		else if ( $number < TOPLYTICS_MIN_POSTS )
			$number = TOPLYTICS_MIN_POSTS;
		else if ( $number > TOPLYTICS_MAX_POSTS )
			$number = TOPLYTICS_MAX_POSTS;

		$instance['number'] = $number;

		$instance['period'] = $new_instance['period'];
		if (!in_array($instance['period'],array('today','week','month')))	$instance['period'] = 'today';

		$instance['thumbnail'] = $new_instance['thumbnail'];
		if (!in_array($instance['thumbnail'],array('none','featuredimage','firstimage','anyimage')))	$instance['thumbnail'] = 'featuredimage';

		$instance['list_type'] = $new_instance['list_type'];
		$instance['show_views'] = $new_instance['show_views'] ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		$widget_title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		if ( !isset($instance['number']) || !$number = (int) $instance['number'] )
			$number = 5;
		$period = isset($instance['period']) ? $instance['period'] : 'today';
		$thumbnail = isset($instance['thumbnail']) ? $instance['thumbnail'] : 'featuredimage';
		$list_type = isset($instance['list_type']) ? $instance['list_type'] : 'default';

		$show_views = isset($instance['show_views']) ? $instance['show_views'] : 0;
		$show_views_checked = '';
	    if ( isset( $instance[ 'show_views' ] ) )
			$show_views_checked = $instance['show_views'] ? ' checked="checked" ' : '';
?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?>:</label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $widget_title; ?>" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts to show', TOPLYTICS_TEXTDOMAIN); ?>:</label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id('period'); ?>"><?php _e('Time period', TOPLYTICS_TEXTDOMAIN); ?>:</label>
		<select id="<?php echo $this->get_field_id('period'); ?>" name="<?php echo $this->get_field_name('period'); ?>">
			<option value="today" <?php if ($period == 'today') echo 'selected="selected"'; echo '>' . __('Daily', TOPLYTICS_TEXTDOMAIN); ?></option>
			<option value="week" <?php if ($period == 'week') echo 'selected="selected"'; echo '>' . __('Weekly', TOPLYTICS_TEXTDOMAIN); ?></option>
			<option value="month" <?php if ($period == 'month') echo 'selected="selected"'; echo '>' . __('Monthly', TOPLYTICS_TEXTDOMAIN); ?></option>
		</select>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id('thumbnail'); ?>"><?php _e('Image'); ?>:</label>
		<select id="<?php echo $this->get_field_id('thumbnail'); ?>" name="<?php echo $this->get_field_name('thumbnail'); ?>">
			<option value="none" <?php if ($thumbnail == 'none') echo 'selected="selected"'; echo '>' . __('None'); ?></option>
			<option value="featuredimage" <?php if ($thumbnail == 'featuredimage') echo 'selected="selected"'; echo '>' . __('Featured Image'); ?></option>
			<option value="firstimage" <?php if ($thumbnail == 'firstimage') echo 'selected="selected"'; echo '>' . __('First Image', TOPLYTICS_TEXTDOMAIN); ?></option>
			<option value="anyimage" <?php if ($thumbnail == 'anyimage') echo 'selected="selected"'; echo '>' . __('Featured/First Image', TOPLYTICS_TEXTDOMAIN); ?></option>
		</select>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php echo $show_views_checked; ?> id="<?php echo $this->get_field_id('show_views'); ?>" name="<?php echo $this->get_field_name('show_views'); ?>" /> <label for="<?php echo $this->get_field_id('show_views'); ?>"><?php echo __( 'Display post views', TOPLYTICS_TEXTDOMAIN ); ?>?</label>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id('list_type'); ?>"><?php _e('Template'); ?>:</label>
		<select id="<?php echo $this->get_field_id('list_type'); ?>" name="<?php echo $this->get_field_name('list_type'); ?>">
<?php
$toplytics_templates = toplytics_get_templates_list();
	foreach($toplytics_templates as $slug) {
?>
	<option value="<?php echo $slug; ?>"<?php if ($list_type == $slug) echo ' selected="selected"'; ?>><?php echo toplytics_get_template_name($slug); ?></option>
<?php } ?>
		</select>
		</p>
<?php
	}
}

