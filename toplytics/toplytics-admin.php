<?php

function toplytics_validate_args( $args ) {
	if ( isset( $args['showviews'] ) ) // showviews (true/false - default=false)
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
 *  Displays all messages registered to 'your-settings-error-slug'
 */
function toplytics_admin_notices_action() {
	settings_errors();
}
add_action( 'admin_notices', 'toplytics_admin_notices_action' );

function toplytics_configuration_page( $info_message = '', $error_message = '' ) {
	$error_message = '';

	$account_base_url = 'https://www.googleapis.com/analytics/v2.4/management/';
	$url              = $account_base_url . 'accounts/~all/webproperties/~all/profiles';

	$account_hash_args = Toplytics_Auth::auth_process( $url );
	$ch = curl_init();

	curl_setopt( $ch, CURLOPT_URL, $account_base_url . 'accounts/~all/webproperties/~all/profiles' );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $account_hash_args );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

	$return = curl_exec( $ch );

	if ( curl_errno( $ch ) ) {
		$error_message = curl_error( $ch );
		$account_hash  = FALSE;
	}

	$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

	if ( $http_code != 200 ) {
		$error_message = $return;
		$account_hash  = FALSE;
	} else {
		$error_message = '';
		$xml = new SimpleXMLElement( $return );

		curl_close( $ch );

		$vhash = array();
		foreach ( $xml->entry as $entry ) {
			$value = (string) $entry->id;
			list( $part1, $part2 ) = explode( 'profiles/', $value );
			$vhash['ga:' . $part2] = (string) $entry->title;
		}
		$account_hash = $vhash;
	}

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
	toplytics_do_this_hourly();

	if ( isset( $info_message ) && '' != trim( $info_message ) ) {
		echo '<div id="message" class="updated fade"><p><strong>' . $info_message . '</strong></p></div>';
	}

	if ( isset( $error_message ) && '' != trim( $error_message ) ) {
		echo '<div id="message" class="error fade"><p><strong>' . $error_message . '</strong></p></div>';
	}

	if ( 0 != sizeof( $account_hash ) ) {
		$current_account_id = isset( $_POST['ga_account_id'] ) ? $_POST['ga_account_id'] : false !== get_option( 'toplytics_account_id' ) ? get_option( 'toplytics_account_id' ) : '' ;

		if ( ! isset( $current_account_id ) || '' == $current_account_id ) {
			?>
			<div class="updated">
			<p><?php _e( '<b>Note:</b> You will need to select an account and <b>click "Save Changes"</b> before the analytics dashboard will work.', TOPLYTICS_TEXTDOMAIN ); ?></p>
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
	if ( 0 == sizeof( $account_hash ) ) {
		echo '<span id="ga_account_id">' . __( 'No accounts available.', TOPLYTICS_TEXTDOMAIN ) . '</span>';
	} else {
		echo '<select id="ga_account_id" name="ga_account_id">';
		foreach ( $account_hash as $account_id => $account_name ) {
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
}

function toplytics_info_page() {
	?>
	<form action="" method="post">
	<table class="form-table">
		<tr valign="top">
		<th><?php _e( "Please configure your Google Analytics Account to be used for this site:<br /><br />Login using Google's OAuth system.", TOPLYTICS_TEXTDOMAIN ); ?></th>
		</tr>

		<tr valign="top">
		<th><?php _e( "This is the prefered method of attaching your Google account.<br/> Clicking the 'Start the Login Process' button will redirect you to a login page at google.com.<br/> After accepting the login there you will be returned here.", TOPLYTICS_TEXTDOMAIN ); ?></th>
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
	<?php
}

function toplytics_options_page() {
	if ( ! current_user_can( 'manage_options' ) )
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	$info_message  = '';
	$error_message = '';

	if ( isset( $_POST['SubmitOptions'] ) ) {
		delete_option( 'toplytics_account_id' );
		add_option( 'toplytics_account_id', $_POST['ga_account_id'] );
		$info_message = __( 'Options Saved', TOPLYTICS_TEXTDOMAIN );
	}

	if ( isset( $_POST['SubmitRemoveCredentials'] ) ) {
		toplytics_remove_credentials();
		$info_message = __( 'Credentials Removed', TOPLYTICS_TEXTDOMAIN );
	}

	if ( isset( $_POST['ga_cache_timeout'] ) ) {
		delete_option( 'toplytics_cache_timeout' );
		if ( '' != $_POST['ga_cache_timeout'] )
			add_option( 'toplytics_cache_timeout', $_POST['ga_cache_timeout'] );
	}
	?>
	<div class="wrap">
	<div id="icon-options-general" class="icon32">&nbsp;</div>
	<h2>Toplytics <?php _e( 'Settings' ); ?></h2>
	<?php
	/**
	 *  if settings are not empty then run the function called every hour (scan the GA statistics)
	 *  this case is useful when you change the GA account settings
	 */
	if ( toplytics_has_configuration() ) {
		toplytics_configuration_page( $info_message, $error_message );
	} else {
		toplytics_info_page();
	}
	?></div><?php
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
