<?php
class Toplytics{

	static function ga_statistics() { // Loading all that's required
		require_once 'gapi.class.php'; // The code

	  	// The credentials
		$options = get_option('toplytics_options');		
		$ga_email = $options['text_username'];
		$ga_password = $options['text_pass'];
		$ga_profile_id = $options['text_account'];

	  	$results = get_transient('gapi.cache'); // Actual data, cached if possible
	  	if ($results && $results['_ts'] + 1800 > time()) { return $results; }

	  	$ranges = array(
			'today' => date('Y-m-d',strtotime('yesterday')),
			'week' => date('Y-m-d',strtotime('-7 days')),
			'month' => date('Y-m-d',strtotime('-30 days'))
	  	);
	  	$results = array('_ts' => time());

	  	try {
			$ga = new gapi($ga_email,$ga_password);

			// check if GA settings are correct
		  /*if ( $ga->getAuthToken()==null ) {
				return null;
		  }*/

			foreach ($ranges as $name => $start_date) {
				$ga->requestReportData($ga_profile_id,array('pagePath'),array('pageviews'),array('-pageviews'),'',$start_date,date('Y-m-d'));
				foreach ($ga->getResults() as $result) {
					$post_id = url_to_postid((string)$result);
					$post = get_post($post_id);
					if ($post && 'post' == $post->post_type) $results[$name][$post_id] = $result->getPageviews();
				}
			}
		} catch (Exception $e) {
			set_transient('gapi.cache',$results,300);
			return $results;
		}
		set_transient('gapi.cache',$results,48 * 1800);

		return $results;
	}
}
