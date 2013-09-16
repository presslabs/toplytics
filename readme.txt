=== Toplytics ===
Contributors: PressLabs
Donate link: http://www.presslabs.com/
Tags: presslabs, analytics, posts, top, most visited, toplytics
Requires at least: 3.5
Tested up to: 3.5.2
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin for displaying most viewed content using data from a Google Analytics account. Relieves the DB from writing every click. 

== Description ==
Plugin for displaying most viewed content using data from a Google Analytics account. Relieves the DB from writing every click.

= IMPORTANT! =
Please configure your plugin options in Tools->Toplytics.

== Installation ==

= Installation =
1. Upload `toplytics.zip` to the `/wp-content/plugins/` directory;
2. Extract the `toplytics.zip` archive into the `/wp-content/plugins/` directory;
3. Activate the plugin through the 'Plugins' menu in WordPress.

= Usage =
Use your plugin from the `Tools->Toplytics` page;

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
		echo (++$k) . ") " . get_the_title( $post_id ) . " - " . $post_views . " Views<br />";
		echo toplytics_get_thumbnail_src( $post_id ) . "<br /><br />";
	}
?>

== Changelog ==

= 1.4 =
Fix some display bugs.
Add i18n support.
Add 'featured image' option to widget.
Add functions to use the functionality outside the sidebar.
Search for custom theme templates in plugin folder and also in current theme folder.
Simplify the template syntax.

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

