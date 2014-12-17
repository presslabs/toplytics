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
		<?php
		wp_nonce_field( 'toplytics-settings' );
		?>

		<table class="form-table">
		<tr valign="top">
		<th scope="row"><label for="ga_account_id"><?php _e( 'Available Accounts', 'toplytics' ); ?></label></th>
		<td>
		<?php $account_hash = false;

		$results  = $this->toplytics->get_data( 'today' );
		?><pre><?php print_r( $results ); ?></pre><?php

		/*
		if ( ! $account_hash ) {
			echo '<span id="ga_account_id">' . __( 'You have no accounts available or there was an error querying Google Analytics.', TOPLYTICS_TEXTDOMAIN ) . '</span>';
		} else {
			echo '<select id="ga_account_id" name="ga_account_id">';
			foreach ( $account_hash as $account_id => $account_name ) {
				echo '<option value="' . $account_id . '" ' . ( $current_account_id == $account_id ? 'selected' : '' ) . '>' . $account_name . '</option>';
			}
			echo '</select>';
		}
		*/
		?>
		</td>
		</tr>
		</table>

		<p class="submit">
		<input type="submit" name="ToplyticsSubmitSaveChanges" class="button-primary" value="<?php _e( 'Save Changes', 'toplytics' ); ?>" />&nbsp;&nbsp;
		<input type="submit" name="ToplyticsSubmitRemoveCredentials" class="button" value="<?php _e( 'Remove Credentials', 'toplytics' ); ?>" />
		</p>

		</form>
		</div>
		<?php
	}
}
