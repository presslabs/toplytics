=== Toplytics ===
Contributors: PressLabs
Donate link: http://www.presslabs.com/
Tags: presslabs, analytics, posts, top, most visited, toplytics
Requires at least: 3.5
Tested up to: 3.6.1
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin for displaying most viewed content using data from a Google Analytics account. Relieves the DB from writing every click. 

== Description ==
Plugin for displaying most viewed content using data from a Google Analytics account. Relieves the DB from writing every click.

= IMPORTANT! =
Please configure your plugin options in Tools->Toplytics.

= Features =
* Extract daily/weekly/monthly pageviews of posts from Google Analytics;
* Connect to Google Analytics Account using OAuth method;
* Custom template support direct into your theme structure;
* i18l support;


== Installation ==

= Installation =
1. Upload `toplytics.zip` to the `/wp-content/plugins/` directory;
2. Extract the `toplytics.zip` archive into the `/wp-content/plugins/` directory;
3. Activate the plugin through the 'Plugins' menu in WordPress.

= Usage =
Connect your plugin with Google Analytics Account from the `Tools->Toplytics` page;
Use your `Most Visited Posts` widget from the `Appearance->Widgets` page;

== Frequently Asked Questions ==

= Why should I use this plugin? =
You should use this plugin if you want to display the most visited posts of your site, from Google Analytics statistics.

= How can I use the plugin functionality outside the sidebar? =
Here is an example of code:

<?php 
	$toplytics_args = array(
		'period' => 'month',
		'number' => 7
	);
	$toplytics_results = toplytics_get_results( $toplytics_args );
	$k = 0;
	foreach ( $toplytics_results as $post_id => $post_views ) {
		echo (++$k) . ') <a href="' . get_permalink( $post_id ) 
			. '" title="' . esc_attr( get_the_title( $post_id ) ) . '">' 
			. get_the_title( $post_id ) . '</a> - ' . $post_views . ' Views<br />';
	}
?>	

= How to use custom template? =
To use your custom template just copy and paste the file `toplytics-template.php` from toplytics plugin directory to your theme directory.

Then you can customize your template. The plugin first search for the file `toplytics-template.php` into theme directory and then search into plugin directory, in this case your custom template from theme structure will be visible first.


== Changelog ==

= 1.4 =
Remove multiple templates support. You can use only one custom template placed into your theme directory.
Fix some display bugs.
Simplify the template syntax.
Add i18n support.

= 1.3 =
Implement OAuth login method.

= 1.2.2 =
Add custom theme templates support.

= 1.2.1 =
Use password to generate token, and save the token in database.

= 1.2 =
Add custom templates.

= 1.1 =
Start version on WP.

