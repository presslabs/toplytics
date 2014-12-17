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

class Toplytics_Submenu_Settings extends Toplytics_Menu {
	private $toplytics;

	public function __construct() {
		parent::__construct();

		global $toplytics;
		$this->toplytics = $toplytics;

		$analytics = new Google_Service_Analytics( $this->toplytics->client );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'remove_credentials' ) );
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

	public function remove_credentials() {
		if ( isset( $_POST['ToplyticsSubmitRemoveCredentials'] ) ) {
			delete_option( 'toplytics_oauth_token' );
			$this->success_redirect();
		}
	}

	public function page() {
		$this->show_message();
		?>
		<div class="wrap">
		<h2><?php _e( 'Toplytics Settings', 'toplytics' ); ?></h2>

		<form action="" method="POST">
		<?php wp_nonce_field( 'toplytics-settings' ) ?>

		access_token:`<?php echo $this->toplytics->_get_token(); ?>`<br />
		refresh_token:`<?php echo $this->toplytics->_get_refresh_token(); ?>`<br />

		<p class="submit">
		<input type="submit" name="ToplyticsSubmitSave" class="button-primary" value="<?php _e( 'Save', 'toplytics' ); ?>" />
		<input type="submit" name="ToplyticsSubmitRemoveCredentials" class="button" value="<?php _e( 'Remove Credentials', 'toplytics' ); ?>" />
		</p>

		</form>
		</div>
		<?php
	}
}
