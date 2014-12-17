<?php
/**
 * Plugin Name: Toplytics
 * Plugin URI: http://wordpress.org/extend/plugins/toplytics/
 * Description: Plugin for displaying most viewed content using data from a Google Analytics account. Relieves the DB from writing every click.
 * Author: PressLabs
 * Version: 3.0
 * Author URI: http://www.presslabs.com/
 * License: GPL2
 * Text Domain: toplytics
 * Domain Path: /languages/
 */

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

require_once __DIR__ . '/lib/google-api/autoload.php';
require_once __DIR__ . '/inc/class-toplytics-admin.php';
require_once __DIR__ . '/inc/class-toplytics-menu.php';
require_once __DIR__ . '/inc/class-toplytics-submenu-configure.php';
require_once __DIR__ . '/inc/class-toplytics-submenu-settings.php';

class Toplytics {
	const DEFAULT_POSTS = 5;
	const MIN_POSTS     = 1;
	const MAX_POSTS     = 25;
	const MAX_RESULTS   = 1000;
	const TEXTDOMAIN    = 'toplytics';
	const TEMPLATE      = 'toplytics-template.php';
	const CACHE_TTL     = 300;

	public $client;

	public function __construct() {
		$client = new Google_Client();
		$client->setAuthConfigFile( __DIR__ . DIRECTORY_SEPARATOR . 'client.json' );
		$client->addScope( Google_Service_Analytics::ANALYTICS_READONLY );
		$client->setAccessType( 'offline' );
		$client->setRedirectUri( 'http://127.0.0.1:8080/wordpress/wp-admin/admin.php?page=toplytics/toplytics.php' );
		$this->client = $client;
	}

	public function _get_token() {
		$token = get_option( 'toplytics_oauth_token' );
		if ( false == $token ) {
			return false;
		} else {
			$token = json_decode( $token );
			return $token->{'access_token'};
		}
	}

	public function _get_refresh_token() {
		$refresh_token = get_option( 'toplytics_oauth_refresh_token' );
		if ( false == $refresh_token ) {
			return false;
		} else {
			return $refresh_token;
		}
	}

	private function _get_analytics_data() {
	}

	public function update_analytics_data() {
		try {
			$data = $this->_get_analytics_data();
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		// convert data to posts
		update_transient( 'toplytics_cached_results', $results );
		$results['_ts'] = time();
	}

	public function get_data( $when = 'daily' ) {
		$cached_results = get_transient( 'toplytics_cached_results', false );
		if ( false !== $cached_results and $now - $cached_results['ts'] < Toplytics::CACHE_TTL ) {
			return $results[ $when ];
		}
		$results = $this->update_analytics_data();
		if ( $results === false && ! empty( $cached_results ) ) {
			return $cached_results[ $when ];
		}

		if ( $results === false ) {
		} else {
			return $results[ $when ];
		}
	}
}
global $toplytics;
$toplytics = new Toplytics();
