<?php
/**
 * Plugin Name: Toplytics
 * Plugin URI: http://wordpress.org/extend/plugins/toplytics/
 * Description: Plugin for displaying most viewed content using data from a Google Analytics account. Relieves the DB from writing every click.
 * Author: PressLabs
 * Version: 2.0
 * Author URI: http://www.presslabs.com/
 */
define( 'TOPLYTICS_DEBUG_MODE', true );
define( 'TOPLYTICS_DEFAULT_POSTS', 5 );
define( 'TOPLYTICS_MIN_POSTS', 1 );
define( 'TOPLYTICS_MAX_POSTS', 20 );
define( 'TOPLYTICS_GET_MAX_RESULTS', 1000 );
define( 'TOPLYTICS_ADD_PAGEVIEWS', true );
define( 'TOPLYTICS_TEXTDOMAIN', 'toplytics-text-domain' );
define( 'TOPLYTICS_TEMPLATE_FILENAME', 'toplytics-template.php' );
define( 'TOPLYTICS_REALTIME_TEMPLATE_FILENAME', 'toplytics-template-realtime.php' );

global $ranges, $ranges_label;

$ranges = array(
	'month'  => date( 'Y-m-d', strtotime( '-30 days'  ) ),
	'today'  => date( 'Y-m-d', strtotime( 'yesterday' ) ),
	'2weeks' => date( 'Y-m-d', strtotime( '-14 days'  ) ),
	'week'   => date( 'Y-m-d', strtotime( '-7 days'   ) ),
);

$ranges_label = array(
	'month'  => 'Monthly',
	'today'  => 'Daily',
	'2weeks' => '2 Weeks',
	'week'   => 'Weekly',
);

require_once 'toplytics-admin.php';      // interface
require_once 'toplytics-widget.php';     // Widget code integration
require_once 'class-toplytics-auth.php'; // the main class
$obj = new Toplytics_Auth();

function toplytics_log( $message ) {
	if ( defined( TOPLYTICS_DEBUG_MODE  )  ) {
		error_log( $message );
	}
}

function toplytics_needs_configuration_message() {
	$plugin_page = plugin_basename( __FILE__ );
	$plugin_link = toplytics_return_settings_link();

	if ( toplytics_needs_configuration() ) {
		add_action(
			'admin_notices',
			create_function(
				'',
				"echo '<div class=\"error\"><p>"
				. sprintf(
					__( 'Toplytics needs configuration information on its <a href="%s">Settings</a> page.', TOPLYTICS_TEXTDOMAIN ),
					admin_url( 'options-general.php?page=' . $plugin_page )
				)
				. "</p></div>';"
			)
		);
	}
}

/**
 *  Add settings link on plugin page
 */
