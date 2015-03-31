=== Toplytics ===
Contributors: PressLabs, olarmarius
Donate link: http://www.presslabs.com/
Tags: presslabs, analytics, posts, top, most visited, most viewed posts, top content, toplytics, popular, google analytics, high traffic, popular posts, oauth, server resources, settings, widget, embed code, javascript, json, json file, simple, post views
Requires at least: 3.9
Tested up to: 4.1
Stable tag: 3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Displays the most visited posts as a widget using data from Google Analytics. Designed to be used under high-traffic or low server resources in a cache friendly manner.

== Description ==
This plugin displays the most visited posts as a widget using data extracted from Google Analytics. It is designed to work with high-traffic sites and all types of caching.

= IMPORTANT! =
You need to have Google Analytics active on your site if you want to use this plugin!

= Features: =
* Connection with Google Analytics Account using OAuth method;
* Starting with the plugin version 3.0 we have switched to GA API v3
* Widget displaying most visited posts as simple links;
* The widget can display the most viewed articles from the past day, week, two weeks or month;
* You can set the number of posts to be displayed between 1 and 1000;
* It can also display the number of views as counted by Google Analytics;
* i18n support/translation requests are more than welcome;
* Generate the list of the most viewed posts dynamicaly with JavaScript;
* custom template for displaying the widget is available and should be included in the active theme folder;
* You can use some of the plugin's functions if the above are not enough for your customization needs. Check FAQ for details;
* Shortcodes are now supported for easier integration into posts/pages or other widgets. Check FAQ for details;

== Installation ==

