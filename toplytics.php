<?php  
/* 
 * Plugin Name: Toplytics
 * Plugin URI: http://wordpress.org/extend/plugins/toplytics/ 
 * Description: Plugin for displaying most viewed content using data from a Google Analytics account. Relieves the DB from writing every click.
 * Author: PressLabs 
 * Version: 1.4.1
 * Author URI: http://www.presslabs.com/ 
 */

//
// Configurations
//
define( 'TOPLYTICS_DEFAULT_POSTS', 5 );
define( 'TOPLYTICS_MIN_POSTS', 1 );
define( 'TOPLYTICS_MAX_POSTS', 20 );
define( 'TOPLYTICS_TEXTDOMAIN', 'toplytics-text-domain' );
define( 'TOPLYTICS_TEMPLATE_FILENAME', 'toplytics-template.php' );

include 'toplytics-widget.php'; // Widget code integration

include 'class-toplytics-auth.php'; // the main class
$obj = new Toplytics_Auth();

include 'class-toplytics-documentation.php';

//------------------------------------------------------------------------------
function toplytics_activate() {
	add_option( 'toplytics_options', array(null) );
	add_option( 'toplytics_services', 'analytics' );
}
register_activation_hook( __FILE__, 'toplytics_activate' );

//------------------------------------------------------------------------------
function toplytics_deactivate() {
	wp_clear_scheduled_hook( 'toplytics_hourly_event' );
}
register_deactivation_hook( __FILE__, 'toplytics_deactivate' );

//------------------------------------------------------------------------------
function toplytics_uninstall() {
	toplytics_remove_all_options();
}
add_action( 'uninstall_' . plugin_basename( __FILE__ ), 'toplytics_uninstall' );

