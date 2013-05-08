<?php
class Toplytics_WP_Widget_Most_Visited_Posts extends WP_Widget {

	function Toplytics_WP_Widget_Most_Visited_Posts() {
		$widget_ops = array('classname' => 'widget_most_visited_posts', 'description' => __( "Toplytics - The most visited posts on your site from Google Analytics") );
		$this->WP_Widget('most-visited-posts', __('Most Visited Posts'), $widget_ops);
		$this->alt_option_name = 'widget_most_visited_posts';
	}


	function widget($args, $instance) {
		
		require_once 'toplytics.class.php';

		ob_start();
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Most visited posts') : $instance['title'], $instance, $this->id_base);
		
		//$title = '';
	  	$number = $instance['number'];
	  	$counter= $number;
		
		$type = $instance['type'];
		if (!in_array($type,array('today','week','month'))) $type = 'today';
	  
		// Get the info from transient
		$results = get_transient('gapi.cache');
	  /*
		if ( $results==null )
			die("The Google Analytics settings are wrong!");
						
		// If the transient is empty then scan the visited posts from Google Analytics Account
		if ( !($results) ) {
			toplytics_do_this_hourly();
		}
		
	  $toplytics_templates = toplytics_get_templates_list();
	  foreach($toplytics_templates as $template) {
		echo $template."<br />";
		echo toplytics_get_template_name($template)."<br />";
	  }
	  $toplytics_templates = toplytics_get_templates();
	  foreach($toplytics_templates as $template) {
		echo $template['template_slug']."<br />";
		echo $template['template_name']."<br />";
		echo $template['template_filename']."<br /><br />";
		}*/
		?>

<?php if (!empty($results[$type])) { ?>

<?php echo $before_widget; ?>

<?php include toplytics_get_template_path($instance['list_type']); ?> 

<?php echo $after_widget; ?>

	<?php	// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();
	  
} else {
	  		echo "No info found!";
} ?>
		<?php ob_get_flush();
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
		  
		$instance['type'] = $new_instance['type'];
		if (!in_array($instance['type'],array('today','week','month')))	$instance['type'] = 'today';
		$instance['list_type'] = $new_instance['list_type'];
		if (!in_array($instance['list_type'],array('default','simple'))) $instance['list_type'] = 'default';
		return $instance;
	}

	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		if ( !isset($instance['number']) || !$number = (int) $instance['number'] )
			$number = 5;
		$type = isset($instance['type']) ? $instance['type'] : 'today';
		$list_type = isset($instance['list_type']) ? $instance['list_type'] : 'default';
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
		<p><label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('Type of posts to show:'); ?></label>
		<select id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>">
			<option value="today" <?php if ($type == 'today') echo 'selected="selected"'; ?>>Today</option>
			<option value="week" <?php if ($type == 'week') echo 'selected="selected"'; ?>>Week</option>
			<option value="month" <?php if ($type == 'month') echo 'selected="selected"'; ?>>Month</option>
		</select>
		</p>
<p><label for="<?php echo $this->get_field_id('list_type'); ?>"><?php _e('Template'); ?>:</label>
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
