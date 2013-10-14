<?php

class Toplytics_Documentation {
	function Toplytics_Documentation() {
		$this->__construct();
	}

//------------------------------------------------------------------------------
	function __construct() {
    		$this->load();
	}

//------------------------------------------------------------------------------
	function load() {
?>
		<h2>IMPORTANT!</h2>
		<p>Please configure your plugin options in Tools->Toplytics.</p>

		<h2>Installation</h2>
		<ol>
			<li>Upload `toplytics.zip` to the `/wp-content/plugins/` directory;</li>
			<li>Extract the `toplytics.zip` archive into the `/wp-content/plugins/` directory;</li>
			<li>Activate the plugin through the 'Plugins' menu in WordPress.</li>
		</ol>

		<h2>Frequently Asked Questions</h2>

		<h3>1. Why should I use this plugin?</h3>
		<p>You should use this plugin if you want to display the most visited posts of your site, from Google Analytics statistics.</p>

		<h3>2. How can I use the plugin functionality outside the sidebar?</h3>
		<p>Simple example:</p>
<code><pre>
	$toplytics_args = array(
		'period' => 'month',  // default=month (today/week/month)
		'numberposts' => 7,   // default=5 (min=1/max=20)
		'showviews' => true   // default=false (true/false)
	);
	if ( function_exists( 'toplytics_results' ) )
		toplytics_results( $toplytics_args );
</pre></code>

		<p>or you can customize your HTML code:</p>

<code><pre>
	if ( function_exists( 'toplytics_get_results' ) ) {
		$toplytics_args = array(
			'period' => 'month',  // default=month (today/week/month)
			'numberposts' => 7    // default=5 (min=1/max=20)
		);
		$toplytics_results = toplytics_get_results( $toplytics_args );
		if ( $toplytics_results ) {
			$k = 0;
			foreach ( $toplytics_results as $post_id => $post_views ) {
				echo (++$k) . ') <a href="' . get_permalink( $post_id ) 
					. '" title="' . esc_attr( get_the_title( $post_id ) ) . '">' 
					. get_the_title( $post_id ) . '</a> - ' . $post_views . ' Views<br />';
			}
		}
	}
</pre></code>

		<h3>3. How to use custom template?</h3>
		<p>To use your custom template just copy and paste the file `toplytics-template.php` from the toplytics plugin directory to your theme directory.<br /><br />
The plugin will search first in `toplytics-template.php` file from the theme directory and then will search in the plugin directory, in this case your custom template from the theme structure will be visible first.</p>

<?php
	}
}
