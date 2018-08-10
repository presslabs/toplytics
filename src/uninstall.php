<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://www.presslabs.com/
 * @since      4.0.0
 *
 * @package    Toplytics
 */

/**
 * Default WP check to make sure the deletion was called
 * from within WP.
 */
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Some extra checks to make sure we are qualified for deletion.
 */
if (isset($_REQUEST['plugin']) && $_REQUEST['plugin'] != 'toplytics/toplytics.php') {
    exit;
}

if (isset($_REQUEST['action']) && !in_array($_REQUEST['action'], ['delete-plugin','delete-selected'], true)) {
    exit;
}

/**
 * We clean up all Toplytics DB options, even the ones we might now
 * be aware of, in case of old plugin data or wrong coding.
 */
global $wpdb;

$toplytics_options = $wpdb->get_results("SELECT option_name FROM {$wpdb->prefix}options WHERE option_name LIKE 'toplytics%'");

foreach ($toplytics_options as $option) :
    delete_option($option->option_name);
endforeach;
