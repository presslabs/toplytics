<?php
class Toplytics_WP_Widget_Most_Visited_Posts extends WP_Widget {
	
	private $stats_periods;

	function Toplytics_WP_Widget_Most_Visited_Posts() {
		$widget_ops = array(
			'classname'   => 'toplytics_widget', 
			'description' => __( 'The most visited posts on your site from Google Analytics', TOPLYTICS_TEXTDOMAIN )
		);
		$this->WP_Widget( 'toplytics-widget', __( 'Toplytics', TOPLYTICS_TEXTDOMAIN ), $widget_ops );
		$this->alt_option_name = 'toplytics_widget';
		
		global $ranges;
		foreach ( $ranges as $key => $value )
			$this->stats_periods[] = $key;
		
	}

	function widget( $args, $instance ) {
		ob_start();
		extract( $args );

		// Get the info from transient
		$results = get_transient( 'toplytics.cache' );

		if ( $results ) {
			$title = apply_filters(
				'widget_title', 
				empty( $instance['title'] ) ? __( 'Most Visited Posts', TOPLYTICS_TEXTDOMAIN ) : $instance['title'], 
				$instance, 
				$this->id_base
			 );

			$widget_period = $instance['period'];
			if ( ! in_array( $widget_period, $this->stats_periods ) ) $widget_period = $this->stats_periods[0];

			$widget_showviews   = $instance['showviews'] ? 1 : 0;
		  	$widget_numberposts = $instance['numberposts'];

			echo $before_widget;

			$template_filename = toplytics_get_template_filename();
			if ( '' != $template_filename ) {
				if ( $title )
					echo $before_title . $title . $after_title;

				include $template_filename;
			}

			echo $after_widget;
		}
		ob_get_flush();
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
	  
		if ( ! $widget_numberposts = (int) $new_instance['numberposts'] )
			$widget_numberposts = TOPLYTICS_DEFAULT_POSTS;
		else if ( $widget_numberposts < TOPLYTICS_MIN_POSTS )
			$widget_numberposts = TOPLYTICS_MIN_POSTS;
		else if ( $widget_numberposts > TOPLYTICS_MAX_POSTS )
			$widget_numberposts = TOPLYTICS_MAX_POSTS;

		$instance['numberposts'] = $widget_numberposts;

		$instance['period'] = $new_instance['period'];
		if ( ! in_array( $instance['period'], $this->stats_periods ) )
			$instance['period'] = $this->stats_periods[0];

		$instance['showviews'] = $new_instance['showviews'] ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		$widget_title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';

		if ( ! isset( $instance['numberposts'] ) || ! $widget_numberposts = (int) $instance['numberposts'] )
			$widget_numberposts = TOPLYTICS_DEFAULT_POSTS;

		$period     = isset( $instance['period']    ) ? $instance['period']     : $this->stats_periods[0];
		$showviews = isset( $instance['showviews']) ? $instance['showviews'] : 0;

		$showviews_checked = '';
	    if ( isset( $instance[ 'showviews' ] ) )
			$showviews_checked = $instance['showviews'] ? ' checked="checked"' : '';
?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $widget_title; ?>" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'numberposts' ); ?>"><?php _e( 'Number of posts to show', TOPLYTICS_TEXTDOMAIN ); ?>:</label>
		<input id="<?php echo $this->get_field_id( 'numberposts' ); ?>" name="<?php echo $this->get_field_name( 'numberposts' ); ?>" type="text" value="<?php echo $widget_numberposts; ?>" size="3" />
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
			<input class="checkbox" type="checkbox"<?php echo $showviews_checked; ?> id="<?php echo $this->get_field_id('showviews'); ?>" name="<?php echo $this->get_field_name('showviews'); ?>" /> <label for="<?php echo $this->get_field_id('showviews'); ?>"><?php echo __( 'Display post views', TOPLYTICS_TEXTDOMAIN ); ?>?</label>
		</p>

		<p>
			<?php _e( 'Template' ); ?>:<br /><?php echo toplytics_get_template_filename(); ?>
		</p>
<?php
	}
}

