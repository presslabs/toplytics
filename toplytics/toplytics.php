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
	public $service;

	public function __construct() {
		$client = new Google_Client();
		$client->setAuthConfigFile( __DIR__ . DIRECTORY_SEPARATOR . 'client.json' );
		$client->addScope( Google_Service_Analytics::ANALYTICS_READONLY );
		$client->setAccessType( 'offline' );
		$client->setRedirectUri( 'http://127.0.0.1:8080/wordpress/wp-admin/admin.php?page=toplytics/toplytics.php' );

		$this->client  = $client;
		$this->service = new Google_Service_Analytics( $this->client );
	}
	/*
	public function get_first_profile_id() {
		$accounts = $this->service->management_accounts->listManagementAccounts();

		if ( count( $accounts->getItems() ) > 0 ) {
			$items = $accounts->getItems();
			$firstAccountId = $items[0]->getId();

			$webproperties = $this->service->management_webproperties->listManagementWebproperties( $firstAccountId );

			if ( count( $webproperties->getItems() ) > 0 ) {
				$items = $webproperties->getItems();
				$firstWebpropertyId = $items[0]->getId();

				$profiles = $this->service->management_profiles->listManagementProfiles( $firstAccountId, $firstWebpropertyId );

				if ( count( $profiles->getItems() ) > 0 ) {
					$items = $profiles->getItems();
					return $items[0]->getId();
				} else {
					throw new Exception( 'No views (profiles) found for this user.' );
				}
			} else {
				throw new Exception( 'No webproperties found for this user.' );
			}
		} else {
			throw new Exception( 'No accounts found for this user.' );
		}
	}
	*/
	public function get_accounts(){
		$man_accounts = $this->service->management_accounts->listManagementAccounts();
		$accounts = [];
		foreach ( $man_accounts['items'] as $account ) {
			$accounts[] = [ 'id' => $account['id'], 'name' => $account['name'] ];
		}
		return $accounts;
	}

	private function _get_token() {
		return get_option( 'toplytics_oauth_token' );
	}

	private function _get_refresh_token() {
		return get_option( 'toplytics_oauth_refresh_token' );
	}

	private function _get_analytics_data() {
		$this->client->setAccessToken( $this->_get_token() );
		$range = array(
			'month'  => date( 'Y-m-d', strtotime( '-30 days'  ) ),
			'today'  => date( 'Y-m-d', strtotime( 'yesterday' ) ),
			'2weeks' => date( 'Y-m-d', strtotime( '-14 days'  ) ),
			'week'   => date( 'Y-m-d', strtotime( '-7 days'   ) ),
		);
		$metrics  = 'ga:pageviews';
		$optParams = array(
			'quotaUser'   => md5( home_url() ),
			'dimensions'  => 'ga:pagePath',
			'sort'        => '-ga:pageviews',
			'max-results' => $this::MAX_RESULTS,
		);
		$result = array();
		foreach ( $range as $when => $date ) {
			$rows = $this->service->data_ga->get( 'ga:94200457', $range[ $when ], date( 'Y-m-d' ), $metrics, $optParams )->rows;
			foreach ( $rows as $item ) {
				$result[ $when ][ $item[0] ] = $item[1];
			}
		}
		return $result;
	}

	private function _convert_data_to_posts( $data ) {
		return $data;
	}

	public function update_analytics_data() {
		try {
			$data = $this->_get_analytics_data();
		} catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		$results = $this->_convert_data_to_posts( $data );
		$results['_ts'] = time();
		set_transient( 'toplytics_cached_results', $results );
	}

	public function get_data( $when = 'today' ) {
		$cached_results = get_transient( 'toplytics_cached_results', false );
		if ( false !== $cached_results and time() - $cached_results['_ts'] < Toplytics::CACHE_TTL ) {
			return $cached_results[ $when ];
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
