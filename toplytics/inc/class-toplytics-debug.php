<?php
/*  Copyright 2014 PressLabs SRL <ping@presslabs.com>

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

class Toplytics_Debug {
	private $toplytics;

	public function __construct() {
		global $toplytics;
		$this->toplytics = $toplytics;

		date_default_timezone_set( get_option( 'timezone_string' ) );

		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_menu', array( $this, 'hide_menu' ) );
			add_action( 'admin_head', array( $this, 'js_print' ) );
			add_filter( 'plugin_action_links_' . $this->toplytics->plugin_basename() , array( $this, 'add_debug_link' ) );
		}
		add_filter( 'toplytics_analytics_data', array( $this, 'get_analytics_data' ), 10, 4 );
		add_filter( 'toplytics_analytics_data_result', array( $this, 'get_analytics_data_result' ), 10, 2 );
		add_filter( 'toplytics_convert_data_to_posts', array( $this, 'convert_data_to_posts' ), 10, 2 );
		add_filter( 'toplytics_convert_data_url', array( $this, 'convert_data_url' ), 10, 5 );
	}

	public function get_analytics_data( $when, $ga_api_request, $query, $profile_id ) {
		$analytics_data = get_option( 'toplytics_analytics_data' );
		$analytics_data[ $when ]['ga_api_request'] = $ga_api_request;
		update_option( 'toplytics_analytics_data', $analytics_data );
	}

	public function get_analytics_data_result( $ga_api_result, $when ) {
		$analytics_data = get_option( 'toplytics_analytics_data' );
		$analytics_data[ $when ]['ga_api_result'] = $ga_api_result;
		update_option( 'toplytics_analytics_data', $analytics_data );
	}

	public function convert_data_to_posts( $new_data, $old_data ) {
		$debug_data = get_option( 'toplytics_debug_data' );
		foreach ( $debug_data as $when => $post ) :
			foreach ( $post as $post_id => $info ) :
				if ( ! empty( $new_data[ $when ][ $post_id ] ) ) {
					$debug_data[ $when ][ $post_id ]['pageviews'] = $new_data[ $when ][ $post_id ];
				} else {
					$debug_data[ $when  ][ $post_id  ]['pageviews'] = 0;
				}
				$debug_data[ $when ][ $post_id ]['permalink'] = get_permalink( $post_id );
			endforeach;
		endforeach;
		update_option( 'toplytics_debug_data', $debug_data );
		update_option( 'toplytics_data', $new_data );
		return $new_data;
	}

	public function convert_data_url( $url, $when, $post_id, $rel_path, $pageviews ) {
		$data = get_option( 'toplytics_debug_data' );
		if ( ! empty( $data[ $when ][ $post_id ]['ga_data'] ) ) {
			$info = array_merge( $data[ $when ][ $post_id ]['ga_data'], array( $rel_path => $pageviews ) );
		} else {
			$info = array( $rel_path => $pageviews );
		}
		$data[ $when ][ $post_id ]['ga_data'] = $info;
		update_option( 'toplytics_debug_data', $data );
		return $url;
	}

	public function js_print() {
		?>
		<script>
			function printDiv(divName) {
				var printContents = document.getElementById(divName).innerHTML;
				var originalContents = document.body.innerHTML;
				document.body.innerHTML = printContents;
				window.print();
				document.body.innerHTML = originalContents;
			}
		</script>
		<?php
	}

	public function add_menu() {
		add_options_page( 'Toplytics Debug', 'Toplytics Debug', 'manage_options', $this->get_debug_slug(), array( $this, 'page' ), 6 );
	}

	public function hide_menu() {
		remove_submenu_page( 'options-general.php', $this->get_debug_slug() );
	}

	public function get_debug_slug() {
		return 'toplytics-debug.php';
	}

	public function get_debug_link() {
		return admin_url( 'options-general.php?page=' . $this->get_debug_slug() );
	}

	public function add_debug_link( $links ) {
		array_unshift( $links, '<a href="' . $this->get_debug_link() . '">' . __( 'Debug'  ) . '</a>' );
		return $links;
	}

	public function page() {
		?>
		<div class="wrap">
			<h2>Toplytics Debug</h2>

			<?php
			$debug_data        = get_option( 'toplytics_debug_data' );
			$analytics_data    = get_option( 'toplytics_analytics_data' );
			$toplytics_results = get_option( 'toplytics_results' );
			?>
			<p><strong>Connected to:</strong> <?php echo $this->toplytics->get_profile_info(); ?></p>
			<p><strong>Data collected at:</strong> <?php if ( ! empty( $toplytics_results['_ts'] ) ) { echo date( 'd-m-Y h:i:s', $toplytics_results['_ts'] ); } else { echo 'NaN'; } ?></p>

			<input type='button' value='Print This Result' onclick='printDiv("wpbody");'/>
			<hr>
		<?php
		foreach ( $this->toplytics->ranges as $when => $date ) :
			?>
			<h3>GA API data[<?php echo $when; ?>]:</h3>
			<textarea cols="70" rows="5" readonly="readonly"><?php echo $analytics_data[ $when ]['ga_api_request']; ?></textarea><hr>
			<p><?php echo count( $analytics_data[ $when ]['ga_api_result'] ); ?> results</p>
			<pre><?php print_r( $analytics_data[ $when ]['ga_api_result'] ); ?></pre><hr>

			<h3>Toplytics debug data[<?php echo $when; ?>]:</h3>
			<?php if ( ! empty( $debug_data[ $when ] ) ) : ?>
			<p><?php echo count( $debug_data[ $when ] ); ?> results</p>
			<pre><?php print_r( $debug_data[ $when ] ); ?></pre><hr>
			<?php
			endif;
		endforeach;
		?></div><?php
	}

}

if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	function toplytics_debug_page() {
		new Toplytics_Debug();
	}
	add_action( 'init', 'toplytics_debug_page' );
}
