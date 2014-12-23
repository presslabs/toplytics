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

class Toplytics_Submenu_Configure extends Toplytics_Menu {
	private $toplytics;

	public function __construct() {
		parent::__construct();

		global $toplytics;
		$this->toplytics = $toplytics;

		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_init', array( $this, 'get_authorization_key' ) );
			add_action( 'admin_init', array( $this, 'request_token' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'exchange_code_for_token' ) );
		}
	}

	public function admin_menu() {
		$submenu_hook = add_management_page(
			'Toplytics',
			'Toplytics',
			'manage_options',
			$this->menu_slug,
			array( $this, 'page' )
		);
	}

	public function get_authorization_key() { // User login & consent
		if ( empty( $_POST['ToplyticsSubmitGetAuthorizationKey'] ) ) {
			return;
		}
		check_admin_referer( 'toplytics-admin' );
		$auth_url = $this->toplytics->client->createAuthUrl();
		wp_redirect( filter_var( $auth_url, FILTER_SANITIZE_URL ) );
	}

	public function request_token() { // get user token
		if ( empty( $_POST['ToplyticsSubmitGetAnalyticsProfiles'] ) || empty( $_POST['toplytics_authorization_key'] ) ) {
			return;
		}
		check_admin_referer( 'toplytics-admin' );
		wp_redirect( filter_var( $this->toplytics->return_settings_link() . '&code=' . $_POST['toplytics_authorization_key'], FILTER_SANITIZE_URL ) );
	}

	public function exchange_code_for_token( $hook ) { // Get token
		if ( ( 'tools_page_toplytics/toplytics' != $hook ) || empty( $_GET['code'] ) ) {
			return ;
		}
		try {
			$auth_secret = $_GET['code'];
			$this->toplytics->client->authenticate( $auth_secret );

			$access_token = $this->toplytics->client->getAccessToken();
			update_option( 'toplytics_oauth_token', $access_token );

			$refresh_token = $this->toplytics->client->getRefreshToken();
			update_option( 'toplytics_oauth_refresh_token', $refresh_token );

			$this->success_redirect( 'Google Analytics token was saved in DB successfully!' );
		} catch ( Exception $e ) {
			if ( 400 === $e->getCode() ) { // 'invalid_grant: Code was already redeemed.'
				$this->redirect( 'Invalid Authorization Key: ' . $_GET['code'] );
			} else {
				trigger_error( 'Exception: ' . $e->getCode() . ' -> '. $e->getMessage(), E_USER_ERROR );
				return false;
			}
		}
	}

	public function page() {
		$this->show_message();
		?>
		<div class="wrap">
			<h2><?php _e( 'Toplytics Configuration', 'toplytics' ); ?></h2>
			<form action="" method="post">
				<?php wp_nonce_field( 'toplytics-admin' ); ?>
			<table class="form-table">
				<tr valign="top">
				<th>
					<?php _e( "Please configure your Google Analytics Account. Login using Google's OAuth system.", 'toplytics' ); ?><br />
					<?php _e( "This is the prefered method of attaching your Google account. Clicking the 'Get Authorization Key' button will redirect you to a login page at google.com.<br/> After accepting the login there you will get a key. This key must be puted in `Authorization Key` field, then push the button `Get Analytics Profiles`", 'toplytics' ); ?>
				</tr>

				<tr valign="top">
				<td>
					<?php _e( 'Authorization Key', 'toplytics' ); ?>:&nbsp;<input type="text" name="toplytics_authorization_key" value="" />
				</td>
				</tr>

				<tr valign="top">
				<td>
				<p class="submit">
					<input type="submit" name="ToplyticsSubmitGetAnalyticsProfiles" class="button-primary" value="<?php _e( 'Get Analytics Profiles', 'toplytics' ); ?>" />
					<input type="submit" name="ToplyticsSubmitGetAuthorizationKey" class="button" value="<?php _e( 'Get Authorization Key', 'toplytics' ); ?>" />
				</p>
				</td>
				</tr>
			</table>
			</form>
		</div>
		<?php
	}
}
