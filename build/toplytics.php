<?php

/**
 *
 * @link              https://github.com/PressLabs/toplytics
 * @since             1.0.0
 * @package           Toplytics
 *
 * @wordpress-plugin
 * Plugin Name:       Toplytics - Popular Posts Widget
 * Plugin URI:        https://www.presslabs.org/toplytics/
 * Description:       Display top posts in a widget without putting any pressure on your host and database. This plugin helps you achieve this using the Google Analytics API to get the data from there so your server will stay clear of the preasure of monitoring and counting every single page view to display top posts.
 * Version:           4.0.0
 * Author:            Presslabs
 * Author URI:        https://www.presslabs.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       toplytics
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin default settings
 */
define( 'TOPLYTICS_VERSION', '4.0.0' );
define( 'TOPLYTICS_APP_NAME', 'Toplytics - Popular Posts Widget' );
define( 'TOPLYTICS_DOMAIN', 'toplytics' );
define( 'TOPLYTICS_ENTRY', 'toplytics.php' );
define( 'TOPLYTICS_SUBMENU_PAGE', 'options-general.php' );
define( 'DEFAULT_POSTS', '5' );
define( 'MIN_POSTS', '1' );
define( 'MAX_POSTS', '100' );
define( 'MAX_RESULTS', '250' );
define( 'CUSTOM_TEMPLATE_DEFAULT_NAME', 'toplytics-template' );
define( 'AUTH_API_VERSION', '1' );
define( 'AUTH_API_BASE_URL', 'https://www.presslabs.org/toplytics/' );
define( 'AUTH_API_BASE_CONFIG', 'apiconfig.json' );

/**
 * We load the composer dependencies at this stage and the
 * PSR4 autoloading functionality for the components folder.
 */
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-toplytics-activator.php
 */
function activate_toplytics() {
	\Toplytics\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-toplytics-deactivator.php
 */
function deactivate_toplytics() {
	\Toplytics\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_toplytics' );
register_deactivation_hook( __FILE__, 'deactivate_toplytics' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    4.0.0
 */
function ready() {

	$engine = new \Toplytics\Engine();
	$engine->start();

}
ready();
