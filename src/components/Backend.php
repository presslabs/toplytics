<?php

namespace Toplytics;

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
     */
    private $window;
    private $client;
    private $service;

    /**
     * Initialize the backend class and set its properties.
     *
     * @since    4.0.0
     * @param      string    $plugin_basename       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     * @param      Window    $window     The window instance.
     */
    public function __construct($plugin_basename, $version, Window $window)
    {

        $this->plugin_basename = $plugin_basename;
        $this->version = $version;

        $this->window = $window;

        /**
         * We are initializing the Google Client for authorization
         * and the Google Service for data retirval.
         */
        $this->client = $this->initClient();
        $this->service = $this->initService();

        /**
         * If the initialization above worked, we then try to schedule
         * the CRON for data retrival.
         */
        if ($this->service) {
            add_action('wp', [ $this, 'setupScheduleEvent' ]);
            add_action('toplytics_cron_event', [ $this, 'updateAnalyticsData' ]);
        }
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    4.0.0
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

    /**
     * This function is responsible for initializing the Google
     * Client and authorizing us with the credentials we used.
     *
     * @since   4.0.0
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

        $client = new \Google_Client($clientConfig);
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
     */
    protected function initService()
    {
        if (!$this->client) {
            return false;
        }

        try {
            if ($this->serviceConnect()) {
                return new \Google_Service_Analytics($this->client);
            }
        } catch (\Google_Service_Exception $e) {
            $this->window->notifyAdmins('error', __(
                'Something went wrong while initiating the Analytics service.',
                TOPLYTICS_DOMAIN
            ));
        } catch (\Google_Auth_Exception $e) {
            $this->window->notifyAdmins('error', __(
                'Something went wrong with your Authorization process. Try to reset your connection to get this fixed.',
                TOPLYTICS_DOMAIN
            ));
        }

        return false;
    }

    /**
     * This is the place where we disconnect from the Google Services
     * by simply forgoting the options.
     *
     * @since   4.0.0
     * @return  void
     */
    public function serviceDisconnect($programatic = false)
    {
        if (empty($_POST['ToplyticsSubmitAccountDisconnect']) && !$programatic) {
            return;
        }

        if (!$programatic) {
            check_admin_referer('toplytics-settings');
        }

        if ($this->client) {
            $this->client->revokeToken();
        }

        update_option('toplytics_private_auth_config', false);
        update_option('toplytics_google_token', false);
        update_option('toplytics_profile_data', false);
        update_option('toplytics_auth_config', false);
        update_option('toplytics_auth_code', false);

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
    public function switchProfile()
    {
        if (empty($_POST['ToplyticsSubmitProfileSwitch'])) {
            return false;
        }

        check_admin_referer('toplytics-settings');

        update_option('toplytics_profile_data', false);

        $this->window->successRedirect(__('Ok. Go ahead and select another profile.', TOPLYTICS_DOMAIN));
    }

    public function profileSelect()
    {
        if (empty($_POST['ToplyticsProfileSelect']) || empty($_POST['profile_id'])) {
            return false;
        }

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

        $this->window->successRedirect(__('Well done. You have selected your analytics profile.', TOPLYTICS_DOMAIN));
    }
    
    public function updateAnalyticsData()
    {
        try {
            $data = $this->getAnalyticsData();
        } catch (\Exception $e) {
            if (401 == $e->getCode()) {
                $this->serviceDisconnect(true);
            }
            error_log('Toplytics: Unexepcted disconnect [' . $e->getCode() . ']: ' . $e->getMessage(), E_USER_ERROR);
            return false;
        }

        $is_updated = false;
        foreach ($data as $when => $stats) {
            if (is_array($stats) && ! empty($stats)) {
                $result['result'] = $this->convertDataToPosts($stats, $when);
                $result['_ts'] = time();
                update_option("toplytics_result_$when", $result);
                $is_updated += 1;
            }
        }

        update_option('toplytics_last_update_status', [
            'time' => time(),
            'count' => $is_updated,
        ]);

        return $is_updated;
    }
    
    /**
     * This is the main place where we read the Google
     * Analytics data and store them in our options.
     *
     * @since 3.0.0
     *
     * @return array The filtered data recived from GA.
     */
    private function getAnalyticsData()
    {
        $optParams = [
            'quotaUser'   => md5(home_url()),
            'dimensions'  => 'ga:pagePath',
            'sort'        => '-ga:pageviews',
            'max-results' => MAX_RESULTS,
        ];
        $result = [];
        $profile_id = get_option('toplytics_profile_data')['profile_id'];
        if ($profile_id) {
            foreach (get_option('toplytics_results_ranges') as $when => $start_date) {
                $filters = apply_filters('toplytics_analytics_filters', '', $when, 'ga:pageviews');
                if (! empty($filters)) {
                    $optParams['filters'] = $filters;
                }
                $data = $this->service->data_ga->get(
                    'ga:' . $profile_id,
                    $start_date,
                    date_i18n('Y-m-d', time()),
                    'ga:pageviews',
                    $optParams
                );
                apply_filters(
                    'toplytics_analytics_data',
                    $when,
                    $data->selfLink,
                    $data->modelData['query'],
                    $data->modelData['profileId']
                );
                $result[ $when ] = [];
                if ($data->rows) {
                    foreach ($data->rows as $item) {
                        $result[ $when ][ $item[0] ] = $item[1];
                    }
                }
                apply_filters('toplytics_analytics_data_result', $result[ $when ], $when);
            }
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

        foreach ($data as $rel_path => $pageviews) {
            $rel_path = apply_filters('toplytics_rel_path', $rel_path, $when);
            $url      = home_url() . $rel_path;
            $post_id  = url_to_postid($url);
            $url      = apply_filters('toplytics_convert_data_url', $url, $when, $post_id, $rel_path, $pageviews);
            $allowed_post_types = apply_filters('toplytics_allowed_post_types', ['post']);
            $post_type = get_post_type($post_id);

            if (( 0 < $post_id ) && in_array($post_type, $allowed_post_types)) {
                $post = get_post($post_id);
                if (is_object($post)) {
                    if (isset($new_data[ $post_id ])) {
                        $new_data[ $post_id ] += $pageviews;
                    } else {
                        $new_data[ $post_id ] = (int) $pageviews;
                    }
                }
            }
        }
        
        // sort the results (revert order)
        arsort($new_data);
        return apply_filters('toplytics_convert_data_to_posts', $new_data, $data, $when);
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
                $accessToken = $this->client->authenticate($authCode);

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
            } catch (\Exception $e) {
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
            wp_schedule_event(time(), 'hourly', 'toplytics_cron_event');
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
            
            if (!$profile) {
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

            $this->window->open(
                'backend.settings',
                compact(
                    'profile',
                    'profiles',
                    'lastUpdateTime',
                    'lastUpdateCount',
                    'auth'
                ),
                true
            );
            return;
        }

        $appRedirectURL = $this->window->getSettingsLink();

        // We are not authenticated if we got here.
        $this->window->open('backend.authorization', ['appRedirectURL' => $appRedirectURL], true);
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
                $get_error = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
                

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
                'We got your google code. Now select a profile to start.',
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
        $base = AUTH_API_BASE_URL . 'v' . AUTH_API_VERSION . '/';

        if (!$subdir && !$file) {
            return $base . AUTH_API_BASE_CONFIG . '?nocache';
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
            return wp_redirect($this->getGoogleAuthLink());
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
            FILTER_SANITIZE_STRING
        );
        $clientSecret = filter_input(
            INPUT_POST,
            'toplytics-private-client-secret',
            FILTER_SANITIZE_STRING
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

    /**
     * Gets the authorization config based on the user data
     * or based on the remote config for public auth.
     *
     * @since 4.0.0
     *
     * @return array The valid (at this point) auth config
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
    public function validateGoogleConfig($googleconfig)
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
        } catch (\Exception $e) {
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
                } else {
                    return [];
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

        $settings_link = $this->window->open('backend.partials.url', [
            'url' => $this->window->getSettingsLink(),
            'title' => 'Settings',
        ]);

        $widgets_link = $this->window->open('backend.partials.url', [
            'url' => admin_url('widgets.php'),
            'title' => 'Widgets',
        ]);

        array_unshift($links, $widgets_link, $settings_link);

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

        if (!is_null($plugin_file) && $plugin_file !== $this->plugin_basename) {
            return $plugin_meta;
        }

        $links = [
            $this->window->open('backend.partials.url', [
                'url' => 'https://www.presslabs.org/toplytics/docs/usage/',
                'title' => 'Usage Documentation',
                'target' => 'blank',
                'icon' => 'media-document',
            ]),
            $this->window->open('backend.partials.url', [
                'url' => 'https://wordpress.org/support/plugin/toplytics/reviews/',
                'title' => 'Review us!',
                'target' => 'blank',
                'icon' => 'format-status',
            ]),
            $this->window->open('backend.partials.url', [
                'url' => 'email:support@presslabs.com',
                'title' => 'Need help? Support is here!',
                'icon' => 'businessman',
            ]),
        ];

        $plugin_meta = array_merge($plugin_meta, $links);

        return $plugin_meta;
    }
}
