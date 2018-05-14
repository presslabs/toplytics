<?php

namespace Toplytics;

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
     * The window to open up Blade templates.
     */
    public $window;

    /**
     * Initialize the class and set its properties.
     *
     * @since    4.0.0
     * @param      string    $plugin_basename       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_basename, $version, Window $window)
    {

        $this->plugin_basename = $plugin_basename;
        $this->version = $version;
        $this->ranges = $this->initRanges();
        $this->window = $window;
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
            $this->plugin_basename,
            plugin_dir_url(__FILE__) . '../resources/frontend/css/toplytics-public.css',
            array(),
            $this->version,
            'all'
        );
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

        wp_enqueue_script(
            $this->plugin_basename,
            plugin_dir_url(__FILE__) . '../resources/frontend/js/toplytics-public.js',
            array( 'jquery' ),
            $this->version,
            false
        );
    }

    public function addEndpoint()
    {
        add_rewrite_rule('toplytics\.json$', 'index.php?toplytics=json', 'top');
    }

    public function handleEndpoint($template)
    {

        if (get_query_var('toplytics', false) == 'json') {
            $this->jsonData();
        }

        return $template;
    }

    /**
     * Return Toplytics data as a json file.
     */
    public function jsonData()
    {
        // header('Content-Type: application/json');
        $post_data = array();
        foreach (array_keys($this->ranges) as $when) {
            $result = $this->getResult($when);
            if (! empty($result)) {
                foreach ($result as $post_id => $pageviews) {
                    $data = array(
                        'permalink' => get_permalink($post_id),
                        'title'     => get_the_title($post_id),
                        'post_id'   => (int) $post_id,
                        'views'     => (int) $pageviews,
                    );
                    $post_data[ $when ][] = apply_filters('toplytics_json_data', $data, $post_id, $when);
                }
            }
        }
        $json_data = apply_filters('toplytics_json_all_data', $post_data);
        wp_send_json($json_data);
        // echo json_encode($json_data, JSON_FORCE_OBJECT);
        // die();
    }

    /**
     * Checks to see if any custom template file exists and returns it's location
     * on disk or otherwise return false so we can load the default one.
     *
     * @since 4.0.0
     *
     * @return bool|string The location on disk on the custom template
     */
    public function getCustomTemplateFile()
    {
        $toplytics_template = CUSTOM_TEMPLATE_DEFAULT_NAME . '.php';

        $theme_template = get_stylesheet_directory() . '/' . $toplytics_template;
        if (file_exists($theme_template)) {
            return $theme_template;
        }

        $custom_template = $this->window->getViewsFolder() . '/frontend/custom.blade.php';

        if (file_exists($custom_template)) {
            return 'frontend.custom';
        }
        
        return false;
    }
    
    /**
     * Get Toplytics result from DB
     */
    public function getResult($when = 'today')
    {
        $toplytics_result = get_option("toplytics_result_$when", array());

        return empty($toplytics_result) ? array() : $toplytics_result['result'];
    }

    public function initRanges()
    {
        $ranges = get_option('toplytics_results_ranges');

        return apply_filters('toplytics_ranges', $ranges);
    }

    public function registerWidget()
    {
        register_widget(new \Toplytics\Widget($this));
    }

    public function restApiInit()
    {
        register_rest_route('toplytics/', '/auth/', array(
        
            'methods' => 'GET',
            'callback' => 'toplyticsMasterApiEndpoint',
        ));
    }

    // function toplyticsMasterApiEndpoint($data)
    // {
        // if(!isset($data['state']) || !$data['state'] ||
        //  !isset($data['code']) || !$data['code'])
        //  return 'Gee. Go get a coffe, this is not for you!';
        // // $posts = get_posts(array(
        // //     'author' => $data['id'],
        // // ));

        // // if (empty($posts)) {
        // //     return null;
        // // }

        // // return var_export($data, true);
        // // return 'yes';
        // // return $data['state'];
        // $url = add_query_arg([
        //  'ok' => 'ok',
        //  'code' => $data['code'],
        // ], $data['state']);
        // // return $url;
        // wp_redirect($url, 303);
    // }
}
