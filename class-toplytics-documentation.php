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
		<p>Here is a simple example:</p>
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
...
</pre></code>

		<h3>3. How to use custom template?</h3>
		<p>To use your custom template just copy and paste the file `toplytics-template.php` from toplytics plugin directory to your theme directory.<br /><br />
Then you can customize your template. The plugin first search for the file `toplytics-template.php` into theme directory and then search into plugin directory, in this case your custom template from theme structure will be visible first.</p>

<?php
	}
}