= Installation =
1. Upload *toplytics.zip* to the */wp-content/plugins/* directory;
2. Extract the *toplytics.zip* archive into the */wp-content/plugins/* directory;
3. Activate the plugin through the *Plugins* menu in WordPress.

Alternatively, go into your WordPress dashboard and click on *Plugins -> Add Plugin* and search for Toplytics. Then click on *Install*, then on *Activate Now*.

= Configuration step 1 =
In this step please register client application with Google. To register an application please login to the Google account and go to Google API console.
1. Create a New Project(set a unique project name and id);
2. Enable the Analytics API in order to be accessed;
3. From the APIs → Credentials tab create an OAuth 2.0 Client ID;
3.1. Select application type as “Installed application”;
3.2. Create Branding information for Client ID by editing the consent screen;
4. Download the JSON file with API credentials(Auth Config file);
5. Upload this file in order to make a proper Configuration.

= Configuration step 2 =
In this step please connect to your Google Analytics Account.
1. Click the 'Get Authorization Key' button and you will be redirected to google.com;
2. After logging in you will receive a key;
3. Then come back to this page and use the key in the 'Authorization Key' field, and then click 'Get Analytics Profiles' button.

= Usage =
Connect your plugin with Google Analytics Account from the Settings page (*Settigns -> Toplytics*);
Use the *Toplytics* widget from the *Appearance->Widgets* page;

== Frequently Asked Questions ==

= Why should I use this plugin? =
You should use this plugin if you want to display the most visited posts of your site in a safe and stable manner, with no risk of downtime or slowness, based on data from Google Analytics statistics. The plugin is built for high-traffic sites where counting every visitor's click loads up the DB and can potentially crash the site.

= How often is the data from Google Analytics refreshed? =
The data from GA is refreshed every hour. During this interval, the information is safely stored using transients and options.

= How to use the custom template? =
To use a custom template you just need to copy the file `toplytics-template.php` from toplytics' plugin folder to your theme folder.

You can then customize your template. The plugin will first search for the file `toplytics-template.php` in the active theme folder, and, if that's not found, it will search for it in the plugin folder. The custom template from the theme folder has priority over the one in the plugin folder.

= How can I use the shortcode? =
The shortcode has 3 parameters: period -> default=month (today/week/2weeks/month), numberposts -> default=5 (min=1/max=1000), showviews -> default=false (true/false)

Shortcode example:

`[toplytics period="week" numberposts="3" showviews="true"]`

The shortcode can be used within post/pages and in other widgets from the sidebar. For any parameter that is not used, the default value will be used.

= How can I use JavaScript code in order to show up the top in widget? =

You can place the JavaScript code right in `toplytics-template.php` file or use a predefined JS code like this:

`<script type="text/javascript">toplytics_results( toplytics_args  );</script>`

The `toplytics_args` are the options from the current widget passed to the template. For a detailed example see the JavaScript code from `js/toplytics.js` file.

= How can I use the plugin functionality outside the sidebar? =

The plugin offers 2 functions that can be used either in the theme or by another plugin. Please review the complete documentation below.

**1.** `toplytics_results`

**Description**

`mixed toplytics_results ( [ array $args ] )`

toplytics_results() prints the toplytics results in `<ol>` format.

**Parameters**

args -> This parameter is a list of toplytics options:
		period      - represents the statistics period, default=month (today/week/month);
		numberposts - represents the number of posts to be displayed, default=5 (min=1/max=20);
		showviews   - set this parameter to true if you want to print out the number of posts views, default=false (true/false);

**Return Values**

If the toplytics results will be printed, then the function returns TRUE, otherwise the return value is FALSE.

**Example**

Here is a simple example that displays the first 7 most visited posts in the past month, toghether with the number of views:

`<?php
	$toplytics_args = array(
		'period' => 'month',  // default=month (today/week/2weeks/month)
		'numberposts' => 7,   // default=5 (min=1/max=1000)
		'showviews' => true   // default=false (true/false)
	);
	if ( function_exists( 'toplytics_results' ) )
		toplytics_results( $toplytics_args );
?>`


**2.** `toplytics_get_results`

**Description**

`mixed toplytics_get_results ( [ array $args ] )`

toplytics_get_results() returns the toplytics results into an array; in this case, the toplytics results' HTML can be formatted according with your needs.

**Parameters**

args -> This parameter is a list of toplytics options:
		period      - represents the statistics period, default=month (today/week/2weeks/month);
		numberposts - represents the number of posts to be displayed, default=5 (min=1/max=1000);

**Return Values**

If the toplytics results contains at least one element, the function will return an array with the toplytics results, otherwise the return value is FALSE.

**Example**

`<?php
	if ( function_exists( 'toplytics_get_results' ) ) {
		$toplytics_args = array(
			'period' => 'month',  // default=month (today/week/2weeks/month)
			'numberposts' => 3    // default=5 (min=1/max=1000)
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
?>`

The outcome will look like this:

1.) This is the most visited post - 123 Views

2.) This is the second most visited post - 99 Views

3.) This is the third most visited post - 12 Views


= How to use the debug page? =
1.First you must be sure to set the WP_DEBUG constant to `true`.
2.Then go to the plugins list `Plugins->Installed plugins` and click to the `Debug` link founded at Toplytics plugin.
3.View the information for debugging.

== Changelog ==

= 3.0 =

This is a major update and you need to re-authenticate with Google Analytics for the plugin to work!

* Google Analytics API v3.0 is being used from now on
* major refactoring
* removed realtime template - the JS code can now be used only in toplytics-template.php file
* removed filters and actions
filters:
    toplytics_ga_api_url_$name
    toplytics_ga_api_result_xml_$name
    toplytics_ga_api_result_simplexml_$name
action:
    action toplytics_options_general_page
* updated debug page
* added new filters in order to get more information about how the plugin works
    toplytics_disconnect_message
    toplytics_analytics_data
    toplytics_analytics_data_result
    toplytics_analytics_data_allresults
    toplytics_rel_path
    toplytics_convert_data_url
    toplytics_convert_data_to_posts
    toplytics_json_data
    toplytics_json_all_data
* removed Romanian translation


= 2.1.1 =

* fixed a possible infinite loop
* `WP_DEBUG` enables toplytics debug mode


= 2.1 =

* added new filters and actions
filters:
  `toplytics_ga_api_url_$name`
  `toplytics_ga_api_result_xml_$name`
  `toplytics_ga_api_result_simplexml_$name`
action:
  `action toplytics_options_general_page`
* resolved the realtime template issues
* added toplytics debug page
* added `2weeks` in the data range.


= 2.0 =

* Implemented OAuth login method.
* Added `Display posts in real time` option.
* Fixed some display bugs.
* Refactored and cleaned-up the entire plugin code and added some tests.
* Simplified the template syntax.
* The plugin settings page now resides under *Settings* and not *Tools*.
* Added i18n support.
* Added shortcode support.
* Added more information and documentation into Readme.txt.

= 1.2.2 =
* Added custom theme templates support.

= 1.2.1 =
* Add a token to login process.

= 1.2 =
* Added custom templates.

= 1.1 =
* First version on WP.

== Upgrade Notice ==

= 3.0 =
Starting this version, Toplytics uses Google Analytics API v3.0. This is a major update and you need to re-authenticate with Google Analytics for the plugin to work!

= 2.0 =
The option `Display posts in real time` will let you get the results from one JSON file with JavaScript code. All HTML code is generated dynamically and as a result, the SEO will be affected.
Major plugin changes & code rewrite. Added theme custom template, OAuth login, i18n support, shortcode support.
