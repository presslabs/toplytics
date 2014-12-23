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
		add_action( 'admin_init', array( $this, 'connect' ) );
		add_action( 'admin_init', array( $this, 'disconnect' ) );
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

	public function connect() {
		if ( isset( $_POST['ToplyticsSubmitConnect'] ) && isset( $_POST['profile_id'] ) ) {
			foreach ( $this->toplytics->get_profiles_list() as $profile_id => $profile_info ) {
				if ( $_POST['profile_id'] == $profile_id ) {
					$this->toplytics->update_profile_data( $profile_id, $profile_info );
					break;
				}
			}
			$this->success_redirect( 'Toplytics connected successfully!' );
		}
	}

	public function disconnect() {
		if ( isset( $_POST['ToplyticsSubmitDisconnect'] ) ) {
			$this->toplytics->disconnect();
			$this->success_redirect( 'Toplytics disconnected successfully!' );
		}
	}

	private function _analytics_profiles_selector() {
		?>
		<table class="form-table">
		<tr valign="top">
		<th scope="row"><label for="profile_id"><?php _e( 'Analytics Profiles', 'toplytics' ); ?></label></th>
		<td>
		<select id="profile_id" name="profile_id">
		<?php
		foreach ( $this->toplytics->get_profiles_list() as $profile_id => $profile_info ) {
			echo '<option value="' . $profile_id . '">' . $profile_info . '</option>';
		}
		?>
		</select>
		</td>
		</tr>
		</table>
		<?php
	}

	private function _show_connection_info() {
		?>
		<table class="form-table">
		<tr valign="top">
		<th scope="row"><label for="profile_id"><?php _e( 'Connected to: ', 'toplytics' ); ?></label></th>
		<td><?php echo $this->toplytics->get_profile_info(); ?></td>
		</tr>
		</table>
		<?php
	}

	public function page() {
		$this->show_message();
		?>
		<div class="wrap">
		<h2><?php _e( 'Toplytics Settings', 'toplytics' ); ?></h2>

		<form action="" method="POST">
		<?php
		wp_nonce_field( 'toplytics-settings' );

		if ( ! $this->toplytics->get_profile_data() ) {
			$this->_analytics_profiles_selector();
		} else {
			$this->_show_connection_info();
		}
		?>
		<p class="submit">
		<?php if ( ! $this->toplytics->get_profile_data()  ) { ?>
		<input type="submit" name="ToplyticsSubmitConnect" class="button-primary" value="<?php _e( 'Connect', 'toplytics' ); ?>" />&nbsp;&nbsp;
		<?php } ?>
		<input type="submit" name="ToplyticsSubmitDisconnect" class="button" value="<?php _e( 'Disconnect', 'toplytics' ); ?>" />
		</p>

		</form>
		</div>
		<?php
	}
}
