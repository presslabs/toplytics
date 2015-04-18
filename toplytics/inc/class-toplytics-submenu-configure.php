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
			add_action( 'admin_init', array( $this, 'upload_auth_config_file' ) );
			add_action( 'admin_init', array( $this, 'reset_auth_config' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'exchange_code_for_token' ) );
		}
	}

	public function admin_menu() {
		$submenu_hook = add_options_page(
			'Toplytics',
			'Toplytics',
			'manage_options',
			$this->menu_slug,
			array( $this, 'page' )
		);
		new Toplytics_Help( $submenu_hook, 'Configuration' );
	}

	public function upload_auth_config_file() {
		if ( empty( $_POST['ToplyticsSubmitUploadAuthConfigFile'] ) ) {
			return;
		}
		check_admin_referer( 'toplytics-admin' );
		if ( ! empty( $_FILES['ToplyticsAuthConfigFile']['tmp_name'] ) ) {
			global $toplytics;
			$filename     = $_FILES['ToplyticsAuthConfigFile']['name'];
			$tmp_filename = $_FILES['ToplyticsAuthConfigFile']['tmp_name'];
			$json_content = file_get_contents( $tmp_filename );
			if ( $toplytics->is_valid_auth_config( $json_content ) ) {
				$toplytics->load_auth_config( $json_content );
				$this->success_redirect( sprintf( __( 'The file `%s` was uploaded!', 'toplytics' ), $filename ) );
			} else {
				$this->redirect( __( 'Invalid file structure.', 'toplytics' ) );
			}
		} else {
			$this->redirect( __( 'No file found! Please choose a file first.', 'toplytics' ) );
		}
	}

	public function reset_auth_config() {
		if ( empty( $_POST['ToplyticsSubmitResetAuthConfig'] ) ) {
			return;
		}
		check_admin_referer( 'toplytics-admin' );
		delete_option( 'toplytics_auth_config' );
		$this->success_redirect( __( 'Auth configurations are now reset.', 'toplytics' ) );
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
		if ( ( 'settings_page_toplytics/toplytics' != $hook ) || empty( $_GET['code'] ) ) {
			return ;
		}
		try {
			$auth_secret = $_GET['code'];
			$this->toplytics->client->authenticate( $auth_secret );

			$access_token = $this->toplytics->client->getAccessToken();
			$this->toplytics->update_token( $access_token );

			$refresh_token = $this->toplytics->client->getRefreshToken();
			$this->toplytics->update_refresh_token( $refresh_token );

			$this->success_redirect( __( 'Google Analytics token was saved in DB successfully!', 'toplytics' ) );
		} catch ( Exception $e ) {
			if ( 400 === $e->getCode() ) { // 'invalid_grant: Code was already redeemed.'
				$this->redirect( __( 'Invalid Authorization Key: ', 'toplytics' ) . $_GET['code'] );
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
			<h2>Toplytics <?php _e( 'Configuration', 'toplytics' ); ?></h2>
			<table class="form-table">
				<tr valign="top">
				<td>
					<strong><?php _e( 'Auth configuration', 'toplytics' ); ?>:</strong><br /><br />
					<?php $this->toplytics->show_auth_config(); ?>
				</td>
				</tr>

			<form enctype="multipart/form-data" action="" method="post">
				<?php wp_nonce_field( 'toplytics-admin' ); ?>
				<?php if ( ! $this->toplytics->is_valid_auth_config() ) { ?>
				<tr valign="top">
				<td>
				<p class="submit">
					<input type="file" name="ToplyticsAuthConfigFile" value="" />
					<input type="submit" name="ToplyticsSubmitUploadAuthConfigFile" class="button" value="<?php _e( 'Upload Auth Config File', 'toplytics' ); ?>" />
				</p>
				</td>
				</tr>
				<?php } else { ?>
				<tr valign="top">
				<td>
				<p class="submit">
					<?php _e( 'Press this button in order to reset the Auth Configurations', 'toplytics' ); ?>:&nbsp;
					<input type="submit" name="ToplyticsSubmitResetAuthConfig" class="button" value="<?php _e( 'Reset Auth Config', 'toplytics' ); ?>" />
				</p>
				</td>
				</tr>
				<?php } ?>
			</form>

			<?php if ( $this->toplytics->is_valid_auth_config() ) { ?>
				<form action="" method="post">
					<?php wp_nonce_field( 'toplytics-admin' ); ?>
					<tr valign="top">
					<td>
						<strong><?php _e( 'Please connect to your Google Analytics Account.', 'toplytics' ); ?></strong><br /><br />
						<ol>
						<li><?php _e( "Click the 'Get Authorization Key' button and you will be redirected to google.com", 'toplytics' ); ?></li>
						<li><?php _e( 'After logging in you will receive a key', 'toplytics' ); ?></li>
						<li><?php _e( "Then come back to this page and use the key in the 'Authorization Key' field, and then click 'Get Analytics Profiles' button", 'toplytics' ); ?></li>
						</ol>
					</td>
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
				</form>
			<?php } ?>

			</table>
		</div>
		<?php
	}
}
