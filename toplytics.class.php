<?php
class Toplytics{

	static function ga_statistics() { // Loading all that's required
		require_once 'gapi.class.php'; // The code

	  	// The credentials
		$options = get_option('toplytics_options');		
		$ga_email = $options['text_username'];
		$ga_profile_id = $options['text_account'];
		$ga_token = $options['text_token'];

	  	$results = get_transient('gapi.cache'); // Actual data, cached if possible
	  	if ($results && $results['_ts'] + 1800 > time()) { return $results; }

	  	$ranges = array(
			'today' => date('Y-m-d',strtotime('yesterday')),
			'week' => date('Y-m-d',strtotime('-7 days')),
			'month' => date('Y-m-d',strtotime('-30 days'))
	  	);
	  	$results = array('_ts' => time());

	  	try {
			$token_need_refresh = true;
			$ga = new gapi(null, null, $ga_token); // new gapi($ga_email,$ga_password);
			$time_stamp = time();
			foreach ($ranges as $name => $start_date) {
				$ga->requestReportData($ga_profile_id,array('pagePath'),
									   array('pageviews'),array('-pageviews'),'',$start_date,date('Y-m-d'));
				foreach ($ga->getResults() as $result) {
					$token_need_refresh = false;
					$post_id = url_to_postid((string)$result);
					$post = get_post($post_id);
					if ($post && 'post' == $post->post_type) {
						$results[$name][$post_id] = $result->getPageviews();
					}

					if ( $token_need_refresh ) {
						$new_options = array();
						$new_options['text_username'] = $ga_email;
						$new_options['text_account'] = $ga_profile_id;
						$data_array = array('option_value' => serialize($new_options) );
						$where_array = array('option_name' => 'toplytics_options');
						global $wpdb;
						$wpdb->update( $wpdb->prefix . 'options', $data_array, $where_array );
					}
				}
			}
		} catch (Exception $e) {
			set_transient('gapi.cache',$results);
			return $results;
		}
		set_transient('gapi.cache',$results);

		return $results;
	}
}
