<?php
/*  Copyright 2014-2015 Presslabs SRL <ping@presslabs.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Toplytics_WP_Widget extends WP_Widget {
	private $toplytics;

	public function Toplytics_WP_Widget() {
		$widget_ops = array(
			'classname'   => 'toplytics_widget',
			'description' => __( 'The most visited posts on your site from Google Analytics', 'toplytics' ),
		);
		$this->WP_Widget( 'toplytics-widget', 'Toplytics', $widget_ops );
		$this->alt_option_name = 'toplytics_widget';

		global $toplytics;
		$this->toplytics = $toplytics;
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

		$title = apply_filters(
			'widget_title',
			$title,
			$instance,
			$this->id_base
		);

		$stats_periods = array_keys( $this->toplytics->ranges );
		if ( ! in_array( $period, $stats_periods ) ) {
			$period = $stats_periods[0];
		}
		$toplytics_results = $this->toplytics->get_result( $period );
		$toplytics_results = array_slice( $toplytics_results, 0, $numberposts, true );

		// variables for backward compatibilty
		$widget_period      = $period;
		$widget_numberposts = $numberposts;
		$widget_showviews   = $showviews;

		echo $before_widget;
		$template_filename = $this->toplytics->get_template_filename();

		if ( '' != $template_filename ) {
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}
			$this->realtime_js_script( $period, $numberposts, $showviews, $widget_id );
			echo "<div id='$widget_id'></div>";
			include $template_filename;
			echo $after_widget;
		}
		ob_get_flush();
	}

	function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		if ( ! $widget_numberposts = (int) $new_instance['numberposts'] ) {
			$widget_numberposts = Toplytics::DEFAULT_POSTS;
		} else if ( $widget_numberposts < Toplytics::MIN_POSTS ) {
			$widget_numberposts = Toplytics::MIN_POSTS;
		} else if ( $widget_numberposts > Toplytics::MAX_POSTS ) {
			$widget_numberposts = Toplytics::MAX_POSTS;
		}

		$instance['numberposts'] = $widget_numberposts;

		$instance['period'] = $new_instance['period'];
		$stats_periods = array_keys( $this->toplytics->ranges );
		if ( ! in_array( $instance['period'], $stats_periods ) ) {
			$instance['period'] = $stats_periods[0];
		}

		$instance['showviews'] = isset( $new_instance['showviews'] ) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		$widget_title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';

		if ( ! isset( $instance['numberposts'] ) || ! $widget_numberposts = (int) $instance['numberposts'] ) {
			$widget_numberposts = Toplytics::DEFAULT_POSTS;
		}

		$stats_periods = array_keys( $this->toplytics->ranges );
		$period = isset( $instance['period'] ) ? $instance['period'] : $stats_periods[0];

		$showviews_checked = '';
		if ( isset( $instance['showviews'] ) ) {
			$showviews_checked = $instance['showviews'] ? ' checked="checked"' : '';
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $widget_title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'numberposts' ); ?>"><?php _e( 'Number of posts to show', 'toplytics' ); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'numberposts' ); ?>" name="<?php echo $this->get_field_name( 'numberposts' ); ?>" type="text" value="<?php echo $widget_numberposts; ?>" size="3" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'period' ); ?>"><?php _e( 'Statistics period', 'toplytics' ); ?>:</label>
			<select id="<?php echo $this->get_field_id( 'period' ); ?>" name="<?php echo $this->get_field_name( 'period' ); ?>">
		<?php
		foreach ( array_keys( $this->toplytics->ranges ) as $key ) {
			?>
			<option value="<?php echo $key; ?>"<?php if ( $period == $key ) { echo ' selected="selected"'; } echo '>' . __( $key, 'toplytics' ); ?></option>
			<?php } ?>
			</select>
		</p>

		<p>
			<input class="checkbox" type="checkbox"<?php echo $showviews_checked; ?> id="<?php echo $this->get_field_id( 'showviews' ); ?>" name="<?php echo $this->get_field_name( 'showviews' ); ?>" /> <label for="<?php echo $this->get_field_id( 'showviews' ); ?>"><?php echo __( 'Display post views', 'toplytics' ); ?>?</label>
		</p>

		<p><?php _e( 'Template' ); ?>:<br /><?php echo $this->toplytics->get_template_filename(); ?></p>
		<?php
	}
}

function toplytics_widget() {
	register_widget( 'Toplytics_WP_Widget' );
}
add_action( 'widgets_init', 'toplytics_widget' );
