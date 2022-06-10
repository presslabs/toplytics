=== Toplytics ===
Contributors: PressLabs, cristianuibar
Donate link: http://www.presslabs.com/
Tags: presslabs, analytics, posts, top, most visited, most viewed posts, top content, toplytics, popular, google analytics, high traffic, popular posts, oauth, server resources, settings, widget, embed code, javascript, json, json file, simple, post views
Requires at least: 4.7.3
Tested up to: 6.0
Stable tag: 4.0.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Displays the most visited posts as a widget using data from Google Analytics. Designed to be used under high-traffic or low server resources.

For more details check the [official Toplytics documentation](https://www.presslabs.com/code/toplytics/).

== About the makers ==
This plugin was developed by the crafty people at Presslabs—the Smart Managed WordPress Hosting Platform. Here we bring high-performance hosting and business intelligence for WordPress sites. In our spare time, we contribute to the global open-source community with our plugins.

We built this plugin in 2013 to provide a less resource consuming alternative to help our clients that wanted to display a list with the most popular articles on their websites.

== What is Toplytics? ==
This plugin displays the most visited posts as a widget, using data extracted from Google Analytics. Toplytics is designed to work with high-traffic sites and all types of caching.

== Why Toplytics? ==
You can use this plugin if you want to display the most visited posts of your site in a safe and reliable manner, with no risk of downtime or slowness. The plugin is built for high-traffic sites where counting every visitor’s click loads up the DB and presents the potential of crashing the site. Of course, you need an active Google Analytics setup on your site to use this plugin.

== Toplytics features: ==
* shortcodes are now supported for easier integration into posts/pages or other widgets. [toplytics period="week" numberposts="3" showviews="true"] Check FAQ for details;
* connect with Google Analytics Account using OAuth 2.0 method;
* starting with the plugin version 3.0 we have switched to GA API v3. Toplytics
* provides a widget displaying the most visited posts as simple links (no styling);
* use the widget to display the most visited posts from the past day, week or month;
* set the number of posts to be displayed between 1 and 250;
* display the number of views as counted by Google Analytics;
* support/translate i18n requests;
* generate the list of the most visited posts dynamically with JavaScript to correctly display them with any caching mechanism/plugin;
* use the custom template to display the widget.

== Receiving is nicer when giving ==
We’ve built Toplytics to make our lives easier and we’re happy to do that for other developers and site owners, too. We’d really appreciate it if you could contribute with code, tests, documentation or just share your experience with Toplytics.

Development of Toplytics happens at [github.com/PressLabs/toplytics](http://github.com/PressLabs/toplytics).
Issues are tracked at [github.com/PressLabs/toplytics/issues](http://github.com/PressLabs/toplytics/issues).
This WordPress plugin can be found at [wordpress.org/plugins/toplytics/](https://wordpress.org/plugins/toplytics/).

== Installation ==
1. Upload *toplytics.zip* to the */wp-content/plugins/* directory;
2. Extract the *toplytics.zip* archive into the */wp-content/plugins/* directory;
3. Activate the plugin through the *Plugins* menu in WordPress.

Alternatively, go into your WordPress dashboard and click on *Plugins -> Add Plugin* and search for Toplytics. Then click on *Install*, then on *Activate Now*.

We offer two possibilities to use Toplytics: through **Public Authorization** or the **Private Authorization**, for more configuration details check [Toplytics installation](https://www.presslabs.com/code/toplytics/installation/).

The **Public Authorization** method is using the Presslabs public API key to authenticate you to the Google Analytics API, and you don't have to set up your own API keys. To use this method, simply press the **Log in with your Google Account via Presslabs.org** button and you will be redirected to the Google Authorization screen where you will be asked for read access to your analytics profiles.

Then you need to select your profile and you are all set.

The private authorization is the recommended way in using Toplytics, as it offers you complete control over the connection by using your very own API keys and application for granting access.

You need to enter your Client ID and Client Secret from your Google Analytics account. The next steps will guide you in configuring your Google Analytics account to Toplytics. Keep in mind that you will need the **Redirect URL** mentioned in this page further in configuring Toplytics.

= Configuration step 1 =
In this step please register a new client application with Google. To register an application please login to your Google account and go to Google API console.

1. Create a New Project (set a unique project name and id);

2. Enable the Analytics API by going to **Enable APIs and Services** and browse the library to find the **Analytics API**, then click it and enable it;

3. Create new Client ID;

After you set up your product name, you can create your credentials. Go back to the **Dashboard** section, click on the arrow of the button **Create credentials** and choose the **OAuth Client ID** option.

When asked to choose your application type choose the **Web application** option. You will be asked to introduce the **Javascript Origins** and **Redirects URI's**. As **Authorized JavaScipt Origins** introduce your domain name, and as **Authorized redirect URI** you need to introduce the Redirect URL from `Settings -> Toplytics -> Private Authorization`.

Your newly created credentials will appear on the **Credentials** page and the **Client ID** and **Client secret** you need to authorize the **Private Authentification** will appear in a pop up. You can also see them by pressing the **Edit OAuth Client** button from the Credentials section.

= Configuration step 2 =
In this step you will need to authorize requests.

1. Copy the Client ID and the Client Secret keys from the **Credentials section**, then go back to `Settings -> Toplytics -> Private Authorization` to paste these credentials. By using these keys the client application will avoid sharing the username and/or password with any other Toplytics users.

2. Click the **Private Authorize** button and after logging in you need to agree that the newly created app will access your Analytics data and you are all set.

3. You can select from the list of profiles the one you want to use for this site or you can disconnect your Google account. Make sure you have a Google Analytics profile set up, otherwise a warning message will appear that there are no profiles on the selected Google account.

= Usage =
Connect your plugin with Google Analytics Account from the Settings page (*Settings -> Toplytics*);
Use the *Toplytics* widget from the *Appearance -> Widgets* page;

== Frequently Asked Questions ==

= Why should I use this plugin? =
You should use this plugin if you want to display the most visited posts of your site in a safe and stable manner, with no risk of downtime or slowness, based on data from Google Analytics statistics. The plugin is built for high-traffic sites where counting every visitor's click loads up the DB and can potentially crash the site.

= How often is the data from Google Analytics refreshed? =
You can set how often the data is refreshed from the widgets settings: hourly, twice a day or daily. By default, it is refreshed hourly.

= How to use the custom template? =
You also have the possibility to create a custom template. Here it is how.

In `toplytics/resorces/views/frontend/` there is a file named `widget.template.php`, which is the default template. To create a custom template, copy the `widget.template.php` file here, rename it to `custom.template.php` and then customize it as you wish. The file is a typical PHP template file.

You can also use the old method of creating a custom template with Toplytics: add a template.php file in your active theme's root folder.

The priority regarding template files is the following:

* Toplytics will look for a template.php file in the root of the active theme folder - this is to ensure backwards compatibility, as this is how you could create a custom template before; you can still use this option too
* Then it will look for the file `custom.template.php` in `toplytics/resorces/views/frontend/` folder, and if it does not exist, it will display the default template, which is `widget.template.php`

= How can I use the shortcode? =
The shortcode has 3 parameters: period -> default=month (today/week/month), numberposts -> default=5 (min=1/max=250), showviews -> default=false (true/false)

Shortcode example:

`[toplytics period="week" numberposts="3" showviews="true"]`

The shortcode can be used within post/pages and in other widgets from the sidebar. For any parameter that is not used, the default value will be used.

= How can I use JavaScript code in order to show up the top in widget? =
There is a check in the widget Settings for this operation, called **Load via Javascipt AJAX**. This way the stats are read from the `toplytics.json` file or `wp-json/toplytics/results` (depends on which endpoint you've activated) and loaded dynamically with JavaScript. Otherwise, the results will be read from the database.

This can be useful for sites that are using caching, for example. If the top is not loaded dynamically with JavaScript and AJAX, it will not refresh unless someone flushes the page cache.

You can check the JavaScript code on the default template `toplytics/resorces/views/frontend/widget.template.php`.

= What is `toplytics.json` file? =
This file contains the statistics in JSON format, if you have the **custom JSON endpoint** enabled. This option exists to maintain backwards compatibility, the recommend option now is to use the **REST API Endpoint**. This way, your statistics will be retrieved from the endpoint `/wp-json/toplytics/results`.

After you enable the REST API Endpoint or the custom JSON endpoint, you need to flush the Permalink cache after you change this by visiting Settings > Permalinks and saving that form with no change.

Both the `toplytics.json` file and the `/wp-json/toplytics/results` endpoint are designed to be used with the JS custom template code to load the top dynamically with JavaScript and AJAX.

= Where is `toplytics.json` file located? =
The file `toplytics.json` is located to the root folder of the site.

**Example**

If the site domain is `http://www.example.com/` then the file url is `http://www.example.com/toplytics.json`.

= What is Local Post Discovery? =
The Analytics API only returns the permalink of the posts and the number of pageviews. In order to display the title, as well as other post fields like featured image, post type etc, Toplytics searches your site's database for these additional information.

However, there are some rare cases when you want to display on your site a top from another site, for example. This means that you don't have access to the respective site's database, only to it's Analytics statistics. In this case, you can activate **Skip local posts discovery**. This means that instead of searching for additional information in the site's database, Toplytics will try to generate a human readable title from the URLs that Google Analytics returns. This will only work if you have pretty permalinks enabled for your site's URLs.

For example, if you have a post with the URL `/code/kubernetes-mysql-operator-digital-ocean/`, Toplytics will generate the title **Code Kubernetes Mysql Operator Digital Ocean**.

Along with **Skip Local Posts Discovery**, you will also need to specify the **Custom domain** for the site, since Google Analytics doesn't give us the domain in the URLs it returns. We need the domain to create the links to the articles in the top.

= Why are pages showing up in my Toplytics Most Viewed Posts Top? =
In the Toplytics settings, you have an option called **Posts to fetch from GA**, which sets up how many articles, pages and other custom post types will be returned by the Google Analytics API.

If you only want your top to show your most viewed posts (not pages or other custom post type), you have a Toplytics setting for specifying what king of posts appear in your top, called **Allowed post types**. By default it is set to **post**, which means that it will extract from the data fetched from Google only the posts.

Toplytics will still extract for Google Analytics your 20 most viewed posts and pages for example, but will only display the most viewed posts.

= Why is my Toplytics output empty? =
Toplytics fetches from Analytics the number of posts, pages and other post types that you specify on `Toplytics Settings -> Posts to fetch from GA`, generically called "posts". Then, they will be filtered based on your Settings. For example, you can set up the **Allowed post types**, which is set by default to **post**. In this case, only the posts (articles) will show in your top.

However, you may encounter the following situation. Your most viewed content is your pages, and you only fetch 10 "posts" from Google Analytics. By default, Toplytics displays the most viewed posts (articles), but if the results fetched were all pages, it will have nothing to show. In this case, you can set up a higher number of "posts" to be fetched (the default is 20).

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

== Screenshots ==

== Changelog ==

= 4.0.10 =

* Tweek: Added an error count threshold to prevent "sudden disconenct sindrome"
* Tweek: Improved classes handling by importing the required ones where needed
* Improved: Exception handling and display of cURL errors
* Fixed: Replaced deprecated Google Exception class with the correct one
* Fixed: Few code fixes like deprecated filters, undefined var
* Fixed: Array offset warning on first activation (#156)

= 4.0.9 =

* Fixed: Widgets button on the Overview page was not working

= 4.0.8 =

* Fixed: Not retrieving the list of GA profiles when empty property present in account. Thanks to @bapman for finding this problem: https://wordpress.org/support/topic/not-retrieving-the-list-of-ga-profiles/

= 4.0.7 =

* Fixed: Status messages were not being displayed on the admin settings pages

= 4.0.6 =

* Optimized vendor dependencies for a smaller package footprint

= 4.0.5 =

* Updating vendor dependencies
* Fix updating date ranges before every update

= 4.0.4 =

* Fix #150 - set proper size for featured image
* Fix #146 - Update analytics data on saving plugin settings
* Fix #145 - Create option toplytics_results_ranges, if not exists
* Fix Skip local post discovery with no domain provided
* Remove Blade templating engine from view files
* Add support for rendering posts per category.

= 4.0 =
**Upgrade Notification**: Please TEST this new version of Toplytics on your development site before upgrading! You might need to update your custom template file or change some settings inside the new settings page.

This version is a complete rewrite of the plugin for better code management and better maintenability.

Please read our FAQ: https://www.presslabs.com/code/toplytics/how-to-use-toplytics/
You can see a list of fixed issues that came with this complete rewrite here: https://github.com/presslabs/toplytics/pull/128

= 3.2 =
Simplify the auth process using our own API keys.

= 3.1 =

* Allow multiple post types via filter
* Add shortcode to features list fix #115
* Remove PDF. Change documentation link from PDF docs to Presslabs site
* Fix #109 - deprecated constructor method call
* Fix #100, exclude disconnecting for network is unreachable
* Fix travis tests
* Use different ID for wrapper div of list of posts
* Fixed get_result() returning null instead of array()
* Update readme.txt
* Remove unused parameter `$when`
* Fix add_query_arg vulnerability
* Add the filter `toplytics_widget_args`

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

= 4.0 =
This is a complete plugin rewrite. The entire plugin is now OOP and we're using the official Google API together with a new templating engine. There are a lot of changes that can affect backward compatibility. We've strived make it work with old custom templates as well, yet it is highly recommanded for you to switch to the new one. Please read the documentation to get a better idea of how everything works here: https://www.presslabs.com/code/toplytics/installation/ and our FAQ available here: https://www.presslabs.com/code/toplytics/how-to-use-toplytics/

= 3.0 =
Starting with version 3.0, Toplytics uses Google Analytics API v3.0. This is a major update and you need to re-authenticate with Google Analytics for the plugin to work!

= 2.0 =
The option `Display posts in real time` will let you get the results from one JSON file with JavaScript code. All HTML code is generated dynamically.
Major plugin changes & code rewrite. Added theme custom template, OAuth login, i18n support, shortcode support.
