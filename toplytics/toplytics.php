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
require_once __DIR__ . '/inc/class-toplytics-wp-widget.php';

class Toplytics {
	const DEFAULT_POSTS = 5;
	const MIN_POSTS     = 1;
	const MAX_RESULTS   = 1000;
	const TEMPLATE      = 'toplytics-template.php';
	const CACHE_TTL     = 300;

	public $client;
	public $service;
	public $ranges;

	public function __construct() {
		add_filter( 'plugin_action_links_' . $this->_plugin_basename() , array( $this, '_settings_link' ) );

		$client = new Google_Client();
		$client->setAuthConfigFile( __DIR__ . DIRECTORY_SEPARATOR . 'client.json' );
		$client->addScope( Google_Service_Analytics::ANALYTICS_READONLY );
		$client->setAccessType( 'offline' );
		$client->setRedirectUri( site_url() . '/wp-admin/admin.php?page=toplytics/toplytics.php' );

		if ( get_option( 'toplytics_oauth_token' ) ) { $client->setAccessToken( $this->_get_token() ); }

		$this->client  = $client;
		$this->service = new Google_Service_Analytics( $this->client );

		$this->ranges = array(
			'month'  => date( 'Y-m-d', strtotime( '-30 days'  ) ),
			'2weeks' => date( 'Y-m-d', strtotime( '-14 days'  ) ),
			'week'   => date( 'Y-m-d', strtotime( '-7 days'   ) ),
			'today'  => date( 'Y-m-d', strtotime( 'yesterday' ) ),
		);
	}

	private function _plugin_basename() {
		return 'toplytics/toplytics.php';
	}

	public function return_settings_link() {
		return admin_url( 'tools.php?page=' . $this->_plugin_basename() );
	}

