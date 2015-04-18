<?php
/*  Copyright 2014-2015 Presslabs SRL <ping@presslabs.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Toplytics_Help {

	public function __construct( $hook, $help = 'toplytics' ) {
		add_action( "load-{$hook}", array( $this, $help ), 20 );
	}

	public function documentation() {
		echo '<p><a href="' . plugins_url( '../doc/toplytics-api.pdf', __FILE__ ) . '" target="_blank">' . __( 'Click here to download the complete documentation in PDF format.', 'toplytics' ) . '</a></p>';
	}

	public function faq() {
		echo '<p><strong>' . __( 'Why should I use this plugin?', 'toplytics' ) . '</strong><br />'. __( "You should use this plugin if you want to display the most visited posts of your site in a safe and stable manner, with no risk of downtime or slowness, based on data from Google Analytics statistics. The plugin is built for high-traffic sites where counting every visitor's click loads up the DB and can potentially crash the site.", 'toplytics' ) . '</p>';
		echo '<p><strong>' . __( 'How often is the data from Google Analytics refreshed?', 'toplytics' ) . '</strong><br />'. __( 'The data from GA is refreshed every hour. During this interval, the information is safely stored using transients and options.', 'toplytics' ) . '</p>';
		echo '<p><strong>' . __( 'How to use the custom template?', 'toplytics' ) . '</strong><br />'. __( "To use a custom template you just need to copy the file toplytics-template.php from Toplytics plugin folder to your theme folder. You can then customize your template. The plugin will first search for the file toplytics-template.php in the active theme folder, and, if that's not found, it will search for it in the plugin folder. The custom template from the theme folder has priority over the one in the plugin folder.", 'toplytics' ) . '</p>';
		$this->documentation();
	}

	public function configuration() {
		$screen = get_current_screen();
		$screen->add_help_tab( array( 'id' => 'configuration', 'title' => __( 'Configuration', 'toplytics' ), 'callback' => array( $this, 'configuration_callback' ) ) );
		$screen->add_help_tab( array( 'id' => 'faq', 'title' => __( 'F.A.Q.', 'toplytics' ), 'callback' => array( $this, 'faq' ) ) );
	}

	public function configuration_callback() {
		echo '<p><strong>' . __( 'Configuration step 1', 'toplytics' ) . '</strong><br />' . __( 'In this step please register a new client application with Google.', 'toplytics' ) . '</p>';
		echo '<p>' . __( 'To register an application please login to your Google account and go to <a href="https://code.google.com/apis/console" target="_blank">Google API console</a>.', 'toplytics' ) . '</p>';
		echo '<p>' . __( '1. Create a New Project (set a unique project name and id);', 'toplytics' ) . '</p>';
		echo '<p>' . __( '2. Enable the Analytics API by going to APIs & auth → API → Analytics API and then click on <em>Enable API</em>;', 'toplytics' ) . '</p>';
		echo '<p>' . __( '3. From the APIs → Credentials tab create an OAuth 2.0 Client ID by clicking on <em>Create new Client ID</em>;', 'toplytics' ) . '</p>';
		echo '<p>' . __( '4. Select application type as <em>Installed application</em>;', 'toplytics' ) . '</p>';
		echo '<p>' . __( '5. Create the Branding information for the Client ID by editing the consent screen. It is compulsory to select your e-mail addres and to set a Product name;', 'toplytics' ) . '</p>';
		echo '<p>' . __( '6. Create the Client ID by selecting again <em>Installed application</em> and <em>Other</em>;', 'toplytics' ) . '</p>';
		echo '<p>' . __( '7. Download the JSON file with the API credentials (Auth Config file);', 'toplytics' ) . '</p>';
		echo '<p>' . __( '8. Upload this file in the plugin Settings page and click on <em>Upload Auth Config File</em>.', 'toplytics' ) . '</p>';
		echo '<p><strong>' . __( 'Configuration step 2', 'toplytics' ) . '</strong><br />' . __( 'In this step please connect to your Google Analytics Account.', 'toplytics' ) . '</p>';
		echo '<p>' . __( '1. Click the <em>Get Authorization Key</em> button and you will be redirected to google.com;', 'toplytics' ) . '</p>';
		echo '<p>' . __( '2. After logging in you need to agree that the newly created app will access your Analytics data. After that you get a key;', 'toplytics' ) . '</p>';
		echo '<p>' . __( '3. Then come back to this page and use the key in the <em>Authorization Key</em> field, and then click <em>Get Analytics Profiles</em> button.', 'toplytics' ) . '</p>';
		$this->documentation();
	}
}
