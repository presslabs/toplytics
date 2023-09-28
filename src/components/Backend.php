<?php

namespace Toplytics;

use Exception;
use Google\Client;
use Toplytics\Activator;
use Google\Service\AnalyticsData;
use GuzzleHttp\Exception\RequestException;
use Google\Service\Exception as GoogleServiceException;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;

use Google\Service\AnalyticsData\DateRange as Google_Service_AnalyticsData_DateRange;
use Google\Service\AnalyticsData\Dimension as Google_Service_AnalyticsData_Dimension;
use Google\Service\AnalyticsData\Metric as Google_Service_AnalyticsData_Metric;
use Google\Service\AnalyticsData\OrderBy as Google_Service_AnalyticsData_OrderBy;
use Google\Service\AnalyticsData\RunReportRequest as Google_Service_AnalyticsData_RunReportRequest;
use Google\Service\AnalyticsData\FilterExpression as Google_Service_AnalyticsData_FilterExpression;
use Google\Service\AnalyticsData\Filter as Google_Service_AnalyticsData_Filter;
use Google\Service\AnalyticsData\StringFilter as Google_Service_AnalyticsData_StringFilter;
use Google\Service\AnalyticsData\MetricOrderBy as Google_Service_AnalyticsData_MetricOrderBy;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       https://www.presslabs.com/
 * @since      4.0.0
 *
 * @package    Toplytics
 * @subpackage Toplytics/components
 * @author     Presslabs <support@presslabs.com>
 */

class Backend
{

    /**
     * The ID of this plugin.
     *
     * @since    4.0.0
     * @access   private
     * @var      string    $plugin_basename    The ID of this plugin.
     */
    private $plugin_basename;

    /**
     * The version of this plugin.
     *
     * @since    4.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Structure elements initialized in constructor.
     *
     * @since    4.0.0
     * @access   private
     * @var      Window                     $window     Used for output.
     * @var      Google_Client              $client     Used for connection.
     * @var      Google_Service_Analytics   $service    Used for retrival.
     * @var      array                      $settings   The settings of the plugin.
     */
    private $window;
    private $client;
    private $service;
    private $settings;

    private $use_ga4;

    private $_need_additional_posts_data;
    private $_widgets;
    private $_gapi_errors_count;

    /**
     * Initialize the backend class and set its properties.
     *
     * @since    4.0.0
     * @param      string    $plugin_basename       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     * @param      Window    $window     The window instance.
     */
    public function __construct($plugin_basename, $version, Window $window, $settings = null)
    {

        $this->plugin_basename = $plugin_basename;
        $this->version = $version;

        $this->window = $window;

        // TODO: This should become it's own class in the future
        $this->settings = $settings ?: get_option('toplytics_settings', null);

        /**
         * We are initializing the Google Client for authorization
         * and the Google Service for data retirval.
         */
        $this->client = $this->initClient();
        $this->service = $this->initService();

        /**
         * We start from the assumption there's no need for additional
         * data to be initialized: latest posts for categories and
         * top commented posts.
         */
        $this->_need_additional_posts_data = false;

        /**
         * We default to GAv4, but we can also support UA.
         */
        $this->use_ga4 = get_option('toplytics_use_ga4', true);

        /**
         * Initialize the list of Toplytics widgets data as null.
         */
        $this->_widgets = (object)[];

        $this->_gapi_errors_count = get_option('toplytics_gapi_errors_count', 0);

        /**
         * If the initialization above worked, we then try to schedule
         * the CRON for data retrival.
         */
        if ($this->service) {
            add_action('wp', [ $this, 'setupScheduleEvent' ]);
            add_action('toplytics_cron_event', [ $this, 'updateAnalyticsData' ]);
        }
    }

    private function _increment_gapi_errors_count() {
        update_option('toplytics_gapi_errors_count', $this->_gapi_errors_count++);
        return $this;
    }

    private function _check_gapi_errors_threshold() {
        if ($this->_gapi_errors_count > apply_filters('toplytics_max_api_errors_count', TOPLYTICS_MAX_API_ERRORS_COUNT)) {
            $this->_reset_gapi_errors_count()->serviceDisconnect(true);
        }

        return $this;
    }

    private function _reset_gapi_errors_count() {
        if ( $this->_gapi_errors_count > 0 ) {
            $this->_gapi_errors_count = 0;
            update_option('toplytics_gapi_errors_count', 0);
        }

        return $this;
    }

    /**
     * Fetch the data regarding Toplytics widgets from the options table.
     */
    private function _maybe_fetch_widgets_data()
    {
        if ( $this->_widgets == (object)[] ) {
            $this->_widgets = get_option( 'widget_toplytics-widget', array() );
        }
    }

