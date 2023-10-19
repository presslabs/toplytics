<?php

use Toplytics\Engine;
use Toplytics\Activator;
use Toplytics\Deactivator;

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
 * Version:           4.1
 * Author:            Presslabs
 * Author URI:        https://www.presslabs.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       toplytics
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
 * Plugin default settings
 */
define('TOPLYTICS_VERSION', '4.1.0');
define('TOPLYTICS_DB_VERSION', '1');
define('TOPLYTICS_APP_NAME', 'Toplytics - Popular Posts Widget');
define('TOPLYTICS_DOMAIN', 'toplytics');
define('TOPLYTICS_ENTRY', 'toplytics.php');
define('TOPLYTICS_SUBMENU_PAGE', 'options-general.php');
define('TOPLYTICS_DEFAULT_POSTS', '5');
define('TOPLYTICS_MIN_POSTS', '1');
define('TOPLYTICS_MAX_POSTS', '250');
define('TOPLYTICS_MAX_RESULTS', 20);
define('TOPLYTICS_NUM_EXTRA_RESULTS', 20);
define('TOPLYTICS_CUSTOM_TEMPLATE_DEFAULT_NAME', 'toplytics-template');
define('TOPLYTICS_AUTH_API_VERSION', '1');
define('TOPLYTICS_AUTH_API_BASE_URL', 'https://toplytics.presslabs.org/toplytics/');
define('TOPLYTICS_AUTH_API_BASE_CONFIG', 'apiconfig.json');
define('TOPLYTICS_WIDGET_TEMPLATE_VERSION', '2039481e7f61d8ee7f3cdd3dea2b0689');
define('TOPLYTICS_FOLDER_ROOT', plugin_dir_path(__FILE__));
define('TOPLYTICS_MAX_API_ERRORS_COUNT', 20);

/**
 * We use the following 2 constants to show upgrade messaged after the upgrade is
 * already performed to the end user. This version needs to be the same with the Toplytics
 * version for the message to appear. We don't want this to show up for minor hotfixes.
 */
define('TOPLYTICS_UPDATE_NOTICE_VERSION', '4.1');
define('TOPLYTICS_UPDATE_NOTICE_MESSAGE', 'Toplytics has been updated to version 4.1 which adds support for Google Analytics 4. Please make sure to re-authenticate your Google Analytics account in order to continue using the plugin with GA4.');

/**
 * We load the composer dependencies at this stage and the
 * PSR4 autoloading functionality for the components folder.
 */
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-toplytics-activator.php
 */
function activate_toplytics()
{
    Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-toplytics-deactivator.php
 */
function deactivate_toplytics()
{
    Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_toplytics');
register_deactivation_hook(__FILE__, 'deactivate_toplytics');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    4.0.0
 */
function toplytics_ready()
{
    $engine = new Engine();
    $engine->start();
    return $engine;
}

/**
 * We continue to use the frontend as a global var to be able
 * to keep backwards compatibility for custom themes.
 *
 * @since 3.0.0
 */
global $toplytics, $toplytics_engine;
$toplytics_engine = toplytics_ready();
$toplytics = $toplytics_engine->frontend;

/**
 * We make sure to include backwardcompatibility functions.
 *
 * @since 3.0.0
 */
require_once plugin_dir_path(__FILE__) . 'backward-compatibility.php';
