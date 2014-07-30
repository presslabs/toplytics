<?php require_once( dirname( __FILE__ ) . '/OAuth.php' );

$dimensions = array( 'ga:pagePath' );

class Toplytics_Auth {
	function Toplytics_Auth() {
		$this->__construct();
	}

	function __construct() {
		add_action( 'admin_init', array( &$this, 'admin_handle_oauth_login_header' ) );
	}

	static function auth_process( $url ) {
		$oauth_token      = get_option( 'toplytics_oauth_token' );
		$oauth_secret     = get_option( 'toplytics_oauth_secret' );
		$request_type     = 'GET';
		$signature_method = new GADOAuthSignatureMethod_HMAC_SHA1();

		$params    = array();
		$consumer  = new GADOAuthConsumer( 'anonymous', 'anonymous', NULL );
		$token     = new GADOAuthConsumer( $oauth_token, $oauth_secret );
		$oauth_req = GADOAuthRequest::from_consumer_and_token( $consumer, $token, $request_type, $url, $params );

		$oauth_req->sign_request( $signature_method, $consumer, $token );

		return array( $oauth_req->to_header() );
	}

	static function get_result_from_xml( $xml ) {
		global $dimensions;
		$return_values = array();
		foreach ( $xml->entry as $entry ) {
			if ( '' == $dimensions ) {
				$dim_name = 'value';
			} else {
				$dimension            = $entry->xpath( 'dxp:dimension' );
				$dimension_attributes = $dimension[0]->attributes();
				$dim_name             = (string) $dimension_attributes['value'];
			}

			$metric = $entry->xpath( 'dxp:metric' );
			if ( 1 < sizeof( $metric ) ) {
				foreach ( $metric as $single_metric ) {
					$metric_attributes = $single_metric->attributes();
					$return_values[ $dim_name ][ (string) $metric_attributes['name'] ] = (string) $metric_attributes['value'];
				}
			} else {
				$metric_attributes = $metric[0]->attributes();
				$return_values[ $dim_name ] = (string) $metric_attributes['value'];
			}
		}
		return $return_values;
	}

	static function get_api_url( $start_date ) {
		global $dimensions;

		$account_id = get_option( 'toplytics_account_id' );
		$base_url   = 'https://www.googleapis.com/analytics/v2.4/';
		$metrics    = array( 'ga:pageviews' );
		$sort       = array( '-ga:pageviews' );

		$url  = $base_url . 'data' . '?ids=' . $account_id;
		$url .= sizeof( $dimensions ) > 0 ? ( '&dimensions=' . join( array_reverse( $dimensions ), ',' ) ) : '';
		$url .= sizeof( $metrics ) > 0 ? ( '&metrics=' . join( $metrics, ',' ) ) : '';
		$url .= sizeof( $sort ) > 0 ? '&sort=' . join( $sort, ',' ) : '';
		$url .= '&start-date=' . $start_date . '&end-date=' . date( 'Y-m-d' ) . '&max-results=' . TOPLYTICS_GET_MAX_RESULTS;

		return $url;
	}

	static function ga_statistics() { // Loading all that's required
		require_once 'gapi.oauth.class.php'; // GAPI code

		$results = get_transient( 'toplytics.cache' ); // Actual data, cached if possible

		global $ranges;
		$results = array( '_ts' => time() );

		try {
			foreach ( $ranges as $name => $start_date ) {
				$ch          = curl_init();
				$url         = Toplytics_Auth::get_api_url( $start_date );
				$auth_header = Toplytics_Auth::auth_process( $url );

				if ( defined( TOPLYTICS_DEBUG_MODE ) )
					error_log( 'TOPLYTICS(' . basename( __FILE__ ) . '|' . __LINE__ . ") \$url -> '" . $url . "'\n\n" );

				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $auth_header );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

				$ch_result = curl_exec( $ch );

				if ( curl_errno( $ch ) ) {
					error_log( 'file:' . __FILE__ . ' line:' . __LINE__ . ' >>> CURL ERROR >>> ' . curl_errno( $ch ) );
					return ;
				}

				curl_close( $ch );

				$xml           = simplexml_load_string( $ch_result );
				$return_values = Toplytics_Auth::get_result_from_xml( $xml );

				foreach ( $return_values as $index => $value ) {
					$link    = home_url() . $index;
					$post_id = url_to_postid( $link );

					if ( 'post' == get_post_type( $post_id ) ) { // filter all posts
						$post = get_post( $post_id );
						if ( TOPLYTICS_ADD_PAGEVIEWS && $post && isset( $results[ $name ][ $post_id ] ) )
							$results[ $name ][ $post_id ] += $value;
						else
							$results[ $name ][ $post_id ] = $value;
					}
				}
				if ( is_array( $results[ $name ] ) ) {
					arsort( $results[ $name ] );
					$results[ $name ] = array_slice( $results[ $name ], 0, TOPLYTICS_MAX_POSTS, true );
				}
			} // end foreach ( $ranges as $name...
		} catch ( Exception $e ) {
			error_log( 'Exception >>> ' . $e );
			return $results;
		}
		set_transient( 'toplytics.cache', $results );
		if ( defined( TOPLYTICS_DEBUG_MODE ) )
			error_log( 'TOPLYTICS(' . basename( __FILE__ ) . '|' . __LINE__ . ') $results -> ' . print_r( $results, true ) . "\n\n" );