	/**
	 *  Add settings link on plugin page
	 */
	public function _settings_link( $links ) {
		$settings_link = '<a href="' . $this->return_settings_link() . '">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
	/**
	 * Return all profiles of the current user from GA api.
	 *
	 * Return result type stored in WP options:
	 *
	 * Array(
	 *  'profile_id' => 'account_name > property_name (Tracking ID) > profile_name',
	 *  'profile_id' => 'account_name > property_name (Tracking ID) > profile_name',
	 * )
	 *
	 * Note that the `Tracking ID` is the same with the `property_id`.
	 */
	public function get_profiles_list() {
		try {
			$profiles_list = [];
			$profiles = $this->_get_profiles();
			foreach ( $profiles as $profile_id => $profile_data ) {
				$profiles_list[ $profile_id ] = $profile_data['account_name'] . ' > ' . $profile_data['property_name'] . ' (' . $profile_data['property_id'] . ') > ' . $profile_data['profile_name'];
			}
			return $profiles_list;
		} catch ( Exception $e ) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	}

	private function _get_profiles() {
		$profiles = [];
		$accounts = $this->_get_accounts();
		foreach ( $accounts as $account_id => $account_name ) {
			$webproperties = $this->_get_webproperties( $account_id );
			foreach ( $webproperties as $web_prop_id => $web_prop_name ) {
				$man_profiles = $this->service->management_profiles->listManagementProfiles( $account_id, $web_prop_id );
				if ( 0 < count( $man_profiles->getItems() ) ) {
					foreach ( $man_profiles->getItems() as $item ) {
						$profiles[ $item->getId() ]['profile_name']  = $item->getName();
						$profiles[ $item->getId() ]['account_id']    = $account_id;
						$profiles[ $item->getId() ]['account_name']  = $account_name;
						$profiles[ $item->getId() ]['property_id']   = $web_prop_id;
						$profiles[ $item->getId() ]['property_name'] = $web_prop_name;
					}
				} else {
					throw new Exception( 'No views (profiles) found for this user.' );
				}
			}
		}
		return $profiles;
	}

	private function _get_webproperties( $account_id ) {
		$man_webproperties = $this->service->management_webproperties->listManagementWebproperties( $account_id );
		if ( 0 < count( $man_webproperties->getItems() ) ) {
			$webproperties = [];
			foreach ( $man_webproperties->getItems() as $item ) {
				$webproperties[ $item->getId() ] = $item->getName();
			}
			return $webproperties;
		} else {
			throw new Exception( 'No webproperties found for this user.' );
		}
	}

	private function _get_accounts() {
		$man_accounts = $this->service->management_accounts->listManagementAccounts();
		if ( 0 < count( $man_accounts->getItems() ) ) {
			$accounts = [];
			foreach ( $man_accounts->getItems() as $item ) {
				$accounts[ $item->getId() ] = $item->getName();
			}
			return $accounts;
		} else {
			throw new Exception( 'No accounts found for this user.' );
		}
	}

	public function update_profile_data( $profile_id, $profile_info ) {
		$profile_data = array(
			'profile_id'   => $profile_id,
			'profile_info' => $profile_info,
		);
		update_option( 'toplytics_profile_data', json_encode( $profile_data ) );
	}

	public function get_profile_data() {
		return get_option( 'toplytics_profile_data' );
	}

	public function get_profile_info() {
		$profile_data = $this->get_profile_data();
		if ( false === $profile_data ) {
			return false;
		}
		$profile_data = json_decode( $profile_data, true );
		return $profile_data['profile_info'];
	}

	public function remove_credentials() {
		delete_option( 'toplytics_oauth_token' );
		delete_option( 'toplytics_profile_data' );
	}

	private function _get_profile_id() {
		$profile_data = $this->get_profile_data();
		if ( false === $profile_data ) {
			Throw new Exception( 'There is no profile data in DB.' );
		}
		$profile_data = json_decode( $profile_data, true );
		return $profile_data['profile_id'];
	}

	private function _get_token() {
		return get_option( 'toplytics_oauth_token' );
	}

	private function _get_refresh_token() {
		return get_option( 'toplytics_oauth_refresh_token' );
	}

	private function _get_analytics_data() {
		$metrics  = 'ga:pageviews';
		$optParams = array(
			'quotaUser'   => md5( home_url() ),
			'dimensions'  => 'ga:pagePath',
			'sort'        => '-ga:pageviews',
			'max-results' => $this::MAX_RESULTS,
		);
		$result = array();
		foreach ( $toplytics->ranges as $when => $start_date ) {
			$rows = $this->service->data_ga->get( 'ga:' . $this->_get_profile_id(), $start_date, date( 'Y-m-d' ), $metrics, $optParams )->rows;
			$result[ $when ] = array();
			if ( $rows ) {
				foreach ( $rows as $item ) {
					$result[ $when ][ $item[0] ] = $item[1];
				}
			}
		}
		return $result;
	}

	private function _convert_data_to_posts( $data ) {
		$new_data = array();
		foreach ( $data as $when => $stats ) {
			$new_data[ $when ] = array();
			foreach ( $stats as $rel_path => $pageviews ) {
				$link    = home_url() . $rel_path;
				$post_id = url_to_postid( $link );
				if ( 'post' == get_post_type( $post_id ) ) {
					$post = get_post( $post_id );
					if ( $post && isset( $new_data[ $when ][ $post_id ] ) ) {
						$new_data[ $when ][ $post_id ] += $pageviews;
					} else {
						$new_data[ $when ][ $post_id ] = $pageviews;
					}
				}
			}
		}
		return $new_data;
	}

	public function update_analytics_data() {
		try {
			$data = $this->_get_analytics_data();
		} catch ( Exception $e ) {
			trigger_error( 'Cannot update Google Analytics data: '. $e->getMessage(), E_USER_ERROR );
			return false;
		}
		$results = $this->_convert_data_to_posts( $data );
		$results['_ts'] = time();
		set_transient( 'toplytics_cached_results', $results );
		return $results;
	}

	public function get_data( $when = 'today' ) {
		$cached_results = get_transient( 'toplytics_cached_results' );
		if ( false !== $cached_results and time() - $cached_results['_ts'] < Toplytics::CACHE_TTL ) {
			return $cached_results[ $when ];
		}
		$results = $this->update_analytics_data();
		if ( $results === false && ! empty( $cached_results ) ) {
			return $cached_results[ $when ];
		}

		if ( $results === false ) {
			return false;
		} else {
			return $results[ $when ];
		}
	}
}
global $toplytics;
$toplytics = new Toplytics();