//------------------------------------------------------------------------------
function toplytics_init() {
	load_plugin_textdomain( TOPLYTICS_TEXTDOMAIN, 
		false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'toplytics_init' );

//------------------------------------------------------------------------------
function toplytics_get_admin_url( $path = '' ) {
	global $wp_version;

	if ( version_compare( $wp_version, '3.0', '>=' ) )
		return get_admin_url( null, $path );
	else
		return get_bloginfo( 'wpurl' ) . '/wp-admin' . $path;
}

//------------------------------------------------------------------------------
// Return the string between 'start' and 'end' from 'conent'.
function toplytics_str_between( $start, $end, $content ) {
	$r = explode( $start, $content );

	if ( isset( $r[1] ) ) {
		$r = explode( $end, $r[1] );
		return $r[0];
	}
	return '';
}

//------------------------------------------------------------------------------
function toplytics_get_template_filename() {
	$theme_template = get_stylesheet_directory() . '/' . TOPLYTICS_TEMPLATE_FILENAME;
	if ( file_exists( $theme_template ) )
		return $theme_template;

	$plugin_template = plugin_dir_path( __FILE__ ) . TOPLYTICS_TEMPLATE_FILENAME;
	if ( file_exists( $plugin_template ) )
		return $plugin_template;

	return '';
}

//------------------------------------------------------------------------------
function toplytics_needs_configuration() {
	$toplytics_oauth_token = get_option( 'toplytics_oauth_token', '' );

	return empty( $toplytics_oauth_token );
}

//------------------------------------------------------------------------------
function toplytics_has_configuration() {
	return ( ! toplytics_needs_configuration() );
}

//------------------------------------------------------------------------------
// Add cron job if all options are set
// Scan Google Analytics statistics every hour
//
if ( toplytics_has_configuration() ) {
	if ( ! wp_next_scheduled( 'toplytics_hourly_event' ) )
		wp_schedule_event( time(), 'hourly', 'toplytics_hourly_event' );
} else {
	wp_clear_scheduled_hook( 'toplytics_hourly_event' );
}

//------------------------------------------------------------------------------
function toplytics_do_this_hourly() {
	// delete_transient( 'toplytics.cache' );
	Toplytics_Auth::ga_statistics();
}
add_action( 'toplytics_hourly_event', 'toplytics_do_this_hourly' );

//------------------------------------------------------------------------------
function toplytics_remove_credentials() {
	delete_option( 'toplytics_services' );
	delete_option( 'toplytics_oauth_token' );
	delete_option( 'toplytics_oauth_secret' );
	delete_option( 'toplytics_auth_token' );
	delete_option( 'toplytics_account_id' );
	delete_option( 'toplytics_cache_timeout' );
}

//------------------------------------------------------------------------------
function toplytics_remove_all_options() {
	delete_option( 'toplytics_options' );
	toplytics_remove_credentials();
	delete_transient( 'toplytics.cache' );
}

//------------------------------------------------------------------------------
function toplytics_widgets_init() {
	if ( toplytics_has_configuration() )
		register_widget( 'Toplytics_WP_Widget_Most_Visited_Posts' );
} 
add_action( 'widgets_init', 'toplytics_widgets_init' );

//------------------------------------------------------------------------------
// Add settings link on plugin page.
function toplytics_settings_link( $links ) {
	$settings_link = '<a href="' . toplytics_return_settings_link() . '">' . __('Settings') . '</a>';
	array_unshift( $links, $settings_link );

	return $links; 
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'toplytics_settings_link' );

//------------------------------------------------------------------------------
function toplytics_return_settings_link() {
	$plugin_page = plugin_basename( __FILE__ );

	return admin_url( 'tools.php?page=' . $plugin_page );
}

//------------------------------------------------------------------------------
// Displays all messages registered to 'your-settings-error-slug'
function toplytics_admin_notices_action() {
	settings_errors();
}
add_action( 'admin_notices', 'toplytics_admin_notices_action' );

//------------------------------------------------------------------------------
// Dashboard integration (Tools)
function toplytics_menu() {
	add_management_page( 'Toplytics Options Page', 'Toplytics', 
		'manage_options', __FILE__, 'toplytics_options_page' );
}
add_action( 'admin_menu', 'toplytics_menu' );

//------------------------------------------------------------------------------
function toplytics_options_page() {
	if ( ! current_user_can( 'manage_options' ) )
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	$info_message = '';
	$error_message = '';

    if ( isset( $_POST['SubmitOptions'] ) ) {
		delete_option( 'toplytics_account_id' );
		add_option( 'toplytics_account_id', $_POST['ga_account_id'] );
		$info_message = __( 'Options Saved', TOPLYTICS_TEXTDOMAIN );
	}

    if ( isset( $_POST['SubmitRemoveCredentials'] ) ) {
    		toplytics_remove_credentials();
		$info_message = __( 'Everything Reset', TOPLYTICS_TEXTDOMAIN );
	}

	if ( isset( $_POST['ga_cache_timeout'] ) ) {
		delete_option( 'toplytics_cache_timeout' );
		if ( '' != $_POST['ga_cache_timeout'] )
			add_option( 'toplytics_cache_timeout', $_POST['ga_cache_timeout'] );
	}

?>
<div class="wrap">
<div id="icon-tools" class="icon32">&nbsp;</div>
<h2><?php _e( 'Settings' ); ?></h2>

<?php
	// if settings are not empty then run the function called every hour (scan the GA statistics)
	// this case is useful when you change the GA account settings
	if ( toplytics_has_configuration() ) {
		toplytics_do_this_hourly();

		$base_url         = 'https://www.googleapis.com/analytics/v2.4/';
		$account_base_url = 'https://www.googleapis.com/analytics/v2.4/management/';
		$auth_type        = 'oauth';
		$auth             = NULL;
		$oauth_token      = get_option( 'toplytics_oauth_token' );
		$oauth_secret     = get_option( 'toplytics_oauth_secret' );
		$ids              = '';
		$cache_timeout    = false !== get_option( 'toplytics_cache_timeout' ) ? get_option( 'toplytics_cache_timeout' ) : 60;
		$error_message    = '';
//----------
		$url = $account_base_url . 'accounts/~all/webproperties/~all/profiles';
		$request_type = 'GET';
		if ( NULL == $url ) error_log( 'No URL to sign.' );

		$signature_method = new GADOAuthSignatureMethod_HMAC_SHA1();

		$params    = array();
		$consumer  = new GADOAuthConsumer( 'anonymous', 'anonymous', NULL );
		$token     = new GADOAuthConsumer( $oauth_token, $oauth_secret );
		$oauth_req = GADOAuthRequest::from_consumer_and_token( $consumer, $token, $request_type, $url, $params );

		$oauth_req->sign_request( $signature_method, $consumer, $token );
		$account_hash_args = array( $oauth_req->to_header() );

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $account_base_url . 'accounts/~all/webproperties/~all/profiles' );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $account_hash_args );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		$return = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			$error_message = curl_error( $ch );
			$account_hash = false;
		}

		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if($http_code != 200) {
			$error_message = $return;
			$account_hash = false;
		} else {
			$error_message = '';
			$xml = new SimpleXMLElement( $return );

			curl_close( $ch );

			$vhash = array();
			foreach ( $xml->entry as $entry ) {
				$value = (string) $entry->id;
				list( $part1, $part2 ) = split( 'profiles/', $value );
				$vhash['ga:' . $part2] = (string) $entry->title;
			}
			$account_hash = $vhash;
		}
