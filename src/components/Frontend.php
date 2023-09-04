<?php

namespace Toplytics;

use Toplytics\Widget;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @link       https://www.presslabs.com/
 * @since      4.0.0
 *
 * @package    Toplytics
 * @subpackage Toplytics/components
 * @author     Presslabs <support@presslabs.com>
 */

class Frontend
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
     * The ranges in which Toplytics gets data.
     */
    public $ranges;

    /**
     * The window to open up templates.
     */
    public $window;

    private $settings;

    /**
     * Initialize the class and set its properties.
     *
     * @since    4.0.0
     * @param      string    $plugin_basename       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_basename, $version, Window $window, $settings = null)
    {

        $this->plugin_basename = $plugin_basename;
        $this->version = $version;
        $this->window = $window;
        $this->settings = $settings ?: get_option('toplytics_settings', null);
        $this->ranges = $this->initRanges();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    4.0.0
     */
    public function enqueueStyles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Toplytics_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Toplytics_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style(
            TOPLYTICS_DOMAIN,
            plugin_dir_url(__FILE__) . '../resources/frontend/css/toplytics-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function checkSetting($var, $requiredValue = null)
    {
        $status = isset($this->settings[$var]) && $this->settings[$var];

        if (isset($this->settings[$var]) && !is_null($requiredValue)) {
            $status = ($this->settings[$var] === $requiredValue);
        }

        return $status;
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    4.0.0
     */
    public function enqueueScripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Toplytics_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Toplytics_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        // if ($this->checkSetting('enable_json') && $this->checkSetting('json_path')) {
        //     wp_register_script(
        //         TOPLYTICS_DOMAIN,
        //         plugin_dir_url(__FILE__) . '../resources/frontend/js/toplytics-public.js',
        //         array( 'jquery' ),
        //         $this->version,
        //         false
        //     );

        //     wp_localize_script(TOPLYTICS_DOMAIN, TOPLYTICS_DOMAIN, array( 'json_url' => esc_url(home_url('/' . $this->settings['json_path'])) ));
        //     wp_enqueue_script(TOPLYTICS_DOMAIN);
        // }
    }

    /**
     * This is where our endpoint is being declared. We'll handle it
     * in another method that will catch the 'toplytics' param
     * and return the apropiate JSON formated data.
     *
     * @since 3.0.0
     * @return void
     */
    public function addEndpoint()
    {
        if ($this->checkSetting('enable_json') && $this->checkSetting('json_path')) {
            add_rewrite_tag('%toplytics%', '([^&]+)');
            add_rewrite_rule('^' . preg_quote($this->settings['json_path']) . '$', 'index.php?toplytics=json', 'top');
        }
        
        return;
    }

    /**
     * This is the endpoint where we handle our json redirection done
     * above. We will return json formated data from the DB for the
     * frontend javascript code to handle and display it.
     *
     * @since 3.0.0
     * @return void
     */
    public function handleEndpoint()
    {
        if ($this->checkSetting('enable_json') &&
            $this->checkSetting('json_path') &&
            get_query_var('toplytics', false) == 'json') {
            wp_send_json($this->jsonData());
        }

        return;
    }

    /**
     * Return Toplytics data from the DB constants formated only
     * with required data for the frontend to display.
     *
     * @since 3.0.0
     * @return string The json data
     */
    public function jsonData()
    {
        // Fetch the lists of posts for each range.
        foreach (array_keys($this->ranges) as $when) {
            $post_data[$when] = $this->getResult($when);
        }

        // Also fetch the list of posts per categories.
        $post_data['categories'] = $this->getResult( 'categories' );
        // Also fetch the list of top posts.
        $post_data['top_posts'] = $this->getResult( 'top_posts' );

        $json_data = apply_filters('toplytics_json_all_data', $post_data);
        return $json_data;
    }

    /**
     * Checks to see if any custom template file exists and returns its location
     * on disk or otherwise return false so we can load the default one.
     *
     * @since 4.0.0
     *
     * @return bool|string The location on disk on the custom template
     */
    public function getCustomTemplateFile()
    {
        $toplytics_template = TOPLYTICS_CUSTOM_TEMPLATE_DEFAULT_NAME . '.php';

        $theme_template = get_stylesheet_directory() . '/' . $toplytics_template;
        if (file_exists($theme_template)) {
            return $theme_template;
        }

        $custom_template = $this->window->getViewsFolder() . '/frontend/custom.template.php';

        if ( file_exists( $custom_template ) ) {
            return $custom_template;
        }
        
        return false;
    }

    /**
     * Wrapper function to preserv backwards compatibility.
     */
    public function get_result($when = 'today')
    {
        return $this->getResult($when);
    }
    
    /**
     * Get Toplytics result from DB - this result is stored in an
     * option which should be re-fetched every hour via a CRON.
     */
    public function getResult($when = 'today')
    {
        $toplytics_result = get_option("toplytics_result_$when", array());

        // TODO: Make this better by maybe including all results in a single option?
        return $toplytics_result['result'][$when] ?? [];
    }

    public function initRanges()
    {
        $ranges = get_option('toplytics_results_ranges');

        if (!$ranges) {
            return [];
        }

        foreach ($ranges as $range => $timestamp) {
            if (!$this->checkSetting('fetch_' . $range)) {
                unset($ranges[$range]);
            }
        }

        return apply_filters('toplytics_ranges', $ranges);
    }

    public function registerWidget()
    {
        register_widget(new Widget($this, $this->settings));
    }

    /**
     * This adds this rest API endpoint to be used by anybody or even
     * by us in a future version of the plugin.
     *
     * Example:
     * http://localhost:8080/wp-json/toplytics/results
     *
     * @since 4.0.0
     */
    public function restApiInit()
    {
        if ($this->checkSetting('enable_rest_endpoint')) {
            register_rest_route('toplytics', '/results/', array(
        
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'toplyticsMasterApiEndpoint'],
            'permission_callback' => '__return_true',
            ));
        }
    }

    /**
     * This callback returns the data as JSON via the REST API.
     *
     * @since 4.0.0
     */
    public function toplyticsMasterApiEndpoint()
    {
        return $this->jsonData();
    }
}