		return $results;
	}

	/**
	 *  We have to catch the oauth login data in admin_init so http headers can be added
	 */
	function admin_handle_oauth_login_header() {
		if ( isset( $_POST['SubmitLogin'] ) && isset( $_POST['toplytics_login_type'] ) && 'oauth' == $_POST['toplytics_login_type'] ) {
			$this->admin_handle_oauth_login_options();
		} else if ( isset( $_REQUEST['oauth_return'] ) ) {
			$this->admin_handle_oauth_complete();
		}
	}

	function admin_handle_oauth_login_options() {
		// Step one in the oauth login sequence is to grab an anonymous token
		delete_option( 'toplytics_oa_anon_token' );
		delete_option( 'toplytics_oa_anon_secret' );

		$signature_method = new GADOAuthSignatureMethod_HMAC_SHA1();
		$params = array();

		$params['oauth_callback']     = toplytics_get_admin_url( '/options-general.php' ) . '?page=toplytics/toplytics.php&oauth_return=true';
		$params['scope']              = 'https://www.googleapis.com/auth/analytics.readonly'; // This is a space seperated list of applications we want access to
		$params['xoauth_displayname'] = 'Analytics Dashboard';

		$consumer = new GADOAuthConsumer( 'anonymous', 'anonymous', NULL );
		$req_req  = GADOAuthRequest::from_consumer_and_token( $consumer, NULL, 'GET', 'https://www.google.com/accounts/OAuthGetRequestToken', $params );

		$req_req->sign_request( $signature_method, $consumer, NULL );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $req_req->to_url() );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		$oa_response = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			$error_message = curl_error( $ch );
			$info_redirect = toplytics_get_admin_url( '/options-general.php' ) . '?page=toplytics/toplytics.php&error_message=' . urlencode( $error_message );
			header( 'Location: ' . $info_redirect );
			die();
		}

		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		if ( 200 == $http_code ) {
			$access_params = $this->split_params( $oa_response );

			add_option( 'toplytics_oa_anon_token', $access_params['oauth_token'] );
			add_option( 'toplytics_oa_anon_secret', $access_params['oauth_token_secret'] );

			header( 'Location: https://www.google.com/accounts/OAuthAuthorizeToken?oauth_token=' . urlencode( $access_params['oauth_token'] ) );
		} else {
			$info_redirect = toplytics_get_admin_url( '/options-general.php' ) . '?page=toplytics/toplytics.php&error_message=' . urlencode( $oa_response );
			header( 'Location: ' . $info_redirect );
		}

		die();
	}

	function admin_handle_oauth_complete_redirect( $oa_response, $http_code ) {
		delete_option( 'toplytics_oa_anon_token' );
		delete_option( 'toplytics_oa_anon_secret' );

		if ( 200 == $http_code ) {
			$access_params = $this->split_params( $oa_response );

			update_option( 'toplytics_oauth_token', $access_params['oauth_token'] );
			update_option( 'toplytics_oauth_secret', $access_params['oauth_token_secret'] );
			update_option( 'toplytics_auth_token', 'toplytics_see_oauth' );

			$info_redirect = toplytics_get_admin_url( '/options-general.php' )
				. '?page=toplytics/toplytics.php&info_message='
				. urlencode( 'Authenticated!' );

			header( 'Location: ' . $info_redirect );
		} else {
			$info_redirect = toplytics_get_admin_url( '/options-general.php' )
				. '?page=toplytics/toplytics.php&error_message='
				. urlencode( $oa_response );

			header( 'Location: ' . $info_redirect );
		}
		die();
	}

	function admin_handle_oauth_complete() { // step two in oauth login process
		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) )
			die( __( 'Cheatin&#8217; uh?' ) );

		$signature_method = new GADOAuthSignatureMethod_HMAC_SHA1();
		$params = array();

		$params['oauth_verifier'] = $_REQUEST['oauth_verifier'];

		$consumer      = new GADOAuthConsumer( 'anonymous', 'anonymous', NULL );
		$upgrade_token = new GADOAuthConsumer( get_option( 'toplytics_oa_anon_token' ), get_option( 'toplytics_oa_anon_secret' ) );

		$acc_req = GADOAuthRequest::from_consumer_and_token( $consumer, $upgrade_token, 'GET', 'https://www.google.com/accounts/OAuthGetAccessToken', $params );

		$acc_req->sign_request( $signature_method, $consumer, $upgrade_token );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $acc_req->to_url() );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		$oa_response = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			$error_message = curl_error( $ch );
			$info_redirect = toplytics_get_admin_url( '/options-general.php' )
				. '?page=toplytics/toplytics.php&error_message='
				. urlencode( $error_message );

			header( 'Location: ' . $info_redirect );
			die();
		}

		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$this->admin_handle_oauth_complete_redirect( $oa_response, $http_code );
	}

	function split_params( $response ) {
		$params = array();
		$param_pairs = explode( '&', $response );
		foreach ( $param_pairs as $param_pair ) {
			if ( '' == trim( $param_pair ) ) { continue; }
			list( $key, $value ) = explode( '=', $param_pair );
			$params[ $key ] = urldecode( $value );
		}
		return $params;
	}
}