//----------
	if ( 200 != $http_code ) {
		if ( 401 == $http_code ) {
			delete_option( 'toplytics_auth_token' ); // this is removed so login will happen again
			toplytics_options_page();
			return;
		} else {
			$error_message = __( 'Error gathering analytics data from Google:', TOPLYTICS_TEXTDOMAIN )
				. strip_tags( $error_message );
				_e( 'Please try again later.', TOPLYTICS_TEXTDOMAIN );
			return;
		}
	}

	if ( isset( $info_message ) && '' != trim( $info_message ) )
		echo '<div id="message" class="updated fade"><p><strong>' . $info_message . '</strong></p></div>';

	if ( isset( $error_message ) && '' != trim( $error_message ) ) 
		echo '<div id="message" class="error fade"><p><strong>' . $error_message . '</strong></p></div>';

	if ( 0 != sizeof( $account_hash ) ) {
		$current_account_id = isset( $_POST['ga_account_id'] ) ? $_POST['ga_account_id'] : 
			false !== get_option( 'toplytics_account_id' ) ? get_option( 'toplytics_account_id' ) : '' ;

		if ( ! isset( $current_account_id ) || '' == $current_account_id ) {
?>
		<div class="updated">
			<p><?php _e('<b>Note:</b> You will need to select an account and <b>click "Save Changes"</b> before the analytics dashboard will work.',
TOPLYTICS_TEXTDOMAIN); ?></p>
		</div>
<?php
		}	
	}
?>
      <form action="" method="post">

        <table class="form-table">
          <tr valign="top">
            <th scope="row"><label for="ga_account_id"><?php _e( 'Available Accounts', TOPLYTICS_TEXTDOMAIN ); ?></label></th>
            <td>
              <?php
                  if ( 0 == sizeof( $account_hash ) )
                  {
                    echo '<span id="ga_account_id">' . __( 'No accounts available.', TOPLYTICS_TEXTDOMAIN ) . '</span>';
                  }
                  else
                  {
                    echo '<select id="ga_account_id" name="ga_account_id">';
                    foreach ( $account_hash as $account_id => $account_name )
                    {
                      echo '<option value="' . $account_id . '" ' . ( $current_account_id == $account_id ? 'selected' : '' ) . '>' . $account_name . '</option>';
                    }
                    echo '</select>';
                  }
              ?>
            </td>
          </tr>

        </table>

        <p class="submit">
          <input type="submit" name="SubmitOptions" class="button-primary" value="<?php _e( 'Save Changes', TOPLYTICS_TEXTDOMAIN ); ?>" />&nbsp;&nbsp;
          <input type="submit" name="SubmitRemoveCredentials" class="button" value="<?php _e( 'Remove Credentials', TOPLYTICS_TEXTDOMAIN ); ?>" />
        </p>

      </form>
