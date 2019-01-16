<?php

namespace Toplytics;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @link       https://www.presslabs.com/
 * @since      4.0.0
 *
 * @package    Toplytics
 * @subpackage Toplytics/components
 * @author     Presslabs <support@presslabs.com>
 */

class Deactivator
{

    /**
     * Clean up our mess from the options table.
     *
     * We remove the options we have set at activation one by one
     * and then do some other clean-up stuff like remove the
     * CRON job and flushing the rewrite_rules as well as
     * flushing the transients and memcache.
     *
     * @since    4.0.0
     */
    public static function deactivate()
    {
        $options = [
            'toplytics_results_ranges',
            'toplytics_private_auth_config',
            'toplytics_last_update_status',
            'toplytics_google_token',
            'toplytics_profile_data',
            'toplytics_auth_config',
            'toplytics_auth_code',
            'toplytics_auth_type',
            'toplytics_settings'
        ];

        /**
         * We should only delete options on deactivation if debugging something.
         * We should keep options saved for later reactivation.
         * Google token will get regenerated anyway.
         */
        // foreach ($options as $option_name) {
        //     delete_option($option_name);
        // }

        /**
         * We make sure our CRON job is no longer being executed
         * by clearing our scheduled hook.
         */
        wp_clear_scheduled_hook('toplytics_cron_event');

        /**
         * We need to force the rewrite rules to be regenerated on
         * the next front-end page load by deleting the actual
         * option from DB since our hooks are still in
         * effect on this request.
         */
        delete_option('rewrite_rules');

        /**
         * To finish this off, we're flushing the memcache
         * and the actual transients.
         */
        wp_cache_flush();
    }
}