function toplytics_settings_link( $links ) {
	$settings_link = '<a href="' . toplytics_return_settings_link() . '">' . __( 'Settings' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'toplytics_settings_link' );

function toplytics_return_settings_link() {
	$plugin_page = plugin_basename( __FILE__ );
	return admin_url( 'options-general.php?page=' . $plugin_page );
}

/**
 *  Dashboard integration (Settings)
 */
function toplytics_menu() {
	add_options_page( 'Toplytics Options Page', 'Toplytics', 'manage_options', __FILE__, 'toplytics_options_page' );
}
add_action( 'admin_menu', 'toplytics_menu' );

function toplytics_activate() {
	add_option( 'toplytics_options', array( null ) );
	add_option( 'toplytics_services', 'analytics' );
}
register_activation_hook( __FILE__, 'toplytics_activate' );

function toplytics_deactivate() {
	wp_clear_scheduled_hook( 'toplytics_hourly_event' );
}
register_deactivation_hook( __FILE__, 'toplytics_deactivate' );

function toplytics_uninstall() {
	toplytics_remove_options();
}
add_action( 'uninstall_' . plugin_basename( __FILE__ ), 'toplytics_uninstall' );

function toplytics_init() {
	load_plugin_textdomain( TOPLYTICS_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'toplytics_init' );

function toplytics_get_admin_url( $path = '' ) {
	global $wp_version;

	if ( version_compare( $wp_version, '3.0', '>=' ) ) {
		return get_admin_url( null, $path );
	} else {
		return get_bloginfo( 'wpurl' ) . '/wp-admin' . $path;
	}
}

/**
 *  Return the string between 'start' and 'end' from 'conent'
 */
function toplytics_str_between( $start, $end, $content ) {
	$r = explode( $start, $content );

	if ( isset( $r[1] ) ) {
		$r = explode( $end, $r[1] );
		return $r[0];
	}
	return '';
}

/**
 *  Return the template filename and path. First is searched in the theme directory and then in the plugin directory
 */
function toplytics_get_template_filename( $realtime = 0 ) {
	$toplytics_template_filename = TOPLYTICS_TEMPLATE_FILENAME;
	if ( 1 == $realtime ) {
		$toplytics_template_filename = TOPLYTICS_REALTIME_TEMPLATE_FILENAME;
	}

	$theme_template = get_stylesheet_directory() . "/$toplytics_template_filename";
	if ( file_exists( $theme_template ) ) {
		return $theme_template;
	}

	$plugin_template = plugin_dir_path( __FILE__ ) . $toplytics_template_filename;
	if ( file_exists( $plugin_template ) ) {
		return $plugin_template;
	}

	return '';
}

function toplytics_needs_configuration() {
	$toplytics_oauth_token = get_option( 'toplytics_oauth_token', '' );
	return empty( $toplytics_oauth_token );
}

function toplytics_has_configuration() {
	return ! toplytics_needs_configuration();
}

/**
 *  Add cron job if all options are set
 *  Scan Google Analytics statistics every hour
 */
if ( toplytics_has_configuration() ) {
	if ( ! wp_next_scheduled( 'toplytics_hourly_event' ) ) {
		wp_schedule_event( time(), 'hourly', 'toplytics_hourly_event' );
	}
} else {
	wp_clear_scheduled_hook( 'toplytics_hourly_event' );
}

function toplytics_do_this_hourly() {
	Toplytics_Auth::ga_statistics(); // get GA statistics
	toplytics_save_stats_in_json(); // save GA statistics in JSON file
}
add_action( 'toplytics_hourly_event', 'toplytics_do_this_hourly' );

function toplytics_remove_credentials() {
	delete_option( 'toplytics_oauth_token' );
	delete_option( 'toplytics_oauth_secret' );
	delete_option( 'toplytics_auth_token' );
	delete_option( 'toplytics_account_id' );
	delete_option( 'toplytics_cache_timeout' );
}

function toplytics_remove_options() {
	delete_option( 'toplytics_options' );
	delete_option( 'toplytics_services' );
	delete_transient( 'toplytics.cache' );
}

function toplytics_widgets_init() {
	if ( toplytics_has_configuration() ) {
		register_widget( 'Toplytics_WP_Widget_Most_Visited_Posts' );
	}
}
add_action( 'widgets_init', 'toplytics_widgets_init' );

function toplytics_admin_init(){
	toplytics_needs_configuration_message();
	register_setting( 'toplytics_options', 'toplytics_options', 'toplytics_options_validate' );
}
add_action( 'admin_init', 'toplytics_admin_init' );

function toplytics_enqueue_script() {
	wp_enqueue_script( 'toplytics', plugins_url( 'js/toplytics.js' , __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'toplytics_enqueue_script' );

function toplytics_get_results( $args = '' ) {
	$args = toplytics_validate_args( $args );

	$results = get_transient( 'toplytics.cache' );
	if ( ! isset( $results[ $args['period'] ] ) ) {
		return false;
	}

	$counter = 1;
	foreach ( $results[ $args['period'] ] as $index => $value ) {
		if ( $counter > $args['numberposts'] ) { break; }
		$toplytics_new_results[ $index ] = $value;
		$counter++;
	}
	return $toplytics_new_results;
}

function toplytics_results( $args = '' ) {
	$args    = toplytics_validate_args( $args );
	$results = toplytics_get_results( $args );
	if ( ! $results ) { return ''; }

	$out = '<ol>';
	$k   = 0;
	foreach ( $results as $post_id => $post_views ) {
		$out .= '<li><a href="' . get_permalink( $post_id )
			. '" title="' . esc_attr( get_the_title( $post_id ) ) . '">'
			. get_the_title( $post_id ) . '</a>';

		if ( $args['showviews'] ) {
			$out .= '<span class="post-views">'
				. sprintf( __( '%d Views', TOPLYTICS_TEXTDOMAIN ), $post_views )
				. '</span>';
		}
		$out .= '</li>';
	}
	$out .= '</ol>';

	return $out;
}
add_shortcode( 'toplytics', 'toplytics_results' );

function toplytics_save_stats_in_json() {
	$filename = 'toplytics.json';
	$filepath = dirname( __FILE__ ) . "/$filename";
	$toplytics_results = get_transient( 'toplytics.cache' );
	if ( false != $toplytics_results ) {
		// post data: id, permalink, title, views
		$post_data = '';
		foreach ( $toplytics_results as $period => $result ) {
			if ( '_ts' != $period ) {
				foreach ( $result as $post_id => $views ) {
					$data['permalink'] = get_permalink( $post_id );
					$data['title']     = get_the_title( $post_id );
					$data['post_id']   = $post_id;
					$data['views']     = $views;

					$post_data[ $period ][] = $data;
				}
			}
		}
		file_put_contents( $filepath, json_encode( $post_data, JSON_FORCE_OBJECT ) );
	}
}
