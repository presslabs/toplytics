<?php
class Toplytics_WP_Widget_Most_Visited_Posts extends WP_Widget {

	function Toplytics_WP_Widget_Most_Visited_Posts() {
		$widget_ops = array(
			'classname'   => 'widget_most_visited_posts', 
			'description' => __( 'Toplytics - The most visited posts on your site from Google Analytics', TOPLYTICS_TEXTDOMAIN )
		);
		$this->WP_Widget( 'most-visited-posts', __( 'Most Visited Posts', TOPLYTICS_TEXTDOMAIN ), $widget_ops );
		$this->alt_option_name = 'widget_most_visited_posts';
	}

	function widget( $args, $instance ) {
		ob_start();
		extract( $args );

		$title = apply_filters(
			'widget_title', 
			empty( $instance['title'] ) ? __( 'Most Visited Posts', TOPLYTICS_TEXTDOMAIN ) : $instance['title'], 
			$instance, 
			$this->id_base
		 );

		$period = $instance['period'];
		if ( ! in_array( $period, array( 'today', 'week', 'month' ) ) ) $period = 'month';

		$show_views = $instance['show_views'] ? 1 : 0;

		// Get the info from transient
		$results = get_transient( 'toplytics.cache' );

	  	$number = $instance['number'];
	  	$counter= $number;
		foreach ( $results[ $period ] as $post_id => $pv ) {
			if ( 0 >= $number ) break;
			$toplytics_results[ $post_id ] = $pv;
			$number--;
		}

		if ( ! empty( $results[ $period ] ) ) {
			echo $before_widget;

			$template_filename = toplytics_get_template_filename();
			if ( '' != $template_filename )
				include $template_filename;

			echo $after_widget;

			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();
		} else {
			_e( "The statistics found in GA account doesn't match with your posts/pages.", TOPLYTICS_TEXTDOMAIN );
		}
		ob_get_flush();
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
	  
		if ( ! $number = (int) $new_instance['number'] )
			$number = TOPLYTICS_DEFAULT_POSTS;
		else if ( $number < TOPLYTICS_MIN_POSTS )
			$number = TOPLYTICS_MIN_POSTS;
		else if ( $number > TOPLYTICS_MAX_POSTS )
			$number = TOPLYTICS_MAX_POSTS;

		$instance['number'] = $number;

		$instance['period'] = $new_instance['period'];
		if ( ! in_array( $instance['period'], array( 'today', 'week', 'month' ) ) )
			$instance['period'] = 'today';

		$instance['show_views'] = $new_instance['show_views'] ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		$widget_title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';

		if ( ! isset( $instance['number'] ) || ! $number = (int) $instance['number'] )
			$number = 5;

		$period     = isset( $instance['period']    ) ? $instance['period']     : 'today';
		$show_views = isset( $instance['show_views']) ? $instance['show_views'] : 0;

		$show_views_checked = '';
	    if ( isset( $instance[ 'show_views' ] ) )
			$show_views_checked = $instance['show_views'] ? ' checked="checked"' : '';
?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $widget_title; ?>" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show', TOPLYTICS_TEXTDOMAIN ); ?>:</label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'period' ); ?>"><?php _e( 'Statistics period', TOPLYTICS_TEXTDOMAIN ); ?>:</label>
		<select id="<?php echo $this->get_field_id( 'period' ); ?>" name="<?php echo $this->get_field_name('period'); ?>">
			<option value="today"<?php if ( $period == 'today' ) echo ' selected="selected"'; echo '>' . __( 'Daily', TOPLYTICS_TEXTDOMAIN ); ?></option>
			<option value="week"<?php  if ( $period == 'week'  ) echo ' selected="selected"'; echo '>' . __( 'Weekly', TOPLYTICS_TEXTDOMAIN ); ?></option>
			<option value="month"<?php if ( $period == 'month' ) echo ' selected="selected"'; echo '>' . __( 'Monthly', TOPLYTICS_TEXTDOMAIN ); ?></option>
		</select>
		</p>

		<p>
			<input class="checkbox" type="checkbox"<?php echo $show_views_checked; ?> id="<?php echo $this->get_field_id('show_views'); ?>" name="<?php echo $this->get_field_name('show_views'); ?>" /> <label for="<?php echo $this->get_field_id('show_views'); ?>"><?php echo __( 'Display post views', TOPLYTICS_TEXTDOMAIN ); ?>?</label>
		</p>

		<p>
			<?php _e( 'Template' ); ?>:<br /><?php echo toplytics_get_template_filename(); ?>
		</p>
<?php
	}
}

