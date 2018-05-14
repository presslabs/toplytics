<?php
/**
 * Plugin Name: Toplytics - Master API
 * Description: The master API handler for Toplytics plugin.
 * Version: 1.0
 * Author: Presslabs
 * Author URI: https://presslabs.com
 * License: GPLv2
 * Text Domain: toplytics-master
 * Tested up to: 4.9.7
 */

/**
 * This is the master API endpoint for Google authorization.
 * This is a simple mirror which redirects users back to thier plugin page.
 *
 * @param array $data Options for the function. The content of the $_GET global.
 *
 * @return void Redirects to the user back to the specified URL.
 */
function toplyticsMasterApiEndpoint($data)
{
    if (!isset($data['state']) || !$data['state'] || !filter_var($data['state'], FILTER_VALIDATE_URL)) {
        return errorForTheCurious();
    }
    
    if (isset($data['google-error']) && $data['google-error']) {
        return wp_redirect(
            add_query_arg(
                [
                'status' => 'error',
                'code' => $data['google-error']
                ],
                $data['state']
            ),
            303
        );
    }

    if (!isset($data['code']) || !$data['code']) {
        return errorForTheCurious();
    }
    
    $url = add_query_arg(
        [
            'status' => 'success',
            'code' => $data['code'],
        ],
        $data['state']
    );

    wp_redirect($url, 303);
}

/**
 * Simply returns an error for the curious that are playing with this "API".
 *
 * @return WP_Error
 */
function errorForTheCurious()
{
    return new WP_Error('coffee', 'Go get one, this is not for you!', array( 'status' => 403 ));
}

/**
 * We add the API endpoint.
 */
add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'toplytics-master/v1',
            '/auth/',
            [
                'methods' => 'GET',
                'callback' => 'toplyticsMasterApiEndpoint',
            ]
        );
    }
);

/**
 * Rename reserved query arg 'error' because WordPress.
 */
add_action('init', function () {
    if (preg_match('/^\/wp-json\/toplytics-master\/v1\/auth\//', $_SERVER['REQUEST_URI'])) {
        if (isset($_GET['error'])) {
            $_GET['google-error'] = $_GET['error'];
        }

        global $wp;
        $wp->add_query_var('google-error');
    }
});
