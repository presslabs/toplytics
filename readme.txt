=== Toplytics ===
Contributors: PressLabs
Donate link: http://www.presslabs.com/
Tags: presslabs, analytics, posts, top, most visited, most viewed posts, top content, toplytics
Requires at least: 3.5
Tested up to: 3.6.1
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Displays the most visited posts as a widget using data from Google Analytics. Designed to be used under high-traffic or low server resources.

== Description ==
This plugin displays the most visited posts as a widget using data extracted from Google Analytics. It is designed to work with high-traffic sites and all types of caching.

= IMPORTANT! =
You need to have Google Analytics active on your site for this plugin to work!

= Features: =
* Connection with Google Analytics Account using OAuth method; the plugin doesn't store any passwords or account details;
* Widget displaying most visited posts as simple links (*Most Visited Posts*);
* The widget can display the most viewed articles from the past day, week or month;
* You can set the number of posts to be displayed between 1 and 20;
* It can also display the number of views as counted by Google Analytics;
* i18n support, requests for translations are more than welcomed;
* **New:** custom templates for displaying the widget are now available and should be included in the active theme folder;
* **New:** the plugin has a set of filters & hooks to be used from the theme files;
* **New:** you can use some of the plugin's functions if the above are not enough for your customization needs. Check FAQ for details;
* **New:** shortcodes are now supported for easier integration into posts/pages or other widgets. Check FAQ for details;

== Installation ==

= Installation =
1. Upload *toplytics.zip* to the */wp-content/plugins/* directory;
2. Extract the *toplytics.zip* archive into the */wp-content/plugins/* directory;
3. Activate the plugin through the *Plugins* menu in WordPress.

Alternatively go into your WordPress dashboard and click on *Plugins -> Add Plugin* and search for Toplytics. Then click on *Install*, then on *Activate Now*.


= Usage =
Connect your plugin with Google Analytics Account from the Settings page (*Settigns -> Toplytics*);
Use the *Most Visited Posts* widget from the *Appearance->Widgets* page;

== Frequently Asked Questions ==

= Why should I use this plugin? =
You should use this plugin if you want to display the most visited posts of your site in a safe and stable way, with no risk of downtime or slowness, based on data from Google Analytics statistics. The plugin is built for high-traffic sites where counting every visitor's click loads up the DB and can potentially crash the site.

= How often is the data from Google Analytics refreshed? =
The data from GA is refreshed every hour. During this interval the information is safely stored using transients.

= How to use the custom template? =
To use a custom template you just need to copy the file `toplytics-template.php` from toplytics' plugin folder to your theme folder.

Then you can customize your template. The plugin will first search for the file `toplytics-template.php` in the active theme folder and if that's not found it will search in the plugin folder. The custom template from the theme folder has priority over the one in the plugin folder.

= How can I use the shortcode? =
The shortcode has 3 parameters: period -> default=month (today/week/month), numberposts -> default=5 (min=1/max=20), showviews -> default=false (true/false)

Shortcode example:

`[toplytics period="week" numberposts="3" showviews="true"]`

The shortcode can be used within post/pages and in other widgets from the sidebar. For any parameter that is not used, the default value will be used.

= How can I use the plugin functionality outside the sidebar? =

The plugin offers 2 functions that can be used either in the theme or by another plugin. Please find below the complete documentation.

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

If the toplytics results will be printed, then the function returns TRUE otherwise the return value is FALSE.

**Example**

Here is a simple example that displays the first 7 most visited posts in the past month, toghether with the number of views:

`<?php 
	$toplytics_args = array(
		'period' => 'month',  // default=month (today/week/month)
		'numberposts' => 7,   // default=5 (min=1/max=20)
		'showviews' => true   // default=false (true/false)
	);
	if ( function_exists( 'toplytics_results' ) )
		toplytics_results( $toplytics_args );
?>`


**2.** `toplytics_get_results`

**Description**

`mixed toplytics_get_results ( [ array $args ] )`

toplytics_get_results() returns the toplytics results into an array; in this case the toplytics results' HTML can be formatted according with your needs.

**Parameters**

args -> This parameter is a list of toplytics options:
		period      - represents the statistics period, default=month (today/week/month);
		numberposts - represents the number of posts to be displayed, default=5 (min=1/max=20);

**Return Values**

If the toplytics results contains at least one element, the function will return an array with the toplytics results, otherwise the return value is FALSE.

**Example**

`<?php
	if ( function_exists( 'toplytics_get_results' ) ) {
		$toplytics_args = array(
			'period' => 'month',  // default=month (today/week/month)
			'numberposts' => 3    // default=5 (min=1/max=20)
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


== Changelog ==

= 1.4 =
* Removed multiple templates support. You can use only one custom template placed in your theme folder.
* Fixed some display bugs.
* Refactored and cleaned-up the entire plugin code
* Simplified the template syntax.
* The plugin settings page stays now under *Settings* and not *Tools*
* Added more filters and hooks to be used inside theme/plugins
* Added i18n support.
* Added shortcode support.
* Added more information and documentation into Readme.txt

= 1.3 =
* Implemented OAuth login method.

= 1.2.2 =
* Added custom theme templates support.

= 1.2.1 =
* GA password is used to generate a token, and the token is saved in the database.

= 1.2 =
* Added custom templates.

= 1.1 =
* First version on WP.

== Upgrade Notice ==

= 1.4 =
Major plugin changes & code rewrite. Added theme custom templates, OAuth login, i18n support, shortcode support, filter & hooks.
