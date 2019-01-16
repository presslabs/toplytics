<?php

namespace Toplytics;

use \Toplytics\Activator;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       https://www.presslabs.com/
 * @since      4.0.0
 *
 * @package    Toplytics
 * @subpackage Toplytics/components
 * @author     Presslabs <support@presslabs.com>
 */

class Activator
{

    /**
     * Setting up the stage for Toplytics.
     *
     * During activation we need to make sure
     * that we clean-up after old deprecated
     * options and that we set out default
     * values if they are not already set.
     *
     * @since   4.0.0
     * @return  void
     */
    public static function activate()
    {
        Activator::deleteDeprecatedOptions();
        // Activator::cleanUpAuthOptions();
        Activator::addDefaultOptions();
    }

    /**
     * We need to clean up our mess after the upgrades,
     * if we didn't already anyway.
     *
     * @since   4.0.0
     * @return  void
     */
    public static function deleteDeprecatedOptions()
    {
        /**
         * Deprecated since 3.x.x
         */
        delete_option('toplytics_oauth_token');
        delete_option('toplytics_oauth_secret');
        delete_option('toplytics_auth_token');
        delete_option('toplytics_account_id');
        delete_option('toplytics_cache_timeout');
        delete_option('toplytics_results');

        /**
         * Deprecated since 4.0.0.
         */
        delete_option('toplytics_result_today');
        delete_option('toplytics_result_week');
        delete_option('toplytics_result_month');
        // delete_option('toplytics_oauth2_remote_token');

        /**
         * Used in testing and no longer used.
         */
        delete_option('toplytics_auth_status');
        delete_option('toplytics_last_update');
    }

    /**
     * We need to make sure that we clean up the
     * authorization infos befor activation
     * because it causes incompatibility
     * after the upgrade to 4.0.0.
     *
     * @since 4.0.0
     * @return void
     */
    public static function cleanUpAuthOptions()
    {
        $options = [
            'toplytics_auth_config',
            'toplytics_auth_code',
            'toplytics_auth_type',
        ];

        // foreach ($options as $option_name) {
        //     delete_option($option_name);
        // }
    }

    /**
     * These options are all the options we use inside
     * our entire plugin, if it doesn't have a
     * default value, we don't use it.
     *
     * @since   4.0.0
     * @return  void
     */
    public static function addDefaultOptions()
    {

        $options = [
            'toplytics_results_ranges' => [
                'month' => date_i18n('Y-m-d', strtotime('-29 days')),
                'week'  => date_i18n('Y-m-d', strtotime('-6 days')),
                'today' => date_i18n('Y-m-d', strtotime('today')),
                'realtime' => 0,
            ],
            'toplytics_private_auth_config' => false,
            'toplytics_last_update_status' => false,
            'toplytics_google_token' => false,
            'toplytics_profile_data' => false,
            'toplytics_auth_config' => false,
            'toplytics_auth_code' => false,
            'toplytics_auth_type' => false,
        ];

        foreach ($options as $option_name => $option_value) {
            if (!get_option($option_name)) {
                add_option($option_name, $option_value, '', 'no');
            }
        }
    }
}
