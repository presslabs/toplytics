<?php
class Toplytics_WP_Widget_Most_Visited_Posts extends WP_Widget {
	private $stats_periods;

	function Toplytics_WP_Widget_Most_Visited_Posts() {
		$widget_ops = array(
			'classname'   => 'toplytics_widget',
			'description' => __( 'The most visited posts on your site from Google Analytics', TOPLYTICS_TEXTDOMAIN ),
		);
		$this->WP_Widget( 'toplytics-widget', __( 'Toplytics', TOPLYTICS_TEXTDOMAIN ), $widget_ops );
		$this->alt_option_name = 'toplytics_widget';

		global $ranges;
		$this->stats_periods = array_keys( $ranges );
	}

	private function realtime_js_script( $period, $numberposts, $showviews, $widget_id ) {
		?>
			<script type="text/javascript">
				toplytics_args = {
					period       : '<?php print $period; ?>',
					numberposts  : <?php print $numberposts; ?>,
					showviews    : <?php print $showviews; ?>,
					widget_id    : '<?php print $widget_id; ?>'
				}
			</script>
		<?php
	}

	function widget( $args, $instance ) {
		ob_start();

		extract( $args );
		extract( $instance );

		// Get the info from transient
		$results = get_transient( 'toplytics.cache' );

		if ( $results ) {
			$title = apply_filters(
				'widget_title',
				empty( $title ) ? __( 'Most Visited Posts', TOPLYTICS_TEXTDOMAIN ) : $title,
				$instance,
				$this->id_base
			);

			if ( ! in_array( $period, $this->stats_periods ) ) {
				$period = $this->stats_periods[0];
			}

			$showviews = $showviews ? 1 : 0;
			$realtime  = $realtime ? 1 : 0; // real time update

			echo $before_widget;
			$template_filename = toplytics_get_template_filename();

			if ( '' != $template_filename ) {
				if ( $title ) {
					echo $before_title . $title . $after_title;
				}

				if ( $realtime ) {
					$this->realtime_js_script( $period, $numberposts, $showviews, $widget_id );
					echo "<div id='$widget_id'></div>";
				} else {
					include $template_filename;
				}
			}
			echo $after_widget;
			if ( $realtime ) {
				include toplytics_get_template_filename( $realtime );
			}
		}
		ob_get_flush();
		}

	function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		if ( ! $widget_numberposts = (int) $new_instance['numberposts'] ) {
			$widget_numberposts = TOPLYTICS_DEFAULT_POSTS;
		} else if ( $widget_numberposts < TOPLYTICS_MIN_POSTS ) {
			$widget_numberposts = TOPLYTICS_MIN_POSTS;
		} else if ( $widget_numberposts > TOPLYTICS_MAX_POSTS ) {
			$widget_numberposts = TOPLYTICS_MAX_POSTS;
		}

		$instance['numberposts'] = $widget_numberposts;

		$instance['period'] = $new_instance['period'];
		if ( ! in_array( $instance['period'], $this->stats_periods ) ) {
			$instance['period'] = $this->stats_periods[0];
		}

		$instance['showviews'] = $new_instance['showviews'] ? 1 : 0;
		$instance['realtime']  = $new_instance['realtime'] ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		$widget_title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';

		if ( ! isset( $instance['numberposts'] ) || ! $widget_numberposts = (int) $instance['numberposts'] ) {
			$widget_numberposts = TOPLYTICS_DEFAULT_POSTS;
		}

		$period = isset( $instance['period'] ) ? $instance['period'] : $this->stats_periods[0];

		$showviews_checked = '';
		if ( isset( $instance['showviews'] ) ) {
			$showviews_checked = $instance['showviews'] ? ' checked="checked"' : '';
		}

		$realtime = isset( $instance['realtime']) ? $instance['realtime'] : 0;

		$realtime_checked = '';
		if ( isset( $instance['realtime'] ) ) {
			$realtime_checked = $instance['realtime'] ? ' checked="checked"' : '';
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $widget_title; ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'numberposts' ); ?>"><?php _e( 'Number of posts to show', TOPLYTICS_TEXTDOMAIN ); ?>:</label>
				<input id="<?php echo $this->get_field_id( 'numberposts' ); ?>" name="<?php echo $this->get_field_name( 'numberposts' ); ?>" type="text" value="<?php echo $widget_numberposts; ?>" size="3" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'period' ); ?>"><?php _e( 'Statistics period', TOPLYTICS_TEXTDOMAIN ); ?>:</label>
				<select id="<?php echo $this->get_field_id( 'period' ); ?>" name="<?php echo $this->get_field_name( 'period' ); ?>">
			<?php
			global $ranges, $ranges_label;
			$ranges_keys = array_keys( $ranges );
			foreach ( $ranges_keys as $key ) {
				?>
				<option value="<?php echo $key; ?>"<?php if ( $period == $key ) { echo ' selected="selected"'; } echo '>' . __( $ranges_label[ $key ], TOPLYTICS_TEXTDOMAIN ); ?></option>
				<?php } ?>
				</select>
			</p>

			<p>
				<input class="checkbox" type="checkbox"<?php echo $showviews_checked; ?> id="<?php echo $this->get_field_id( 'showviews' ); ?>" name="<?php echo $this->get_field_name( 'showviews' ); ?>" /> <label for="<?php echo $this->get_field_id( 'showviews' ); ?>"><?php echo __( 'Display post views', TOPLYTICS_TEXTDOMAIN ); ?>?</label>
			</p>

			<p>
				<input class="checkbox" type="checkbox"<?php echo $realtime_checked; ?> id="<?php echo $this->get_field_id( 'realtime' ); ?>" name="<?php echo $this->get_field_name( 'realtime' ); ?>" /><label title="<?php echo __( 'If you choose this, the content will be generated dynamically and your SEO will be affected', TOPLYTICS_TEXTDOMAIN ); ?>" for="<?php echo $this->get_field_id( 'realtime' ); ?>"><?php echo __( 'Display posts in real time', TOPLYTICS_TEXTDOMAIN ); ?>?</label>
			</p>

			<p><?php _e( 'Template' ); ?>:<br /><?php echo toplytics_get_template_filename( $realtime ); ?></p>
			<?php
		}
	}
}
