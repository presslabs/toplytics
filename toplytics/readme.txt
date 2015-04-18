=== Toplytics ===
Contributors: PressLabs, olarmarius
Donate link: http://www.presslabs.com/
Tags: presslabs, analytics, posts, top, most visited, most viewed posts, top content, toplytics, popular, google analytics, high traffic, popular posts, oauth, server resources, settings, widget, embed code, javascript, json, json file, simple, post views
Requires at least: 3.9
Tested up to: 4.1.1
Stable tag: 3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Displays the most visited posts as a widget using data from Google Analytics. Designed to be used under high-traffic or low server resources.

== Description ==
This plugin displays the most visited posts as a widget using data extracted from Google Analytics. It is designed to work with high-traffic sites and all types of caching.

= IMPORTANT! =
You need to have Google Analytics active on your site if you want to use this plugin!

= Features =
* Connection with Google Analytics Account using OAuth 2.0 method;
* Starting with the plugin version 3.0 we have switched to GA API v3
* Offers a widget displaying the most visited posts as simple links (no styling);
* The widget can display the most visited posts from the past day, week or month;
* You can set the number of posts to be displayed between 1 and 250;
* It can also display the number of views as counted by Google Analytics;
* i18n support/translation requests are more than welcome;
* Generate the list of the most visited posts dynamicaly with JavaScript to correctly display them with any caching mechanism/plugin;
* Custom template for displaying the widget is available and should be included in the active theme folder;
* You can use some of the plugin's functions if the above are not enough for your customization needs. Check FAQ for details;
* Shortcodes are now supported for easier integration into posts/pages or other widgets. Check FAQ for details;

== Installation ==

