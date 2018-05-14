<?php

namespace Toplytics;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.presslabs.com/
 * @since      4.0.0
 *
 * @package    Toplytics
 * @subpackage Toplytics/components
 * @author     Presslabs <support@presslabs.com>
 */

class Internationalization
{
    /**
     * Load the plugin text domain for translation.
     *
     * @since    4.0.0
     */
    public function loadPluginTextdomain()
    {
        load_plugin_textdomain(
            TOPLYTICS_DOMAIN,
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages'
        );
    }
}
