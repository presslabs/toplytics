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

if ( ! class_exists( 'Toplytics_Update' ) ) :

	class Toplytics_Update {

		public function __construct() {
			if ( current_user_can( 'manage_options' ) ) {
				add_action( 'in_plugin_update_message-toplytics/toplytics.php', array( $this, 'in_plugin_update_message' ) );
			}
		}

		public function in_plugin_update_message( $args ) {
			$response = wp_remote_get( 'https://plugins.svn.wordpress.org/toplytics/trunk/readme.txt' );

			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) { // Output Upgrade Notice
				$matches        = null;
				$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( Toplytics::VERSION ) . '\s*=|$)~Uis';
				$upgrade_notice = '';

				if ( preg_match( $regexp, $response['body'], $matches ) ) {
					$version = trim( $matches[1] );
					$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

					if ( version_compare( Toplytics::VERSION, $version, '<' ) ) {
						$upgrade_notice .= '<div style="font-weight: 400; color: #fff; background: #d54d21; padding: 1em; margin: 9px 0;">';

						foreach ( $notices as $index => $line ) {
							$upgrade_notice .= wp_kses_post( preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line ) );
						}

						$upgrade_notice .= '</div> ';
					}
				}
			}

			if ( ! empty( $upgrade_notice ) ) {
				echo wp_kses_post( $upgrade_notice );
			}
		}
	}

	if ( is_admin() ) {
		function toplytics_update_admin_page() {
			new Toplytics_Update();
		}
		add_action( 'init', 'toplytics_update_admin_page' );
	}

endif;
