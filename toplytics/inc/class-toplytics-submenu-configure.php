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

	public function __construct() {
		parent::__construct( $this->toplytics_menu_slug, $this->toplytics_menu_slug );

		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_init', array( $this, 'choose_branch' ) );
		}
	}

	public function admin_menu() {
		add_menu_page(
			__( 'Toplytics Configuration', 'toplytics' ),
			'Toplytics',
			'manage_options',
			$this->menu_slug,
			array( $this, 'page' )
		);

		$submenu_hook = add_submenu_page(
			$this->menu_slug,
			__( 'Toplytics Configuration', 'toplytics' ),
			__( 'Configuration', 'toplytics' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'page' )
		);
	}

	public function choose_branch() {
		if ( ! isset( $_POST['GitiumSubmitMergeAndPush'] ) || ! isset( $_POST['tracking_branch'] ) ) {
			return;
		}
		check_admin_referer( 'gitium-admin' );
		$this->git->add();

		$branch       = $_POST['tracking_branch'];
		$current_user = wp_get_current_user();

		$commit = $this->git->commit( __( 'Merged existing code from ', 'gitium' ) . get_home_url(), $current_user->display_name, $current_user->user_email );
		if ( ! $commit ) {
			$this->git->cleanup();
			$this->redirect( __( 'Could not create initial commit -> ', 'gitium' ) . $this->git->get_last_error() );
		}
		if ( ! $this->git->merge_initial_commit( $commit, $branch ) ) {
			$this->git->cleanup();
			$this->redirect( __( 'Could not merge the initial commit -> ', 'gitium' ) . $this->git->get_last_error() );
		}
		$this->git->push( $branch );
		$this->success_redirect();
	}

	public function page() {
		$this->show_message();
		?>
		<div class="wrap">
			<h2><?php _e( 'Toplytics Configuration', 'toplytics' ); ?></h2>
			<form action="" method="POST">
				<?php wp_nonce_field( 'toplytics-admin' ); ?>
				<p class="submit">
				<input type="submit" name="ToplyticsSubmitLoginProcess" class="button-primary" value="<?php _e( 'Login Process', 'toplytics' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}
}
