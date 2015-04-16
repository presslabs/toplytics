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


class Toplytics_Admin {
	private $toplytics;

	public function __construct() {
		global $toplytics;
		$this->toplytics = $toplytics;

		if ( current_user_can( 'manage_options' ) ) {
			if ( $this->toplytics->get_token() ) {
				new Toplytics_Submenu_Settings();
				delete_option( 'toplytics_disconnect_message' );
			} else {
				new Toplytics_Submenu_Configure();
				add_action( 'admin_init', array( $this, 'admin_notices' ) );
			}
			if ( get_option( 'toplytics_disconnect_message' ) ) {
				add_action( 'admin_init', array( $this, 'admin_disconnect_notice' ) );
				add_action( 'admin_init', array( $this, 'dismiss' ) );
			}
		}
	}

	public function notice() {
		?><div class="error"><p><?php
		echo sprintf(
			__( 'Toplytics needs configuration information on its <a href="%s">Settings</a> page.', 'toplytics' ),
			$this->toplytics->return_settings_link()
		);
		?></p></div><?php
	}

	public function admin_notices() {
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	public function disconnect_notice() {
		?><div class="error"><p><?php
		echo sprintf(
			__( 'Toplytics plugin was disconnected! Possible reason: %s!', 'toplytics' ),
			get_option( 'toplytics_disconnect_message' )
		)
		. ' '
		. sprintf(
			__( '<a href="%s">Dismiss</a>', 'toplytics' ), $this->toplytics->return_settings_link()
			. '&ToplyticsDismiss=true'
		);
		?></p></div><?php
	}

	public function admin_disconnect_notice() {
		add_action( 'admin_notices', array( $this, 'disconnect_notice' ) );
	}

	public function dismiss() {
		if ( empty( $_GET['ToplyticsDismiss'] ) ) {
			return;
		}
		delete_option( 'toplytics_disconnect_message' );
		wp_redirect( filter_var( $this->toplytics->return_settings_link(), FILTER_SANITIZE_URL ) );
	}
}

if ( is_admin() ) {
	function toplytics_admin_page() {
		new Toplytics_Admin();
	}
	add_action( 'init', 'toplytics_admin_page' );
}