    /**
     * In case there are widgets which need to render
     * results for a particular category, verify if there
     * are enough posts fetched for the specified period.
     *
     * @param array $posts The array of posts to check
     * @param boolean $period The period for which to check the post results
     * @return boolean Flag indicating whether there are enough posts
     */
    private function _have_enough_results( $posts, $period )
    {
        // Fetch the widgets data from the database, if not already retrieved.
        $this->_maybe_fetch_widgets_data();

        // Check each widget.
        foreach ( $this->_widgets as $widget ) {
            /* Verify that the widget renders posts
             * from a certain category and if it renders
             * results for the period of the current check. */
            if ( ! empty( $widget['category'] ) && ( $widget['period'] == $period ) ) {
                // There is at least one widget filtering posts by category.
                $this->_need_additional_posts_data = true;
                
                $num_posts_in_category = 0;
                $category_id = intval( $widget['category'] );
                foreach ( $posts as $post_id => $post_data ) {
                    $post = get_post( $post_id );
                    if ( is_object( $post ) && has_category( $category_id, $post ) ) {
                        $num_posts_in_category ++;
                    }
                }
                
                // Stop checking if there aren't enough posts for this category.
                if ( $num_posts_in_category < $widget['numberposts'] ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Return the Window property.
     *
     * @since    4.0.0
     * @return    Window    The window instance.
     */
    public function getWindow()
    {
        return $this->window;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    4.0.0
     * @return    void
     */
    public function enqueueStyles()
    {
        wp_enqueue_style(
            $this->plugin_basename,
            plugin_dir_url(__FILE__) . '../resources/backend/css/toplytics-admin.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    4.0.0
     */
    public function enqueueScripts()
    {
        wp_enqueue_script(
            $this->plugin_basename,
            plugin_dir_url(__FILE__) . '../resources/backend/js/toplytics-admin.js',
            ['jquery'],
            $this->version,
            false
        );
    }

    public function pluginUpgradeNotice($data, $response ) {
        if( isset( $data['upgrade_notice'] ) ) {
            printf(
                '<div class="update-message">%s</div>',
                wpautop( $data['upgrade_notice'] )
            );
        }
    }

    public function pluginUpgradeComplete( $upgraderObject, $options ) {
        // If an update has taken place and the updated type is plugins and the plugins element exists
        if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
            // Iterate through the plugins being updated and check if ours is there
            foreach( $options['plugins'] as $plugin ) {
                if( $plugin == $this->plugin_basename && version_compare(TOPLYTICS_VERSION, TOPLYTICS_UPDATE_NOTICE_VERSION, '==')) {
                    // Set a transient to record that our plugin has just been updated
                    set_transient( 'toplyticsMessage', TOPLYTICS_UPDATE_NOTICE_MESSAGE );
                }
            }
        }
    }

    /**
     * This function is responsible for initializing the Google
     * Client and authorizing us with the credentials we used.
     *
     * @since   4.0.0
     * @return  Google_Client
     */
    protected function initClient()
    {
        $remoteConfig = $this->getAuthConfig();

        if (!$remoteConfig) {
            return false;
        }

        $clientConfig = ['retry' => [
            'initial_delay' => 1,
            'max_delay' => 30,
            'factor' => 1,
            'jitter' => 0.2,
            'retries' => 10
        ]];

        $client = new Client($clientConfig);
        $client->setApplicationName(TOPLYTICS_APP_NAME);
        $client->setClientId($remoteConfig->client_id);
        $client->setClientSecret($remoteConfig->client_secret);
        $client->setRedirectUri($remoteConfig->redirect_uris[0]);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);

        return $client;
    }

    /**
     * This function is responsible for initializing our service,
     * if of course our client is already initializind and
     * we are authenticated.
     *
     * @since   4.0.0
     * @return  \Google\Service\Analytics|false
     */
    protected function initService()
    {
        if (!$this->client) {
            return false;
        }

        try {
            if ($this->serviceConnect()) {
                return new AnalyticsData($this->client);
            }
        } catch (GoogleServiceException $e) {
            $this->window->notifyAdmins('error', __(
                'Something went wrong while initiating the Analytics service.',
                TOPLYTICS_DOMAIN
            ));
        }

        return false;
    }

    public function useGA4() {
        if (empty($_POST['ToplyticsUseGA4'])) {
            return;
        }
        
        update_option('toplytics_use_ga4', true, true);

        $this->window->successRedirect(__(
            'You have switched to use Google Analytics v4!',
            TOPLYTICS_DOMAIN
        ));
    }

    public function useUA() {
        if (empty($_POST['ToplyticsUseUA'])) {
            return;
        }

        update_option('toplytics_use_ga4', false, true);

        $this->window->successRedirect(__(
            'You have switched to use Universal Analytics!',
            TOPLYTICS_DOMAIN
        ));
    }

    /**
     * This is the place where we disconnect from the Google Services
     * by simply forgoting the options and pinging Google to revoke the token.
     *
     * @since   4.0.0
     * @return  void
     */
    public function serviceDisconnect($programatic = false, $silent = false)
    {
        if (empty($_POST['ToplyticsSubmitAccountDisconnect']) && !$programatic) {
            return;
        }

        if (!$programatic) {
            check_admin_referer('toplytics-settings');
        }

        if ($this->client) {
            try {
                $this->client->revokeToken();
            } catch (RequestException $e) {
                error_log('Toplytics: Something went wrong while revoking the token. Details: ' . $e->getMessage());
            }
        }

        update_option('toplytics_private_auth_config', false);
        update_option('toplytics_google_token', false);
        update_option('toplytics_profile_data', false);
        update_option('toplytics_property_id', false);
        update_option('toplytics_auth_config', false);
        update_option('toplytics_auth_code', false);

        if ($silent) {
            return;
        }

        if (!$programatic) {
            $this->window->successRedirect(__(
                'Google account successfully disconnected. All authorization settings reseted.',
                TOPLYTICS_DOMAIN
            ));
        }

        $this->window->redirect(
            __(
                'Google account disconnected. Most probaby this is due to an error. Please try again.',
                TOPLYTICS_DOMAIN
            ),
            'warning'
        );
    }

    /**
     * This action clears the selected profile from options so we can
     * selecte a new profile on the next load.
     *
     * @since 4.0.0
     *
     * @return Redirect
     */
    public function forceUpdate()
    {
        if (empty($_POST['ToplyticsSubmitForceUpdate'])) {
            return false;
        }

        check_admin_referer('toplytics-settings');

        $update = $this->updateAnalyticsData();

        if ($update) {
            $this->window->successRedirect(__('The data has been updated.', TOPLYTICS_DOMAIN));
        } else {
            $this->window->errorRedirect(__('Update failed. Check your logs for more info.', TOPLYTICS_DOMAIN));
        }
    }

    /**
     * The settings page for our plugin.
     *
     * TODO: These settings declaration functions and getters / setters should
     * be moved to their own Class.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function initSettings()
    {
        $options = get_option('toplytics_settings');
        if ($options == false) {
            $options = $this->getDefaultSettings();
            add_option('toplytics_settings', $options);
        }

        register_setting(
            'toplytics',
            'toplytics_settings',
            array(
                'sanitize_callback' => array( $this, 'act_before_settings_update' ),
            )
        );

        // Below line is for DEBUG ONLY
        // update_option('toplytics_settings', $this->getDefaultSettings());

        // Primary Section
        add_settings_section('toplytics_settings', __('Global Settings', TOPLYTICS_DOMAIN), [$this, 'getSettingsHeader'], 'toplytics');

        // Enable REST API JSON Output
        add_settings_field(
            'enable_rest_endpoint',
            $this->getLabel(__('Enable REST API Endpoint', TOPLYTICS_DOMAIN), 'enable_rest_endpoint'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'enable_rest_endpoint',
                'tooltip' => __('Enables and disables the default WP REST API endpoint. The endpoint is: ', TOPLYTICS_DOMAIN) . esc_url(get_rest_url(null, '/toplytics/results')) . __(' Default: Enabled', TOPLYTICS_DOMAIN),
            ]
        );

        // Enable JSON Output
        add_settings_field(
            'enable_json',
            $this->getLabel(__('Enable custom JSON endpoint', TOPLYTICS_DOMAIN), 'enable_json'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'enable_json',
                'tooltip' => __('Enables and disables the JSON output on a custom endpoint. Use the WP REST API endpoint for common tasks. The endpoint is: ', TOPLYTICS_DOMAIN) . esc_url(home_url('/' . $this->checkSetting('json_path') ? $this->settings['json_path'] : '')) . __(' Default: Disabled', TOPLYTICS_DOMAIN),
            ]
        );

        // JSON output Path
        add_settings_field(
            'json_path',
            $this->getLabel(__('Json Endpoint Path', TOPLYTICS_DOMAIN), 'json_path'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'json_path',
                'option' => 'toplytics_settings',
                'input' => 'text',
                'tooltip' => __('This is the path where your JSON will be available on the frontend if enabled. Please don\'t forget to flush the Permalink cache after you change this by visiting Settings > Permalink and saving that form with no change. Default: toplytics.json', TOPLYTICS_DOMAIN),
            ]
        );

        // Enable Monthly Fetch
        add_settings_field(
            'fetch_month',
            $this->getLabel(__('Monthly Top', TOPLYTICS_DOMAIN), 'fetch_month'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'fetch_month',
                'tooltip' => __('Enables the fetch of the most visited posts per month from Google Analytics in the local DB. Default: Enabled', TOPLYTICS_DOMAIN),
            ]
        );

        // Enable Weekly Fetch
        add_settings_field(
            'fetch_week',
            $this->getLabel(__('Weekly Top', TOPLYTICS_DOMAIN), 'fetch_week'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'fetch_week',
                'tooltip' => __('Enables the fetch of the most visited posts per week from Google Analytics in the local DB. Default: Enabled', TOPLYTICS_DOMAIN),
            ]
        );

        // // Enable Daily Fetch
        // add_settings_field(
        //     'fetch_today',
        //     $this->getLabel(__('Daily Top', TOPLYTICS_DOMAIN), 'fetch_today'),
        //     [$this, 'printInput'],
        //     'toplytics',
        //     'toplytics_settings',
        //     [
        //         'id' => 'fetch_today',
        //         'tooltip' => __('Enables the fetch of the most visited posts per day from Google Analytics in the local DB. Default: Enabled', TOPLYTICS_DOMAIN),
        //     ]
        // );

        // // Enable Realtime Fetch
        // add_settings_field(
        //     'fetch_realtime',
        //     $this->getLabel(__('Realtime Top', TOPLYTICS_DOMAIN), 'fetch_realtime'),
        //     [$this, 'printInput'],
        //     'toplytics',
        //     'toplytics_settings',
        //     [
        //         'id' => 'fetch_realtime',
        //         'tooltip' => __('Enables the fetch of the most visited posts in realtime from Google Analytics in the local DB. Default: Disabled', TOPLYTICS_DOMAIN),
        //     ]
        // );

        // The custom post variables that will be included in the JSON output when fetching posts from DB
        add_settings_field(
            'max_posts_fetch_limit',
            $this->getLabel(__('Posts to fetch from GA', TOPLYTICS_DOMAIN), 'max_posts_fetch_limit'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'max_posts_fetch_limit',
                'option' => 'toplytics_settings',
                'input' => 'text',
                'tooltip' => __('The maximum number of posts to fetch for each data range set from GA. Default: 20', TOPLYTICS_DOMAIN),
            ]
        );

        // The custom post variables that will be included in the JSON output when fetching posts from DB
        add_settings_field(
            'cron_exec_interval',
            $this->getLabel(__('GA Data Refresh Interval', TOPLYTICS_DOMAIN), 'cron_exec_interval'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'cron_exec_interval',
                'option' => 'toplytics_settings',
                'input' => 'select',
                'options' => ['hourly', 'twicedaily', 'daily'],
                'tooltip' => __('How often do you want your data refreshed for your top? Default: hourly', TOPLYTICS_DOMAIN),
            ]
        );

        // The custom post variables that will be included in the JSON output when fetching posts from DB
        add_settings_field(
            'custom_output_post_variables',
            $this->getLabel(__('Custom post variables in JSON output', TOPLYTICS_DOMAIN), 'custom_output_post_variables'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'custom_output_post_variables',
                'option' => 'toplytics_settings',
                'input' => 'text',
                'disabled' => $this->checkSetting('skip_local_post_discovery'),
                'tooltip' => __('Use commas (,) to separate variable names. For variable names see: <a href="https://codex.wordpress.org/Class_Reference/WP_Post" target="_blank" title="WP Post Class Reference">WP Codex: WP_POST</a>. Default: Empty', TOPLYTICS_DOMAIN),
            ]
        );

        // Enable Realtime Fetch
        add_settings_field(
            'include_featured_image_in_json',
            $this->getLabel(__('Include Featured Image in JSON Output', TOPLYTICS_DOMAIN), 'include_featured_image_in_json'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'include_featured_image_in_json',
                'disabled' => $this->checkSetting('skip_local_post_discovery'),
                'tooltip' => __('Adds the featured_image for each post when fetching into JSON output. Default: Disabled', TOPLYTICS_DOMAIN),
            ]
        );

        // Featured image size
        add_settings_field(
            'custom_featured_image_size',
            $this->getLabel(__('Featured image thumbnail size', TOPLYTICS_DOMAIN), 'custom_featured_image_size'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'custom_featured_image_size',
                'option' => 'toplytics_settings',
                'input' => 'select',
                'disabled' => $this->checkSetting('skip_local_post_discovery'),
                'options' => get_intermediate_image_sizes(),
                'tooltip' => __('The image size name. See <a href="https://developer.wordpress.org/reference/functions/add_image_size/" target="_blank" title="Registering a new image size.">Add Image Size docs</a> for the name. Default: post-thumbnail', TOPLYTICS_DOMAIN),
            ]
        );

        // Allowed post types
        add_settings_field(
            'allowed_post_types',
            $this->getLabel(__('Allowed post types', TOPLYTICS_DOMAIN), 'allowed_post_types'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'allowed_post_types',
                'option' => 'toplytics_settings',
                'input' => 'text',
                'disabled' => $this->checkSetting('skip_local_post_discovery'),
                'tooltip' => __('A comma (,) separated list of allowed post types to allow in the feed. Default: post', TOPLYTICS_DOMAIN),
            ]
        );

        // Ignore posts ids
        add_settings_field(
            'ignore_posts_ids',
            $this->getLabel(__('Ignore Post IDs', TOPLYTICS_DOMAIN), 'ignore_posts_ids'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'ignore_posts_ids',
                'option' => 'toplytics_settings',
                'input' => 'text',
                'disabled' => $this->checkSetting('skip_local_post_discovery'),
                'tooltip' => __('A comma (,) separated list of post ids (any post type including pages) to be ignored. Default: Empty', TOPLYTICS_DOMAIN),
            ]
        );

        // Skip local post discovery
        add_settings_field(
            'skip_local_post_discovery',
            $this->getLabel(__('Skip Local Post Discovery', TOPLYTICS_DOMAIN), 'skip_local_post_discovery'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'skip_local_post_discovery',
                'tooltip' => __(" Take care! As the Analytics API only returns the permalink and pageviews, local post discovery searches the database for more post related information, such as post type. If you enable this option, we'll try to generate a human readable title from your post URLs, which will work only if you're using pretty permalinks. Checking this option affects the settings above and limits the amount of data you have available in the JSON output. After you change this setting, you will also need to re-fetch from Google the data. Default: Disabled", TOPLYTICS_DOMAIN),
            ]
        );

        // Set custom domain for local post discovery
        add_settings_field(
            'custom_domain',
            $this->getLabel(__('Custom Domain', TOPLYTICS_DOMAIN), 'custom_domain'),
            [$this, 'printInput'],
            'toplytics',
            'toplytics_settings',
            [
                'id' => 'custom_domain',
                'option' => 'toplytics_settings',
                'input' => 'text',
                'disabled' => !$this->checkSetting('skip_local_post_discovery'),
                'tooltip' => __('This works together with local post discovery, since GA will not give us the domain in the URL and we need to build it using this custom domain. Enable "Skip Local Post Discovery" and then this will be enabled. The domain needs to include the protocol, but not the final slash. Default: ', TOPLYTICS_DOMAIN) . get_home_url(),
            ]
        );
    }

    /**
     * We are outputing the settings header for our settings page.
     *
     * TODO: This should go in a blade template in the future.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function getSettingsHeader()
    {
        echo '<p>' . __('Configure your Toplytics the way you want. These settings are global for all of your widgets.', TOPLYTICS_DOMAIN) . '</p>';
    }

    /**
     * We setup the label for each setting if requested.
     * TODO: This should go in a blade template in the future.
     *
     * @since 4.0.0
     *
     * @return string The label formated as an HTML element
     */
    public function getLabel($label, $id = '', $checkbox = false)
    {
        if (!empty($id) && $checkbox) {
            $label = "<label for='" . $id . "'>" . $label . "</label>";
        }

        return $label;
    }

    /**
     * We avoid creating each individual input by creating and outputing them
     * using a dynamic function that will add them for us.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function printInput($args)
    {
        $option = 'toplytics_settings';
        $settings = get_option($option);

        echo "<div style='display: table; width: 100%;'>";
            echo "<div>";

        //Text
        if (!empty($args['input']) && ($args['input'] == 'text' || $args['input'] == 'color')) {
            echo "<input type='text' id='" . $args['id'] . "' name='" . $option . "[" . $args['id'] . "]' value='" . (!empty($settings[$args['id']]) ? $settings[$args['id']] : '') . "' placeholder='" . (!empty($args['placeholder']) ? $args['placeholder'] : '') . "' " . (!empty($args['disabled']) && $args['disabled'] ? 'readonly="readonly"' : '') . " />";
        }

        //Select
        elseif (!empty($args['input']) && $args['input'] == 'select') {
            echo "<select id='" . $args['id'] . "' name='" . $option . "[" . $args['id'] . "]' " . (!empty($args['disabled']) && $args['disabled'] ? 'disabled' : '') . ">";
            foreach ($args['options'] as $value => $title) {
                    echo "<option value='" . $value . "' ";
                if (!empty($settings[$args['id']]) && $settings[$args['id']] == $value) {
                    echo "selected";
                }
                    echo ">" . $title . "</option>";
            }
                    echo "</select>";
        }

        //Checkbox + Toggle
        else {
            echo "<input type='checkbox' id='" . $args['id'] . "' name='" . $option . "[" . $args['id'] . "]' value='1' style='display: block; margin: 0px;' ";
            if (!empty($settings[$args['id']]) && $settings[$args['id']] == "1") {
                echo "checked";
            }
            echo (!empty($args['disabled']) && $args['disabled'] ? 'disabled' : '') . " >";
        }

            echo "</div>";

        if (!empty($args['tooltip'])) {
            echo "<div style='display: table; height: 100%; width: 100%;'>";
                echo "<div style='display: table-cell; vertical-align: middle;'>";
                    echo "<span>" . $args['tooltip'] . "</span>";
                echo "</div>";
            echo "</div>";
        }
        echo "</div>";
    }

    /**
     * This will define our basic default settings for the settings page.
     *
     * @since 4.0.0
     *
     * @return array Filtered default settings
     */
    private function getDefaultSettings()
    {
        $defaults = [
            'enable_json' => '0',
            'skip_local_post_discovery' => '0',
            'custom_domain' => get_home_url(),
            'enable_rest_endpoint' => '1',
            'json_path' => 'toplytics.json',
            'fetch_month' => '1',
            'fetch_week' => '1',
            'fetch_today' => '0',
            'fetch_realtime' => '0',
            'custom_output_post_variables' => '',
            'include_featured_image_in_json' => '0',
            'custom_featured_image_size' => 'post-thumbnail',
            'allowed_post_types' => 'post',
            'ignore_posts_ids' => '',
            'max_posts_fetch_limit' => '20',
            'cron_exec_interval' => 'hourly',
        ];

        return apply_filters('defaultToplyticsSettings', $defaults);
    }

    /**
     * This action clears the selected profile from options so we can
     * selecte a new profile on the next load.
     *
     * @since 4.0.0
     *
     * @return Redirect
     */
    public function switchProfile()
    {
        if (empty($_POST['ToplyticsSubmitProfileSwitch'])) {
            return false;
        }

        check_admin_referer('toplytics-settings');

        update_option('toplytics_profile_data', false);
        update_option('toplytics_property_id', false);

        $this->window->successRedirect(__('Ok. Go ahead and select another profile.', TOPLYTICS_DOMAIN));
    }

    /**
     * We select the profile and do the initial data read from Google.
     *
     * @since 3.0
     *
     * @return redirect Send us to the plugin settings overview page
     */
    public function profileSelect()
    {
        if (empty($_POST['ToplyticsProfileSelect']) || (empty($_POST['profile_id']) && empty($_POST['property_id']))) {
            return false;
        }

        if ($this->use_ga4) {
            $property_id = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);

            if (!$property_id) $this->window->errorRedirect(__('Invalid property ID. Please use a valid one!', TOPLYTICS_DOMAIN));

            update_option('toplytics_property_id', $property_id);
        } else {
            $this->finish_setup_for_ua();
        }

        $this->window->successRedirect(__('Well done. You have selected your analytics profile.', TOPLYTICS_DOMAIN));
    }

    protected function finish_setup_for_ua() {
        foreach ($this->getProfilesList() as $profile_id => $profile_info) {
            if ($_POST['profile_id'] == $profile_id) {
                update_option(
                    'toplytics_profile_data',
                    [
                        'profile_id'   => $profile_id,
                        'profile_info' => $profile_info,
                    ]
                );
                // We sleep here a bit to make sure the data is set in DB
                sleep(5);
                $update = $this->updateAnalyticsData();
                break;
            }
        }
    }

    /**
     * This function is called upon saving the
     * Toplytics settings. It allows performing actions
     * before the settings are updated in the database.
     *
     * @since 4.0.1
     *
     * @param array $updated_settings The updated set of settings
     * @return array The updated settings, after processing
     */
    public function act_before_settings_update( $updated_settings ) {
        // Fetch the current set of Toplytics settings from the DB.
        $current_settings = get_option( 'toplytics_settings' );
        // If certain settings have changed, trigger an update of the analytics data.
        if ( ( $updated_settings['include_featured_image_in_json'] != $current_settings['include_featured_image_in_json'] ) ||
                ( $updated_settings['custom_featured_image_size'] != $current_settings['custom_featured_image_size'] ) ||
                ( $updated_settings['allowed_post_types'] != $current_settings['allowed_post_types'] ) ||
                ( $updated_settings['ignore_posts_ids'] != $current_settings['ignore_posts_ids'] ) ) {
            // Schedule a late call for updating the analytics data.
            add_action( 'shutdown', array( $this, 'updateAnalyticsDataOnSettingsUpdate' ) );
        }

        return $updated_settings;
    }

    /**
     * Called after updating plugins settings, to
     * trigger an update of the analytics data, using
     * the latest version of the settings from the database.
     *
     * @since 4.0.1
     */
    public function updateAnalyticsDataOnSettingsUpdate() {
        // First, refetch the latest version of the settings from the database.
        $this->settings = get_option( 'toplytics_settings' );
        // Now trigger the update.
        $this->updateAnalyticsData();
    }

    /**
     * We update our database with the latest data from GA.
     * We do 2 separate data fetchings for normal data and realtime
     * which we parse the same way.
     *
     * @since 4.0.0
     *
     * @return bool The status of the update
     */
    public function updateAnalyticsData()
    {
        // At this point we get and process the normal Analytics data
        $is_updated = false;

        // Do an initial fetch of the regular GA stats and store them in the database.
        $is_updated += $this->updateAnalyticsDataResults();
        // $is_updated += $this->updateAnalyticsRealTimeDataResults();

        // Maybe also update category posts data and top posts data.
        if ( $this->_need_additional_posts_data ) {
            $this->update_additional_posts_data();
        }

        update_option('toplytics_last_update_status', [
            'time' => time(),
            'count' => $is_updated,
        ]);

        return $is_updated;
    }

    /**
     * We update our database with the latest data from GA.
     * We do 2 separate data fetchings for normal data and realtime
     * which we parse the same way.
     *
     * @since 4.0.0
     *
     * @param boolean Flag indicating whether to fetch a higher number of posts.
     * @return bool The status of the update
     */
    public function updateAnalyticsDataResults( $extended_fetch = false )
    {
        try {
            $data = $this->getAnalyticsData( $extended_fetch );
        } catch (Exception $e) {
            if (401 == $e->getCode()) {
                $this->_increment_gapi_errors_count()->_check_gapi_errors_threshold();
            }
            error_log('Toplytics: Unexepected disconnect [regular] [' . $e->getCode() . ']: ' . $e->getMessage(), E_USER_ERROR);
            return false;
        }

        $this->_reset_gapi_errors_count();

        $num_stats = 0;
        foreach ($data as $when => $stats) {
            $result = [];
            if (is_array($stats) && $stats) {
                $result['result'] = $this->convertDataToPosts($stats, $when);
                // If this is the inital fetch for GA posts, check if there are enough to render in the widgets.
                if ( ! $extended_fetch && ! $this->_have_enough_results( $result['result'], $when ) ) {
                    $num_stats = 0;
                    break;
                }
                $result['_ts'] = time();
                update_option("toplytics_result_$when", $result);
                $num_stats += count($stats);
            }
        }

        if ( $num_stats == 0 ) {
            // Do another fetch, requesting a higher number of results.
            $num_stats = $this->updateAnalyticsDataResults( true );
        }

        return $num_stats;
    }

    /**
     * We update our database with the latest data from GA.
     * We do 2 separate data fetchings for normal data and realtime
     * which we parse the same way.
     *
     * @since 4.0.0
     *
     * @param boolean Flag indicating whether to fetch a higher number of posts.
     * @return bool The status of the update
     */
    public function updateAnalyticsRealTimeDataResults( $extended_fetch = false )
    {
        try {
            $realtime = $this->getAnalyticsRealTimeData( $extended_fetch );
        } catch (Exception $e) {
            if (401 == $e->getCode()) {
                $this->_increment_gapi_errors_count()->_check_gapi_errors_threshold();
            }
            error_log('Toplytics: Unexepected disconnect [realtime] [' . $e->getCode() . ']: ' . $e->getMessage(), E_USER_ERROR);
            return false;
        }

        $this->_reset_gapi_errors_count();

        $num_stats = 0;
        if ( $realtime ) {
            $results = $this->convertDataToPosts($realtime, 'realtime');
            // If this is the inital fetch for GA posts, check if there are enough to render in the widgets.
            if ( ! $extended_fetch && ! $this->_have_enough_results( $results, 'realtime' ) ) {
                $num_stats = $this->updateAnalyticsRealTimeDataResults( true );
            } else {
                update_option("toplytics_result_realtime", [
                    'result' => $results,
                    '_ts' => time(),
                ]);
                $num_stats = count( $realtime );
            }
        }

        return $num_stats;
    }

    /**
     * When called, we update the data regarding the latest posts
     * for the categories used in the Toplytics widget and the
     * overall top posts in the database, based on number of comments.
     */
    public function update_additional_posts_data()
    {
        // Fetch the widgets data from the database, if not already retrieved.
        $this->_maybe_fetch_widgets_data();

        // Determine the maximum number of posts.
        $max_num_posts = 0;
        foreach ( $this->_widgets as $widget ) {
            if ( ! empty( $widget['category'] ) ) {
                if ( $max_num_posts < $widget['numberposts'] ) {
                    $max_num_posts = $widget['numberposts'];
                }
            }
        }

        // Now fetch posts for each category and the latest posts, if needed.
        if ( $max_num_posts > 0 ) {
            // Fetch posts for categories.
            $data = [
                        'result' => [ 'categories' => [] ],
                        '_ts' => time(),
                    ];
            foreach ( $this->_widgets as $widget ) {
                if ( $widget['category'] ) {
                    $posts = get_posts( array(
                                        'posts_per_page' => $max_num_posts,
                                        'cat' => $widget['category'],
                                        'post_status' => 'publish',
                                    ) );
                    $data['result']['categories'][ $widget['category'] ] = [];
                    foreach ( $posts as $cat_post ) {
                        $data['result']['categories'][ $widget['category'] ][ $cat_post->ID ] = array(
                                                                                                    'permalink' => get_permalink( $cat_post ),
                                                                                                    'title' => get_the_title( $cat_post ),
                                                                                                    'featured_image' => $this->_get_featured_image( $cat_post->ID ),
                                                                                                );
                    }
                }
            }
            update_option( 'toplytics_result_categories', $data );

            // Fetch top posts.
            $data = [
                        'result' => [ 'top_posts' => [] ],
                        '_ts' => time(),
                    ];
            $posts = get_posts( array(
                                    'posts_per_page' => $max_num_posts,
                                    'cat' => $widget['category'],
                                    'orderby' => 'comment_count',
                                    'post_status' => 'publish',
                                ) );
            foreach ( $posts as $top_post ) {
                $data['result']['top_posts'][ $top_post->ID ] = array(
                                                                    'permalink' => get_permalink( $top_post ),
                                                                    'title' => get_the_title( $top_post ),
                                                                    'featured_image' => $this->_get_featured_image( $top_post->ID ),
                                                                );
            }
            update_option( 'toplytics_result_top_posts', $data );
        }

    }

    /**
     * We need to make sure that the var is set and that it contains
     * a proper value before using it.
     *
     * TODO: Remove required value as it's not being used.
     *
     * @since 4.0.0
     *
     * @return bool the settings status
     */
    public function checkSetting($var, $requiredValue = null)
    {
        $status = isset($this->settings[$var]) && $this->settings[$var];

        if (isset($this->settings[$var]) && !is_null($requiredValue)) {
            $status = ($this->settings[$var] === $requiredValue);
        }

        return $status;
    }

    /**
     * We use a separate method to read the Google realtime
     * Analytics data and store them in our options.
     *
     * @since 4.0.0
     *
     * @param boolean Flag indicating whether to fetch a higher number of posts.
     * @return array The realtime filtered data recived from GA.
     * @throws \Google\Service\Exception on server side error (ie: not authenticated,
     *  invalid or malformed post body, invalid url)
     */
    private function getAnalyticsRealTimeData( $extended_fetch = false )
    {
        if ( ! $this->checkSetting('fetch_realtime') ) {
            return [];
        }

        // data_realtime
        $optParams = [
            'dimensions' => 'rt:pagePath',
            'sort'        => '-rt:activeUsers',
            'max-results' => $this->checkSetting('max_posts_fetch_limit') ? (int)$this->settings['max_posts_fetch_limit'] : TOPLYTICS_MAX_RESULTS,
        ];

        if ( $extended_fetch ) {
            // Retrieve extra posts.
            $optParams['max-results'] += TOPLYTICS_NUM_EXTRA_RESULTS;
        }

        $results = [];
        $profile_id = get_option('toplytics_profile_data')['profile_id'];

        if ($profile_id) {
            try {
                $data = $this->service->data_realtime->get(
                    'ga:' . $profile_id,
                    'rt:activeUsers',
                    $optParams
                );

                if ($data->getRows()) {
                    foreach ($data->getRows() as $item) {
                        $results[ $item[0] ] = $item[1];
                    }
                }
            } catch (GoogleServiceException $e) {
              // Handle API service exceptions.
                error_log($e->getMessage());
            }
        }

        return $results;
    }

    /**
     * This is the main place where we read the Google
     * Analytics data and store them in our options.
     *
     * @since 3.0.0
     *
     * @param boolean Flag indicating whether to fetch a higher number of posts.
     * @return array The filtered data recived from GA.
     */
    private function getAnalyticsData( $extended_fetch = false )
    {
        $propertyId = get_option('toplytics_property_id', false);

        if (!$propertyId) return false;
        
        $propertyId = 'properties/'.$propertyId;
        
        $is_updated = 0;
        
        $dimension = new Google_Service_AnalyticsData_Dimension([
            'name' => 'pagePath',
        ]);
        
        $metric = new Google_Service_AnalyticsData_Metric([
            'name' => 'eventCount',
        ]);
        
        $orderMetric = new Google_Service_AnalyticsData_MetricOrderBy();
        $orderMetric->setMetricName('eventCount');

        $orderBy = new Google_Service_AnalyticsData_OrderBy();
        $orderBy->setMetric($orderMetric);
        $orderBy->setDesc(true);

        $orderBys = [$orderBy];

        $limit = $this->checkSetting('max_posts_fetch_limit') ? (int)$this->settings['max_posts_fetch_limit'] : TOPLYTICS_MAX_RESULTS;

        if ( $extended_fetch ) {
            // Retrieve extra posts.
            $limit += TOPLYTICS_NUM_EXTRA_RESULTS;
        }

        // TODO: Make these settings configurable.
        $results_ranges = [
            'month' => date_i18n('Y-m-d', strtotime('-29 days')),
            'week'  => date_i18n('Y-m-d', strtotime('-6 days')),
            // 'today' => date_i18n('Y-m-d', strtotime('today')),
            // 'realtime' => 0,
        ];

        $result = [];

        foreach ( $results_ranges as $when => $start_date ) {
                // We make sure fetching is enabled in settings
                if ( ! $start_date || ! $this->checkSetting( 'fetch_' . $when ) ) {
                    continue;
                }

            $dateRange = new Google_Service_AnalyticsData_DateRange([
                'start_date' => $start_date,
                'end_date' => date_i18n('Y-m-d', time()),
            ]);

            $request = new Google_Service_AnalyticsData_RunReportRequest( apply_filters('toplytics_analytics_params_v4', [
                'property' => $propertyId,
                'date_ranges' => [$dateRange],
                'dimensions' => [$dimension],
                'metrics' => [$metric],
                'limit' => $limit,
                'dimension_filter' => new Google_Service_AnalyticsData_FilterExpression([
                    'filter' => new Google_Service_AnalyticsData_Filter([
                        'field_name' => 'eventName',
                        'string_filter' => new Google_Service_AnalyticsData_StringFilter([
                            'value' => 'page_view',
                        ]),
                    ]),
                ]),
                'order_bys' => $orderBys,
            ], $when, $start_date));
            
            $response = $this->service->properties->runReport($propertyId, $request);
            
            $result[ $when ] = [];

            if ($response) {
                foreach ($response->getRows() as $row) {
                    $result[ $when ][ $row->getDimensionValues()[0]->getValue() ] = $row->getMetricValues()[0]->getValue();
                }
            }

            apply_filters('toplytics_analytics_data_result', $result[ $when ], $when);
        }

        return apply_filters('toplytics_analytics_data_allresults', $result);
    }

    /**
     * This is where we convert the data we got from GA and
     * assign it to it's specific post in our DB.
     *
     * @since 3.0.0
     * @param array $data The array of data recived from GA
     * @param string $when The time period for which to convert
     *
     * @return array The new filtered and existing data
     */
    private function convertDataToPosts($data, $when)
    {
        $new_data = [];

        if ($this->checkSetting('ignore_posts_ids')) {
            $ignored_posts = explode(',', preg_replace('/\s+/', '', $this->settings['ignore_posts_ids']));
        }

        // TODO: Improve this IF and explode it in multiple functions to prevent repeated code.
        if ( $this->checkSetting( 'skip_local_post_discovery' ) ) {
            // Fetch the custom domain.
            $custom_domain = $this->settings['custom_domain'];
            if ( $custom_domain === '' ) {
                // By default, use the domain of the current site.
                $custom_domain = home_url();
            }

            // We bypass the local post discovery and simply use the URL straight from GA.
            $counter = 0;
            foreach ( $data as $rel_path => $pageviews ) {
                $rel_path = apply_filters( 'toplytics_rel_path', $rel_path, $when );
                $url = $custom_domain . $rel_path;
                if ( ! isset( $new_data[ $when ][ $counter ]['permalink'] ) ) {
                    $new_data[ $when ][ $counter ]['permalink'] = $url;
                }
                if ( ! isset( $new_data[ $when ][ $counter ]['title'])) {
                    $new_data[ $when ][ $counter ]['title'] = ucwords( str_replace( ['/', '-'], ' ', stripslashes( $rel_path ) ) );
                }
                // Pageviews counting
                if ( isset( $new_data[ $when ][ $counter ]['pageviews'] ) ) {
                    $new_data[ $when ][ $counter ]['pageviews'] += (int) $pageviews;
                } else {
                    $new_data[ $when ][ $counter ]['pageviews'] = (int) $pageviews;
                }

                $counter++;
            }
        } else {
            foreach ($data as $rel_path => $pageviews) {
                $rel_path = apply_filters('toplytics_rel_path', $rel_path, $when);
                $url      = home_url() . $rel_path;
                $post_id  = url_to_postid($url);
                $url      = apply_filters('toplytics_convert_data_url', $url, $when, $post_id, $rel_path, $pageviews);
                $allowed_post_types = apply_filters('toplytics_allowed_post_types', $this->checkSetting('allowed_post_types') ? explode(',', preg_replace('/\s+/', '', $this->settings['allowed_post_types'])) : ['post']);
                $post_type = get_post_type($post_id);

                if (( 0 < $post_id ) && in_array($post_type, $allowed_post_types)) {
                    // We make sure the user did not ignore this post
                    if (isset($ignored_posts) && in_array($post_id, $ignored_posts)) {
                        continue;
                    }

                    // $new_data = [pageviews, featured_image_url, ...] (can include custom vars)

                    $post = get_post($post_id);
                    if (is_object($post)) {
                        //Required data: permalink, title
                        if (!isset($new_data[$when][ $post_id ]['permalink'])) {
                            $new_data[$when][ $post_id ]['permalink'] = get_permalink($post);
                        }
                        if (!isset($new_data[$when][ $post_id ]['title'])) {
                            $new_data[$when][ $post_id ]['title'] = get_the_title($post);
                        }

                        // Pageviews counting
                        if (isset($new_data[$when][ $post_id ]['pageviews'])) {
                            $new_data[$when][ $post_id ]['pageviews'] += (int) $pageviews;
                        } else {
                            $new_data[$when][ $post_id ]['pageviews'] = (int) $pageviews;
                        }

                        // Categories - IDs.
                        $new_data[$when][ $post_id ]['categories'] = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );

                        // Featured image
                        $new_data[$when][$post_id]['featured_image'] = $this->_get_featured_image( $post_id );

                        // Custom Post Variables
                        if ($this->checkSetting('custom_output_post_variables')) {
                            $vars = explode(',', preg_replace('/\s+/', '', $this->settings['custom_output_post_variables']));
                            foreach ($vars as $var) {
                                if (property_exists($post, $var) && !isset($new_data[$when][$post_id][$var])) {
                                    $new_data[$when][$post_id][$var] = $post->$var;
                                }
                            }
                        }
                    }
                }
            }
        }

        // sort the results (revert order)
        // arsort($new_data);

        return apply_filters('toplytics_convert_data_to_posts', $new_data, $data, $when);
    }

    /**
     * Fetch the featured image for the post ID.
     *
     * @param integer $post_id The ID of the post
     * @param string The URL of the featured image
     */
    private function _get_featured_image( $post_id )
    {
        $featured_image = $image_size = ''; // Sensible default.
        if ( $this->checkSetting( 'include_featured_image_in_json' ) ) {
            // Check if a value is set for the featured image size.
            if ( $this->checkSetting('custom_featured_image_size') ) {
                // Fetch the list of image sizes active for the site - numeric array.
                $theme_sizes = get_intermediate_image_sizes();
                // Set the image size if the selected index is in the list of available sizes.
                if ( isset( $theme_sizes[ $this->settings['custom_featured_image_size'] ] ) ) {
                    $image_size = $theme_sizes[ $this->settings['custom_featured_image_size'] ];
                }
            }
            // Set "post-thumbnail" as default image size if no other configured.
            if ( ! $image_size ) {
                $image_size = 'post-thumbnail';
            }
            // Now fetch the URL of the featured image.
            $featured_image = get_the_post_thumbnail_url( $post_id, $image_size );
        }
        return $featured_image;
    }

    /**
     * This is were we connect with the Google services and renew
     * our access tokens when they expire.
     *
     * For managing the connection we use the class variable $client
     *
     * @since   4.0.0
     *
     * @return  bool The result of the connection.
     */
    protected function serviceConnect()
    {
        if (!$this->client) {
            return false;
        }

        $googleToken = get_option('toplytics_google_token');

        if (! $googleToken) {
            $authCode = get_option('toplytics_auth_code');

            if (! $authCode) {
                return false;
            }

            try {
                $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);

                if (isset($accessToken['error']) && $accessToken['error']) {
                    update_option('toplytics_auth_code', false);

                    $this->window->notifyAdmins('error', __(
                        'Google authorization went wrong. Try again.',
                        TOPLYTICS_DOMAIN
                    ));

                    return false;
                }

                $googleToken = $this->client->getAccessToken();
                update_option('toplytics_google_token', $googleToken);
            } catch (Exception $e) {
                $this->window->notifyAdmins('warning', "Toplytics error: " . $e->getMessage());

                return false;
            }
        }

        $this->client->setAccessToken($googleToken);

        if ($this->client->isAccessTokenExpired()) {
            if (isset($googleToken['refresh_token']) && $refresh_token = $googleToken['refresh_token']) {
                $this->client->refreshToken($refresh_token);
                update_option('toplytics_google_token', $this->client->getAccessToken());
            }
        }

        return true;
    }

    /**
     * Register the admin page as a submenu on the Settings Menu.
     *
     * @since    4.0.0
     *
     * @return   void
     */
    public function registerPluginSettingsPage()
    {
        add_submenu_page(
            TOPLYTICS_SUBMENU_PAGE,
            __('Toplytics Settings', TOPLYTICS_DOMAIN),
            __('Toplytics', TOPLYTICS_DOMAIN),
            'manage_options',
            'toplytics',
            [$this, 'openWindowToSettingsView']
        );
    }

    /**
     * This is where we setup our CRON event to run every hour
     * to update the search results.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function setupScheduleEvent()
    {
        if (! wp_next_scheduled('toplytics_cron_event')) {
            wp_schedule_event(time(), $this->checkSetting('cron_exec_interval') ? $this->settings['cron_exec_interval'] : 'hourly', 'toplytics_cron_event');
        }
    }

    /**
     * This is the main method that loads up the actual view of
     * our application based on our authorization status.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function openWindowToSettingsView()
    {
        if (get_option('toplytics_google_token')) {
            $profile = get_option('toplytics_profile_data');

            $profiles = false;

            if (!$profile && !$this->use_ga4) {
                $profiles = $this->getProfilesList();
            }

            $lastUpdateStatus = get_option('toplytics_last_update_status');
            if ($lastUpdateStatus && $lastUpdateStatus['count'] > 0) {
                $lastUpdateTime = date_i18n('l, d-M-y H:i:s T', $lastUpdateStatus['time']);
                $lastUpdateCount = (string)$lastUpdateStatus['count'];
            } else {
                $lastUpdateTime = 'Never updated.';
                $lastUpdateCount = '-';
            }

            $auth = get_option('toplytics_auth_type') == 'private' ? 'private' : 'public';

            if (md5_file(TOPLYTICS_FOLDER_ROOT . 'resources/views/frontend/widget.template.php') !== TOPLYTICS_WIDGET_TEMPLATE_VERSION) {
                $this->window->notifyAdmins('warning', __('WARNING! You have modified the default template file. On a plugin update you will lose these changes. Please copy the template and rename it to custom.template.php to prevent the customization from being lost.', TOPLYTICS_DOMAIN), false, '', true);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Toplytics Debug: The new md5 of the widget file is ' . md5_file(TOPLYTICS_FOLDER_ROOT . 'resources/views/frontend/widget.template.php'));
                }
            }

            $use_ga4 = $this->use_ga4;
            $property_id = get_option('toplytics_property_id', false);

            include $this->window->getView( 'backend.settings' );

            return;
        }

        $appRedirectURL = $this->window->getSettingsLink();
        $isDirtyAuth = $this->isDirtyAuth();

        // We are not authenticated if we got here.
        include $this->window->getView( 'backend.authorization' );
    }

    /**
     * This is where we build up the google authorization link based
     * on the Auth config.
     *
     * @since 4.0.0
     *
     * @return bool|string The authorization link
     */
    public function getGoogleAuthLink()
    {

        $googleconfig = $this->getAuthConfig();

        if (!$googleconfig) {
            return false;
        }

        $url = http_build_query([
            'next'            => $this->window->getSettingsLink(),
            'scope'           => 'https://www.googleapis.com/auth/analytics.readonly',
            'response_type'   => 'code',
            'state'           => $this->window->getSettingsLink(),
            'redirect_uri'    => $googleconfig->redirect_uris[0],
            'client_id'       => $googleconfig->client_id,
            'access_type'     => 'offline',
            'approval_prompt' => 'force',
        ]);

        return 'https://accounts.google.com/o/oauth2/auth?' . $url;
    }

    /**
     * This is the entry point that listens for the authorization
     * response from Google / our custom API.
     *
     * @since 4.0.0
     *
     * @return Redirect To the plugin settings page.
     */
    public function checkAuthorization()
    {
        if (isset($_GET['code']) && $_GET['page'] === 'toplytics') {
            if (isset($_GET['status']) && $_GET['status'] && $_GET['status'] == 'error') {
                $get_error = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                if ($get_error == 'access_denied') {
                    $this->window->errorRedirect(__('You have canceled the auth process.', TOPLYTICS_DOMAIN));
                }

                $this->window->errorRedirect(__(
                    'Google sent an error. Authorization faild. Error code: ',
                    TOPLYTICS_DOMAIN
                ) . $get_error);
            }

            $authCode = sanitize_text_field(wp_unslash($_GET['code']));

            update_option('toplytics_auth_code', $authCode);

            // There is a race condition if the DB is not fast enough
            // or there is any replication lag on distributed sys.
            sleep(2);

            $this->window->successRedirect(__(
                'We got your Google code. Now select a profile to start.',
                TOPLYTICS_DOMAIN
            ));
        }
    }

    /**
     * We build up the remote config URI based on the global
     * configuration in the plugin main file.
     *
     * @since 4.0.0
     * @param string $subdir Any subdirectories needed (read from config)
     * @param string $file The end file that we need to read (read from config)
     *
     * @return string The remote config path
     */
    public function getRemoteConfigUri($subdir = '', $file = '')
    {
        $base = TOPLYTICS_AUTH_API_BASE_URL . 'v' . TOPLYTICS_AUTH_API_VERSION . '/';

        if (!$subdir && !$file) {
            return $base . TOPLYTICS_AUTH_API_BASE_CONFIG . '?nocache';
        }

        return $base . $subdir . '/' . $file . '.json?nocache';
    }

    /**
     * The public auth API decode takes place here. We have
     * an extra config file to specify what config files
     * to read and where they are located so we don't
     * need to update the plugin every time we need
     * to add or remove a certain API key.
     *
     * @since 4.0.0
     * @param string $config The JSON encoded config string
     *
     * @return bool|object The decoded JSON config
     */
    public function decodeApiConfig($config = '')
    {
        if (!$config = $this->decodeConfig($config)) {
            return false;
        }

        if (!isset($config->subdir) || !$config->subdir || !isset($config->files) || !$config->files) {
            return false;
        }

        return $config;
    }

    /**
     * Decoding the google config and doing some validation
     * for the decoded data.
     *
     * @since 4.0.0
     * @param string $config The JSON encoded config
     *
     * @return bool|object The decoded Google web config
     */
    public function decodeGoogleConfig($config = '')
    {
        if (!$config = $this->decodeConfig($config)) {
            return false;
        }

        // Old style config - backward compatibility
        if (isset($config->installed) || isset($config->installed->client_id) || isset($config->installed->client_secret)) {
            return $config->installed;
        }

        if (!isset($config->web) || !isset($config->web->client_id) || !isset($config->web->client_secret)) {
            return false;
        }

        return $config->web;
    }

    /**
     * Decoding the actual JSON config file we read from
     * the presslabs.org site for public authorization.
     *
     * @since 4.0.0
     * @param string $config The JSON content to be decoded
     *
     * @return bool|object The decoded JSON
     */
    public function decodeConfig($config = '')
    {
        if (!is_string($config) || !$config) {
            return false;
        }

        $config = json_decode($config);
        if (!is_object($config)) {
            return false;
        }

        return $config;
    }

    /**
     * This is the place where we arrive after pressing the
     * Public Authorization button. Here we redirect the user
     * to the Public Google page.
     *
     * @return Redirect Redirect to Google Authorization
     */
    public function publicAuthorization()
    {
        if (empty($_POST['ToplyticsSubmitPublicAuthorization'])) {
            return false;
        }

        if (!check_admin_referer('toplytics-public-authorization')) {
            $this->window->notifyAdmins('error', __('Form expired. Try again.', TOPLYTICS_DOMAIN));
            return;
        }

        update_option('toplytics_auth_type', 'public');

        $authorizationLink = $this->getGoogleAuthLink();

        if ($authorizationLink) {
            return wp_redirect($authorizationLink);
        }

        return $this->window->errorRedirect('Authorization link build faild. Try the private method instead.');
    }

    /**
     * This is the where we arrive after submition of the Private
     * Authorization form, and we need to validate the
     * data entered by the user, and generate the redirect URL
     * for the Google Account.
     *
     * @since 4.0.0
     *
     * @return Redirect Redirect to Google Authorization
     */
    public function privateAuthorization()
    {
        if (empty($_POST['ToplyticsSubmitPrivateAuthorization'])) {
            return false;
        }

        if (!check_admin_referer('toplytics-private-authorization')) {
            $this->window->notifyAdmins('error', __('Form expired. Try again.', TOPLYTICS_DOMAIN));
            return;
        }

        $clientID = filter_input(
            INPUT_POST,
            'toplytics-private-client-id',
            FILTER_SANITIZE_FULL_SPECIAL_CHARS
        );
        $clientSecret = filter_input(
            INPUT_POST,
            'toplytics-private-client-secret',
            FILTER_SANITIZE_FULL_SPECIAL_CHARS
        );
        $redirectURI = filter_var(
            filter_input(INPUT_POST, 'toplytics-private-redirect', FILTER_SANITIZE_URL),
            FILTER_VALIDATE_URL
        );

        if (!$clientID || !$clientSecret || !$redirectURI) {
            $this->window->notifyAdmins(
                'error',
                __(
                    'Form validation error. Please check the details you have entered below and try again.',
                    TOPLYTICS_DOMAIN
                )
            );
            return;
        }

        $config = (object)[
            'client_id' => $clientID,
            'client_secret' => $clientSecret,
            'redirect_uris' => [$redirectURI]
        ];

        update_option('toplytics_private_auth_config', $config);
        update_option('toplytics_auth_type', 'private');

        return wp_redirect($this->getGoogleAuthLink());
    }

    public function isDirtyAuth()
    {
        $dirt = 0;

        $options = [
            'toplytics_private_auth_config',
            'toplytics_profile_data',
            'toplytics_property_id',
            'toplytics_auth_config',
            'toplytics_google_token',
            'toplytics_auth_code',
        ];

        foreach ($options as $option) {
            if (get_option($option)) {
                $dirt++;
            }
        }

        if ($dirt > 1) {
            return true;
        }

        return false;
    }

    public function cleanDirtyAuth()
    {
        if (empty($_POST['ToplyticsCleanDirtyAuth'])) {
            return false;
        }

        check_admin_referer('toplytics-dirty-cleanup');

        $this->serviceDisconnect(true, true);

        $this->window->successRedirect(__(
            'Auth config clean-up completed.',
            TOPLYTICS_DOMAIN
        ));
    }

    public function checkBackwardsAuth($googleconfig)
    {
        // We try to decode the old-style auth config from the DB option.
        $googleconfig = $this->decodeGoogleConfig($googleconfig);

        // If we succeed, we'll revalidate and save the option.
        if ($googleconfig && $this->validateGoogleConfig($googleconfig, true)) {
            return $googleconfig;
        }

        return false;
    }

    public function doBackwardsPrivateAuth($config)
    {

        // We want this cleaned either way
        delete_option('toplytics_auth_config');

        if (!$oauth_token = get_option('toplytics_oauth2_remote_token')) {
            return false;
        }

        // Update the config object in DB for future use
        update_option('toplytics_private_auth_config', $config);
        update_option('toplytics_auth_type', 'private');
        update_option('toplytics_google_token', $oauth_token);

        // Clean old style config settings no matter the below result
        delete_option('toplytics_oauth2_remote_token');

        return true;
    }

    /**
     * Gets the authorization config based on the user data
     * or based on the remote config for public auth.
     *
     * @since 4.0.0
     *
     * @return object The valid (at this point) auth config
     */
    public function getAuthConfig()
    {
        $authType = get_option('toplytics_auth_type');

        if ($authType == 'private') {
            $googleconfig = get_option('toplytics_private_auth_config');
        } else {
            $googleconfig = get_option('toplytics_auth_config');
        }

        if (! $googleconfig && $authType != 'private') {
            $apiconfig = @file_get_contents($this->getRemoteConfigUri());
            $apiconfig = $this->decodeApiConfig($apiconfig);

            $googleconfig = @file_get_contents($this->getRemoteConfigUri(
                $apiconfig->subdir,
                $apiconfig->files[mt_rand(0, count($apiconfig->files) - 1)]
            ));

            $googleconfig = $this->decodeGoogleConfig($googleconfig);

            if ($this->validateGoogleConfig($googleconfig)) {
                update_option('toplytics_auth_config', $googleconfig);
                return $googleconfig;
            }

            return false;
        }

        // We arrive here if we do have set an old style auth in DB on the auth_config option.
        if ($new_ga_config = $this->checkBackwardsAuth($googleconfig)) {
            $this->doBackwardsPrivateAuth($new_ga_config);
            return $new_ga_config;
        }

        if (!$this->validateGoogleConfig($googleconfig)) {
            return false;
        }

        return $googleconfig;
    }

    /**
     * Validates the actual Google Config that needs to comply
     * with the standard google format.
     *
     * @since 4.0.0
     * @param array $googleconfig The google config candidate to validate
     *
     * @return bool The config is valid or not
     */
    public function validateGoogleConfig($googleconfig, $skipUriCheck = false)
    {
        if (!is_object($googleconfig)) {
            return false;
        }

        if (!isset($googleconfig->client_id) || ! $googleconfig->client_id) {
            return false;
        }

        if (!isset($googleconfig->client_secret) || ! $googleconfig->client_secret) {
            return false;
        }

        if (!isset($googleconfig->redirect_uris) ||
            ! $googleconfig->redirect_uris ||
            !is_array($googleconfig->redirect_uris)) {
            return false;
        }

        if ($skipUriCheck) {
            return true;
        }

        if (!filter_var($googleconfig->redirect_uris[0], FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    /**
     * Return all profiles of the current user from GA api.
     *
     * Return result type stored in WP options:
     *
     * Array(
     *  'profile_id' => 'account_name > property_name (Tracking ID) > profile_name',
     *  'profile_id' => 'account_name > property_name (Tracking ID) > profile_name',
     * )
     *
     * Note that the `Tracking ID` is the same with the `property_id`.
     *
     * @since 3.0.0
     *
     * @return array The final list to be displayed to the user.
     */
    public function getProfilesList()
    {
        try {
            $profiles_list = [];
            $profiles = $this->getProfiles();
            foreach ($profiles as $profile_id => $profile_data) {
                $profiles_list[ $profile_id ] =
                    $profile_data['account_name'] .
                    ' > ' . $profile_data['property_name'] .
                    ' (' . $profile_data['property_id'] .
                    ') > ' . $profile_data['profile_name'];
            }
            return $profiles_list;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Gets the list of available accounts for the user that
     * authorized us and we then get it's available profiles.
     *
     * @since 3.0.0
     *
     * @return array A list of profiles containing account and property data.
     */
    private function getProfiles()
    {
        $profiles = [];
        $accounts = $this->getAccounts();
        foreach ($accounts as $account_id => $account_name) {
            $webproperties = $this->getWebproperties($account_id);
            foreach ($webproperties as $web_prop_id => $web_prop_name) {
                $man_profiles = $this->service->management_profiles->listManagementProfiles($account_id, $web_prop_id);
                if (0 < count($man_profiles->getItems())) {
                    foreach ($man_profiles->getItems() as $item) {
                        $profiles[ $item->getId() ]['profile_name']  = $item->getName();
                        $profiles[ $item->getId() ]['account_id']    = $account_id;
                        $profiles[ $item->getId() ]['account_name']  = $account_name;
                        $profiles[ $item->getId() ]['property_id']   = $web_prop_id;
                        $profiles[ $item->getId() ]['property_name'] = $web_prop_name;
                    }
                }
            }
        }
        return $profiles;
    }

    /**
     * Gets the list of web properties for each account that
     * is available for the authorized Google Account.
     *
     * @since 3.0.0
     * @param int $account_id The account ID for which to get the property
     *
     * @return array The list of properties for this account ID
     */
    private function getWebproperties($account_id)
    {
        $manWebProperties = $this->service->management_webproperties->listManagementWebproperties($account_id);
        if (0 < count($manWebProperties->getItems())) {
            $webproperties = [];
            foreach ($manWebProperties->getItems() as $item) {
                $webproperties[ $item->getId() ] = $item->getName();
            }
            return $webproperties;
        } else {
            return [];
        }
    }

    /**
     * Gets the list of accounts available for the selected
     * Google account that authorized us.
     *
     * @since 3.0.0
     *
     * @return array The list of user accounts.
     */
    private function getAccounts()
    {
        $man_accounts = $this->service->management_accounts->listManagementAccounts();
        if (0 < count($man_accounts->getItems())) {
            $accounts = [];
            foreach ($man_accounts->getItems() as $item) {
                $accounts[ $item->getId() ] = $item->getName();
            }
            return $accounts;
        } else {
            return [];
        }
    }

    /**
     * Registering the extra action links for the plugin. These
     * links are displayed next to the Deactivate link on
     * the plugins listing page.
     *
     * @since 4.0.0
     * @param array  $links already defined meta links. (Ex: deactivate)
     *
     * @return array $links The new array which also contain our links.
     */
    public function pluginActionLinks($links)
    {
        global $toplytics_engine;

        // TODO: We should rewrite this entire IF more elegantly
        if ( get_option( 'toplytics_google_token' ) ) {
            $url = $this->window->getSettingsLink();
            $title = __( 'Settings', TOPLYTICS_DOMAIN );
            ob_start();
            include $this->window->getView( 'backend.partials.url' );
            $settings_link = ob_get_clean();

            $url = admin_url( 'widgets.php' );
            $title = __( 'Widgets', TOPLYTICS_DOMAIN );
            ob_start();
            include $this->window->getView( 'backend.partials.url' );
            $widgets_link = ob_get_clean();

            array_unshift( $links, $widgets_link, $settings_link );
        } else {
            $url = $this->window->getSettingsLink();
            $title = __( 'Connect Google Analytics', TOPLYTICS_DOMAIN );
            ob_start();
            include $this->window->getView( 'backend.partials.url' );
            $settings_link = ob_get_clean();

            array_unshift( $links, $settings_link );
        }

        return $links;
    }

    /**
     * Registering the extra meta links for the plugin. These are
     * the links displayed under the description of the plugin
     * on the plugins listing page.
     *
     * @since 4.0.0
     * @param array  $plugin_meta already defined meta links.
     * @param string $plugin_file plugin plugin_file path and name being processed.
     *
     * @return array $plugin_meta
     */
    public function extraRowMeta($plugin_meta, $plugin_file = null)
    {
        global $toplytics_engine;

        if ( ! is_null( $plugin_file ) && $plugin_file !== $this->plugin_basename ) {
            return $plugin_meta;
        }

        $url    = 'https://www.presslabs.com/code/toplytics/';
        $title  = __('Usage Documentation', TOPLYTICS_DOMAIN );
        $target = 'blank';
        $icon   = 'media-document';
        ob_start();
        include $this->window->getView( 'backend.partials.url' );
        $documentation_link = ob_get_clean();

        $url    = 'https://wordpress.org/support/plugin/toplytics/reviews/';
        $title  = __( 'Review us!', TOPLYTICS_DOMAIN );
        $target = 'blank';
        $icon   = 'format-status';
        ob_start();
        include $this->window->getView( 'backend.partials.url' );
        $review_link = ob_get_clean();

        $url    = 'https://wordpress.org/support/plugin/toplytics/';
        $title  = __( 'Need help? Support is here!', TOPLYTICS_DOMAIN );
        $target = 'blank';
        $icon   = 'businessman';
        ob_start();
        include $this->window->getView( 'backend.partials.url' );
        $support_link = ob_get_clean();

        $links = [
            $documentation_link,
            $review_link,
            $support_link,
        ];

        $plugin_meta = array_merge( $plugin_meta, $links );

        return $plugin_meta;
    }
}
