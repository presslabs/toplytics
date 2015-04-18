<?php
/**
 * Plugin Name: Toplytics
 * Plugin URI: http://wordpress.org/extend/plugins/toplytics/
 * Description: Plugin for displaying most viewed content using data from a Google Analytics account. Relieves the DB from writing every click.
 * Author: Presslabs
 * Version: 3.0
 * Author URI: http://www.presslabs.com/
 * License: GPL2
 * Text Domain: toplytics
 * Domain Path: /languages/
 */

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

require_once __DIR__ . '/lib/google-api-php-client/src/Google/autoload.php';

class Toplytics {
	const VERSION       = '3.0';
	const DEFAULT_POSTS = 5;
	const MIN_POSTS     = 1;
	const MAX_POSTS     = 100;
	const MAX_RESULTS   = 250;
	const TEMPLATE      = 'toplytics-template.php';

	public $client;
	public $service;
	public $ranges;

	public function __construct() {
		add_filter( 'toplytics_rel_path', array( $this, 'filter_rel_path' ) );
		add_filter( 'plugin_action_links_' . $this->plugin_basename() , array( $this, '_settings_link' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		add_filter( 'redirect_canonical', array( $this, 'canonical' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_filter( 'template_include', array( $this, 'handle_endpoint' ) );
		add_action( 'wp_loaded', array( $this, 'add_endpoint' ) );

		// add cron event
		if ( $this->get_token() ) {
			add_action( 'wp', array( $this, 'setup_schedule_event' ) );
			add_action( 'toplytics_cron_event', array( $this, 'update_analytics_data' ) );
		}
		register_activation_hook( __FILE__, array( $this, 'activation_hook' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation_hook' ) );

		$this->add_ranges();
		$this->add_google_client();
	}

	public function add_ranges() {
		$ranges = array(
			'month' => date_i18n( 'Y-m-d', strtotime( '-29 days' ) ),
			'week'  => date_i18n( 'Y-m-d', strtotime( '-6 days'  ) ),
			'today' => date_i18n( 'Y-m-d', strtotime( 'today'    ) ),
		);
		$this->ranges = apply_filters( 'toplytics_ranges', $ranges );
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'toplytics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	public function add_google_client() {
		$auth_config = $this->get_auth_config();
		if ( ! empty( $auth_config ) ) {
			try {
				$client = new Google_Client();
				$client->setAuthConfig( $this->get_auth_config() );
				$client->addScope( Google_Service_Analytics::ANALYTICS_READONLY );
				$client->setAccessType( 'offline' );

				$token = $this->get_token();
				if ( $token ) {
					$client->setAccessToken( $token );
				}

				if ( $client->isAccessTokenExpired() ) {
					$refresh_token = $this->get_refresh_token();
					if ( $refresh_token ) {
						$client->refreshToken( $refresh_token );
						$this->update_token( $client->getAccessToken() );
					}
				}

				$this->client  = $client;
				$this->service = new Google_Service_Analytics( $this->client );
			} catch ( Exception $e ) {
				$message = 'Google Analytics Error[' . $e->getCode() . ']: '. $e->getMessage();
				$this->disconnect( $message );
				error_log( $message, E_USER_ERROR );
				return;
			}
		}
	}

	public function activation_hook() {
		$this->remove_old_credentials();
		$this->add_endpoint();
		$this->flush_rules();
		$this->add_options();
	}

	public function deactivation_hook() {
		$this->flush_rules();
	}

	public function add_options() {
		foreach ( $this->ranges as $when => $data ) :
			add_option( "toplytics_result_$when", array(), '', 'no' );
		endforeach;
	}

	public function add_query_var( $query_vars ) {
		$query_vars[] = 'toplytics';
		return $query_vars;
	}

	public function flush_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	public function add_endpoint() {
		add_rewrite_rule( 'toplytics\.json$', 'index.php?toplytics=json', 'top' );
	}

	public function handle_endpoint( $template ) {
		global $wp_query;
		if ( ! empty( $wp_query->query_vars['toplytics'] ) ) {
			$this->json_data();
		}
		return $template;
	}

	/**
	 * Hook into redirect_canonical to stop trailing slashes on sitemap.xml URLs
	 *
	 * @param string $redirect The redirect URL currently determined.
	 *
	 * @return bool|string $redirect
	 */
	function canonical( $redirect ) {
		$query_var = get_query_var( 'toplytics' );
		if ( ! empty( $query_var ) ) {
			return false;
		}
		return $redirect;
	}

	/**
	 * On an early action hook, check if the hook is scheduled - if not, schedule it.
	 */
	function setup_schedule_event() {
		if ( ! wp_next_scheduled( 'toplytics_cron_event' ) ) {
			wp_schedule_event( time(), 'hourly', 'toplytics_cron_event' );
		}
	}

	public function remove_old_credentials() {
		delete_option( 'toplytics_oauth_token' );
		delete_option( 'toplytics_oauth_secret' );
		delete_option( 'toplytics_auth_token' );
		delete_option( 'toplytics_account_id' );
		delete_option( 'toplytics_cache_timeout' );
		delete_option( 'toplytics_results' );
	}

	public function enqueue_script() {
		wp_register_script( 'toplytics', plugins_url( 'js/toplytics.js' , __FILE__ ) );
		wp_localize_script( 'toplytics', 'toplytics', array( 'json_url' => esc_url( home_url( '/toplytics.json' ) ) ) );
		wp_enqueue_script( 'toplytics' );
	}

	public function get_template_filename() {
		$toplytics_template = Toplytics::TEMPLATE;

		$theme_template = get_stylesheet_directory() . "/$toplytics_template";
		if ( file_exists( $theme_template ) ) {
			return $theme_template;
		}

		$plugin_template = plugin_dir_path( __FILE__ ) . $toplytics_template;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return '';
	}

	public function plugin_basename() {
		return 'toplytics/toplytics.php';
	}

	public function return_settings_link() {
		return admin_url( 'options-general.php?page=' . $this->plugin_basename() );
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
			$profiles_list = array();
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
		$profiles = array();
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
			$webproperties = array();
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
			$accounts = array();
			foreach ( $man_accounts->getItems() as $item ) {
				$accounts[ $item->getId() ] = $item->getName();
			}
			return $accounts;
		} else {
			throw new Exception( 'No accounts found for this user.' );
		}
	}

	public function get_profile_info() {
		$profile_data = $this->get_profile_data();
		if ( false === $profile_data ) {
			return false;
		}
		$profile_data = json_decode( $profile_data, true );
		return $profile_data['profile_info'];
	}

	public function disconnect( $message ) {
		update_option( 'toplytics_disconnect_message', apply_filters( 'toplytics_disconnect_message', $message ) );
		$this->remove_token();
		$this->remove_refresh_token();
		$this->remove_profile_data();
		$this->remove_auth_config();
	}

	private function _get_profile_id() {
		$profile_data = $this->get_profile_data();
		if ( false === $profile_data ) {
			Throw new Exception( 'There is no profile data in DB.' );
		}
		$profile_data = json_decode( $profile_data, true );
		if ( ! empty( $profile_data['profile_id'] ) ) {
			return $profile_data['profile_id'];
		}

		return '';
	}

	public function get_profile_data() {
		return get_option( 'toplytics_profile_data' );
	}

	public function remove_profile_data() {
		return delete_option( 'toplytics_profile_data' );
	}

	public function update_profile_data( $profile_id, $profile_info ) {
		$profile_data = array(
			'profile_id'   => $profile_id,
			'profile_info' => $profile_info,
		);
		update_option( 'toplytics_profile_data', json_encode( $profile_data ) );
	}

	public function get_token() {
		return get_option( 'toplytics_oauth2_token' );
	}

	public function remove_token() {
		return delete_option( 'toplytics_oauth2_token' );
	}

	public function update_token( $value ) {
		return update_option( 'toplytics_oauth2_token', $value );
	}

	public function get_refresh_token() {
		return get_option( 'toplytics_oauth2_refresh_token' );
	}

	public function remove_refresh_token() {
		return delete_option( 'toplytics_oauth2_refresh_token' );
	}

	public function update_refresh_token( $value ) {
		return update_option( 'toplytics_oauth2_refresh_token', $value );
	}

	/**
	 * This function returns the `auth config` as JSON
	 */
	public function get_auth_config() {
		return get_option( 'toplytics_auth_config', '' );
	}

	public function remove_auth_config() {
		return delete_option( 'toplytics_auth_config' );
	}

	/**
	 * Get Google Analytics statistics using `gapi`
	 * Needs `profile_id`
	 */
	private function _get_analytics_data() {
		$optParams = array(
			'quotaUser'   => md5( home_url() ),
			'dimensions'  => 'ga:pagePath',
			'sort'        => '-ga:pageviews',
			'max-results' => $this::MAX_RESULTS,
		);
		$result = array();
		$profile_id = $this->_get_profile_id();
		if ( ! empty( $profile_id ) ) {
			foreach ( $this->ranges as $when => $start_date ) {
				$filters = apply_filters( 'toplytics_analytics_filters', '', $when, 'ga:pageviews' );
				if ( ! empty( $filters ) ) {
					$optParams['filters'] = $filters;
				}
				$data = $this->service->data_ga->get( 'ga:' . $profile_id, $start_date, date_i18n( 'Y-m-d', time() ), 'ga:pageviews', $optParams );
				apply_filters( 'toplytics_analytics_data', $when, $data->selfLink, $data->modelData['query'], $data->modelData['profileId'] );
				$result[ $when ] = array();
				if ( $data->rows ) {
					foreach ( $data->rows as $item ) {
						$result[ $when ][ $item[0] ] = $item[1]; // result[ when ][ pagepath ] = pageviews
					}
				}
				apply_filters( 'toplytics_analytics_data_result', $result[ $when ], $when );
			}
		}
		return apply_filters( 'toplytics_analytics_data_allresults', $result );
	}

	/**
	 * Remove rel_path with `preview=true` parameter
	 */
	public function filter_rel_path( $rel_path ) {
		if ( false === strpos( $rel_path, '&preview=true' ) ) {
			return $rel_path;
		}
		return '';
	}

	/**
	 * Use the GA data to retrieve WordPress post data
	 */
	private function _convert_data_to_posts( $data, $when ) {
		$new_data = array();
		foreach ( $data as $rel_path => $pageviews ) {
			$rel_path = apply_filters( 'toplytics_rel_path', $rel_path, $when );
			$url      = home_url() . $rel_path;
			$post_id  = url_to_postid( $url );
			$url      = apply_filters( 'toplytics_convert_data_url', $url, $when, $post_id, $rel_path, $pageviews );
			if ( ( 0 < $post_id ) && ( 'post' == get_post_type( $post_id ) ) ) {
				$post = get_post( $post_id );
				if ( is_object( $post ) ) {
					if ( isset( $new_data[ $post_id ] ) ) {
						$new_data[ $post_id ] += $pageviews;
					} else {
						$new_data[ $post_id ] = (int) $pageviews;
					}
				}
			}
		}
		arsort( $new_data ); // sort the results (revert order)
		return apply_filters( 'toplytics_convert_data_to_posts', $new_data, $data, $when );
	}

	/**
	 * Update Toplytics data collected from Analytics
	 */
	public function update_analytics_data() {
		try {
			$data = $this->_get_analytics_data();
		} catch ( Exception $e ) {
			if ( 401 == $e->getCode() ) {
				$this->disconnect( 'Invalid Credentials' );
			}
			error_log( 'Cannot update Google Analytics data[' . $e->getCode() . ']: '. $e->getMessage(), E_USER_ERROR );
			return false;
		}

		$is_updated = false;
		foreach ( $data as $when => $stats ) {
			if ( is_array( $stats ) && ! empty( $stats ) ) {
				$result['result'] = $this->_convert_data_to_posts( $stats, $when );
				$result['_ts'] = time();
				update_option( "toplytics_result_$when", $result );
				$is_updated = true;
			}
		}

		return $is_updated;
	}

	/**
	 * Get Toplytics result from DB
	 */
	public function get_result( $when = 'today' ) {
		$toplytics_result = get_option( "toplytics_result_$when", array() );

		return $toplytics_result['result'];
	}

	/**
	 * Return Toplytics data as a json file
	 */
	public function json_data() {
		header( 'Content-Type: application/json' );
		$post_data = array();
		foreach ( array_keys( $this->ranges ) as $when ) {
			$result = $this->get_result( $when );
			if ( ! empty( $result ) ) {
				foreach ( $result as $post_id => $pageviews ) {
					$data = array(
						'permalink' => get_permalink( $post_id ),
						'title'     => get_the_title( $post_id ),
						'post_id'   => (int) $post_id,
						'views'     => (int) $pageviews,
					);
					$post_data[ $when ][] = apply_filters( 'toplytics_json_data', $data, $post_id, $when );
				}
			}
		}
		$json_data = apply_filters( 'toplytics_json_all_data', $post_data, $when );
		echo json_encode( $json_data, JSON_FORCE_OBJECT );
		die();
	}

	/**
	 * This function returns TRUE if $config is valid, otherwise return FALSE
	 */
	public function is_valid_auth_config( $config = '' ) {
		if ( empty( $config ) ) { $config = $this->get_auth_config(); }

		if ( ! is_string( $config ) ) { return false; }

		$config = json_decode( $config, true );
		if ( ! is_array( $config ) ) { return false; }
		if ( empty( $config['installed'] ) ) { return false; }

		$config = $config['installed'];
		if ( empty( $config['client_id'] ) || empty( $config['client_secret'] ) ) { return false; }
		if ( empty( $config['auth_uri'] ) || empty( $config['token_uri'] ) ) { return false; }

		return true;
	}

	/**
	 * This function load the $config
	 */
	public function load_auth_config( $config ) {
		if ( empty( $config ) || ! is_string( $config ) ) { return false; }

		update_option( 'toplytics_auth_config', $config );
		return true;
	}

	/**
	 * This function show up the auth config data as a HTML table
	 */
	public function show_auth_config() {
		$auth_config = json_decode( $this->get_auth_config(), true );
		$auth_config = $auth_config['installed'];
		if ( ! empty( $auth_config ) ) {
			?>
			<table><tbody>
			<tr><th>client_id:</th><td><?php echo $auth_config['client_id']; ?></td></tr>
			<tr><th>client_secret:</th><td><?php echo $auth_config['client_secret']; ?></td></tr>
			<tr><th>auth_uri:</th><td><?php echo $auth_config['auth_uri']; ?></td></tr>
			</tbody></table>
			<?php
		} else {
			_e( 'Every application has to be registered with the Google API so that we can use the OAuth 2.0 token during the authentication and authorisation process. To get help with this, please go to the Help menu (upper-right corner).', 'toplytics' );
		}
	}
}
global $toplytics;
$toplytics = new Toplytics();

require_once __DIR__ . '/backward-compatibility.php';
require_once __DIR__ . '/inc/class-toplytics-help.php';
require_once __DIR__ . '/inc/class-toplytics-menu.php';
require_once __DIR__ . '/inc/class-toplytics-admin.php';
require_once __DIR__ . '/inc/class-toplytics-submenu-configure.php';
require_once __DIR__ . '/inc/class-toplytics-submenu-settings.php';
require_once __DIR__ . '/inc/class-toplytics-wp-widget.php';
require_once __DIR__ . '/inc/class-toplytics-shortcode.php';
require_once __DIR__ . '/inc/class-toplytics-update.php';
