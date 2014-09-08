<?php

require_once( 'class-toplytics-auth.php' );

class Toplytics_Statistics {
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

	static function filter_all_posts( $return_values, &$results, $name ) {
		foreach ( $return_values as $index => $value ) {
			$link    = home_url() . $index;
			$post_id = url_to_postid( $link );

			if ( 'post' == get_post_type( $post_id ) ) { // filter all posts
				$post = get_post( $post_id );
				if ( TOPLYTICS_ADD_PAGEVIEWS && $post && isset( $results[ $name ][ $post_id ] ) ) {
					$results[ $name ][ $post_id ] += $value;
				} else {
					$results[ $name ][ $post_id ] = $value;
				}
			}
		}
	}

	static function get_results() { // Loading all that's required
		require_once 'lib/gapi.oauth.class.php'; // GAPI code

		global $ranges;
		$results = array( '_ts' => time() );

		try {
			foreach ( $ranges as $name => $start_date ) {
				$curl_handler = curl_init();
				$url          = Toplytics_Auth::get_api_url( $start_date );
				$auth_header  = Toplytics_Auth::auth_process( $url );

				toplytics_log( basename( __FILE__ ) . '|' . __LINE__ . ": \$url -> '" . $url );

				curl_setopt( $curl_handler, CURLOPT_URL, $url );
				curl_setopt( $curl_handler, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $curl_handler, CURLOPT_HTTPHEADER, $auth_header );
				curl_setopt( $curl_handler, CURLOPT_RETURNTRANSFER, 1 );

				$curl_handler_result = curl_exec( $curl_handler );

				if ( curl_errno( $curl_handler ) ) {
					error_log( 'file:' . __FILE__ . ' line:' . __LINE__ . ' >>> CURL ERROR >>> ' . curl_errno( $curl_handler ) );
					return ;
				}

				curl_close( $curl_handler );

				$xml           = simplexml_load_string( $curl_handler_result );
				$return_values = Toplytics_Statistics::get_result_from_xml( $xml );
				Toplytics_Statistics::filter_all_posts( $return_values, $results, $name );

				if ( empty( $results[ $name ] ) ) { continue; }

				if ( is_array( $results[ $name ] ) ) {
					arsort( $results[ $name ] );
					$results[ $name ] = array_slice( $results[ $name ], 0, TOPLYTICS_MAX_POSTS, true );
				}
			} // end foreach ( $ranges as $name...
		} catch ( Exception $e ) {
			error_log( 'Exception >>> ' . $e );
			return $results;
		}

		if ( 1 < count( $results ) ) {
			set_transient( 'toplytics.cache', $results );
		} else {
			$results = get_transient( 'toplytics.cache' ); // Actual data, cached if possible
		}

		toplytics_log( basename( __FILE__ ) . '|' . __LINE__ . ': $results -> ' . print_r( $results, true ) );

		return $results;
	}
}