<?php
	} else {
?>
      <form action="" method="post">

        <table class="form-table">

          <tr valign="top">
            <th><?php _e( "Please configure your Google Analytics Account to be used for this site:<br /><br />Login using Google's OAuth system.", TOPLYTICS_TEXTDOMAIN ); ?></th>
          </tr>

          <tr valign="top">
            <th><?php _e( "This is the prefered method of attaching your Google account.<br/>
                Clicking the 'Start the Login Process' button will redirect you to a login page at google.com.<br/>
                After accepting the login there you will be returned here.", TOPLYTICS_TEXTDOMAIN ); ?></th>
          </tr>

          <tr valign="top">
            <td>
				<p class="submit">
        			<input type="hidden" name="toplytics_login_type" value="oauth" />
					<input type="submit" name="SubmitLogin" class="button-primary" value="<?php _e( 'Start the Login Process', TOPLYTICS_TEXTDOMAIN ); ?>&nbsp;&raquo;" />
				</p>
			</td>
          </tr>

        </table>
      </form>

	<?php } ?>


</div>

<?php
}

//------------------------------------------------------------------------------
function toplytics_needs_configuration_message() {
	$plugin_page = plugin_basename( __FILE__ );
	$plugin_link = toplytics_return_settings_link();

	if ( toplytics_needs_configuration() )
		add_action( 'admin_notices', create_function( '', "echo '<div class=\"error\"><p>"
			. sprintf( __('Toplytics needs configuration information on its <a href="%s">Settings</a> page.', TOPLYTICS_TEXTDOMAIN ), admin_url( 'tools.php?page=' . $plugin_page ) ) . "</p></div>';" ) );
}

//------------------------------------------------------------------------------
function toplytics_admin_init(){
	toplytics_needs_configuration_message();

	register_setting( 'toplytics_options', 'toplytics_options', 'toplytics_options_validate' );
}
add_action( 'admin_init', 'toplytics_admin_init' );

//------------------------------------------------------------------------------
function toplytics_validate_args( $args ) {
	//
	// showviews (true/false - default=false)
	//
	if ( isset( $args['showviews'] ) )
		$args['showviews'] = true;
	else
		$args['showviews'] = false;

	if ( ! isset( $args['period'] ) ) // set default value
		$args['period'] = 'month';

	if ( ! in_array( $args['period'], array( 'today', 'week', 'month' ) ) )
		$args['period'] = 'month';

	if ( ! isset( $args['numberposts'] ) ) // set default value
		$args['numberposts'] = TOPLYTICS_DEFAULT_POSTS;

	if ( 0 > $args['numberposts'] )
		$args['numberposts'] = TOPLYTICS_MIN_POSTS;

	if ( TOPLYTICS_MAX_POSTS < $args['numberposts'] )
		$args['numberposts'] = TOPLYTICS_MAX_POSTS;

	return $args;
}

//------------------------------------------------------------------------------
function toplytics_get_results( $args = '' ) {
	$args = toplytics_validate_args( $args );

	$results = get_transient( 'toplytics.cache' );
	if ( ! $results[ $args['period'] ] ) return false;

	$counter = 1;
	foreach ( $results[ $args['period'] ] as $index => $value ) {
		if ( $counter > $args['numberposts'] ) break;
		$toplytics_new_results[ $index ] = $value;
		$counter++;
	}
	return $toplytics_new_results;
}

//------------------------------------------------------------------------------
function toplytics_results( $args = '' ) {
	$args = toplytics_validate_args( $args );
	$results = toplytics_get_results( $args );
	if ( ! $results ) return false;

	echo '<ol>';
	$k = 0;
	foreach ( $results as $post_id => $post_views ) {
		echo '<li><a href="' . get_permalink( $post_id ) 
			. '" title="' . esc_attr( get_the_title( $post_id ) ) . '">' 
			. get_the_title( $post_id ) . '</a>';

		if ( $args['showviews'] )
			echo '<span class="post-views">'
				. sprintf( __( '%d Views', TOPLYTICS_TEXTDOMAIN ), $post_views )	
				. '</span>';

		echo '</li>';
	}
	echo '</ol>';

	return true;
}
add_shortcode( 'toplytics', 'toplytics_results' );