= Installation =
1. Upload *toplytics.zip* to the */wp-content/plugins/* directory;
2. Extract the *toplytics.zip* archive into the */wp-content/plugins/* directory;
3. Activate the plugin through the *Plugins* menu in WordPress.

Alternatively, go into your WordPress dashboard and click on *Plugins -> Add Plugin* and search for Toplytics. Then click on *Install*, then on *Activate Now*.

= Configuration step 1 =
In this step please register a new client application with Google. To register an application please login to your Google account and go to Google API console.

1. Create a New Project (set a unique project name and id);

2. Enable the Analytics API by going to APIs & auth → API → Analytics API and then click on *Enable API*;

3. From the APIs → Credentials tab create an OAuth 2.0 Client ID by clicking on *Create new Client ID*;

4. Select application type as *Installed application*;

5. Create the Branding information for the Client ID by editing the consent screen. It's compulsory to select your e-mail addres and to set a Product name;

6. Create the Client ID by selecting again *Installed application* and *Other*;

7. Download the JSON file with the API credentials (Auth Config file);

8. Upload this file in the plugin Settings page and click on *Upload Auth Config File*.

= Configuration step 2 =
In this step please connect to your Google Analytics Account.

1. Click the *Get Authorization Key* button from the plugin's settings page and you will be redirected to google.com;

2. After logging in you need to agree that the newly created app will access your Analytics data. After that you get a key;

3. Then come back to the plugin settings page and use the key in the *Authorization Key* field. Click on *Get Analytics Profiles* button, select the profile for your current site and click on *Connect*.

= Usage =
Connect your plugin with Google Analytics Account from the Settings page (*Settings -> Toplytics*);
Use the *Toplytics* widget from the *Appearance -> Widgets* page;

== Frequently Asked Questions ==

= Why should I use this plugin? =
You should use this plugin if you want to display the most visited posts of your site in a safe and stable manner, with no risk of downtime or slowness, based on data from Google Analytics statistics. The plugin is built for high-traffic sites where counting every visitor's click loads up the DB and can potentially crash the site.

= How often is the data from Google Analytics refreshed? =
The data from GA is refreshed every hour. During this interval, the information is safely stored using transients and options.

= How to use the custom template? =
To use a custom template you just need to copy the file `toplytics-template.php` from Toplytics' plugin folder to your theme folder.

You can then customize your template. The plugin will first search for the file `toplytics-template.php` in the active theme folder, and, if that's not found, it will search for it in the plugin folder. The custom template from the theme folder has priority over the one in the plugin folder.

= How can I use the shortcode? =
The shortcode has 3 parameters: period -> default=month (today/week/month), numberposts -> default=5 (min=1/max=250), showviews -> default=false (true/false)

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

`mixed toplytics_results( [ array $args ] )`

toplytics_results() prints the toplytics results in `<ol>` format.

**Parameters**

args -> This parameter is a list of toplytics options:
		period      - represents the statistics period, default=month (today/week/month);
		numberposts - represents the number of posts to be displayed, default=5 (min=1/max=250);
		showviews   - set this parameter to true if you want to print out the number of posts views, default=false (true/false);

**Return Values**

If the toplytics results will be printed, then the function returns TRUE, otherwise the return value is FALSE.

**Example**

Here is a simple example that displays the first 7 most visited posts in the past month, toghether with the number of views:

`<?php
	$toplytics_args = array(
		'period' => 'month',  // default=month (today/week/month)
		'numberposts' => 7,   // default=5 (min=1/max=250)
		'showviews' => true   // default=false (true/false)
	);
	if ( function_exists( 'toplytics_results' ) )
		toplytics_results( $toplytics_args );
?>`


**2.** `toplytics_get_results`

**Description**

`mixed toplytics_get_results( [ array $args ] )`

toplytics_get_results() returns the toplytics results into an array; in this case, the toplytics results' HTML can be formatted according with your needs.

**Parameters**

args -> This parameter is a list of toplytics options:
		period      - represents the statistics period, default=month (today/week/month);
		numberposts - represents the number of posts to be displayed, default=5 (min=1/max=250);

**Return Values**

If the toplytics results contains at least one element, the function will return an array with the toplytics results, otherwise the return value is FALSE.

**Example**

`<?php
	if ( function_exists( 'toplytics_get_results' ) ) {
		$toplytics_args = array(
			'period' => 'month',  // default=month (today/week/month)
			'numberposts' => 3    // default=5 (min=1/max=250)
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


= How to change the data ranges? =
You can use the filter `toplytics_ranges` in order to change the default ranges (today/week/month).

**Example 1**

Here is a simple example that adds `year` range:

`<?php
add_filter( 'toplytics_ranges', 'toplytics_add_on_ranges' );
function toplytics_add_on_ranges( $ranges ) {
    $ranges['year'] = date_i18n( 'Y-m-d', strtotime( '-364 days' ) );
    return $ranges;
}
?>`

**Example 2**

Here is a simple example that removes all ranges except `month`:

`<?php
add_filter( 'toplytics_ranges', 'toplytics_add_on_ranges' );
function toplytics_add_on_ranges( $ranges  ) {
    if ( ! empty( $ranges['month'] ) ) {
        $new_ranges['month'] = $ranges['month'];
        return $new_ranges;
    }
    return $ranges;
}
?>`

= What is `toplytics.json` file? =
This file contains the statistics in JSON format, and is designed to be used with the JS custom template code.

= Where is `toplytics.json` file located? =
The file `toplytics.json` is located to the root folder of the site.

**Example**

If the site domain is `http://www.example.com/` then the file url is `http://www.example.com/toplytics.json`.

== Screenshots ==

1. Output of the top most visited posts from last month.

== Changelog ==

= 3.0 =

**This is a major update and you need to re-authenticate with Google Analytics for the plugin to work!**

* Google Analytics API v3.0 is being used from now on
* major code refactoring
* removed realtime template - the JS code can now be used directly in toplytics-template.php file
* removed filters and actions
filters:
    toplytics_ga_api_url_$name
    toplytics_ga_api_result_xml_$name
    toplytics_ga_api_result_simplexml_$name
action:
    action toplytics_options_general_page
* removed debug page
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
* removed `2weeks` from the data range.
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
Starting with version 3.0, Toplytics uses Google Analytics API v3.0. This is a major update and you need to re-authenticate with Google Analytics for the plugin to work!

= 2.0 =
The option `Display posts in real time` will let you get the results from one JSON file with JavaScript code. All HTML code is generated dynamically.
Major plugin changes & code rewrite. Added theme custom template, OAuth login, i18n support, shortcode support.
