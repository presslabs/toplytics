<?php

namespace Toplytics;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link       https://www.presslabs.com/
 * @since      4.0.0
 *
 * @package    Toplytics
 * @subpackage Toplytics/components
 * @author     Presslabs <support@presslabs.com>
 */

class Engine
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    4.0.0
     * @access   protected
     * @var      Toplytics/Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    4.0.0
     * @access   protected
     * @var      string    $plugin_basename    The string used to uniquely identify this plugin.
     */
    protected $plugin_basename;

    /**
     * The current version of the plugin.
     *
     * @since    4.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    protected $window;

    protected $settings;

    public $frontend;
    public $backend;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    4.0.0
     */
    public function __construct()
    {
        if (defined('TOPLYTICS_VERSION')) {
            $this->version = TOPLYTICS_VERSION;
        } else {
            $this->version = '4.0.0';
        }
        $this->plugin_basename = TOPLYTICS_DOMAIN . '/' . TOPLYTICS_ENTRY;

        /**
         * The class that is responsible for orchestrating the actions and
         * filters of the plugin.
         */
        $this->loader = new \Toplytics\Loader();
        $this->window = new \Toplytics\Window();

        $this->settings = get_option('toplytics_settings', null);

        $this->setLocale();
        $this->defineAdminHooks();
        $this->definePublicHooks();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Internationalization class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    4.0.0
     * @access   private
     */
    private function setLocale()
    {

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        $pluginInternationalization = new Internationalization();

        $this->loader->addAction('init', $pluginInternationalization, 'loadPluginTextdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    4.0.0
     * @access   private
     */
    private function defineAdminHooks()
    {

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        $plugin_admin = new \Toplytics\Backend($this->getPluginBasename(), $this->getVersion(), $this->window, $this->settings);

        $this->loader->addAction('admin_enqueue_scripts', $plugin_admin, 'enqueueStyles');
        $this->loader->addAction('admin_enqueue_scripts', $plugin_admin, 'enqueueScripts');

        $this->loader->addAction('admin_init', $plugin_admin, 'publicAuthorization');
        $this->loader->addAction('admin_init', $plugin_admin, 'privateAuthorization');
        $this->loader->addAction('admin_init', $plugin_admin, 'checkAuthorization');
        $this->loader->addAction('admin_init', $plugin_admin, 'serviceDisconnect');
        $this->loader->addAction('admin_init', $plugin_admin, 'profileSelect');
        $this->loader->addAction('admin_init', $plugin_admin, 'switchProfile');
        $this->loader->addAction('admin_init', $plugin_admin, 'initSettings');
        $this->loader->addAction('admin_init', $plugin_admin, 'forceUpdate');
        $this->loader->addAction('admin_init', $plugin_admin, 'cleanDirtyAuth');

        $this->loader->addAction('admin_menu', $plugin_admin, 'registerPluginSettingsPage');

        $this->loader->addFilter('plugin_action_links_' . $this->plugin_basename, $plugin_admin, 'pluginActionLinks');
        $this->loader->addAction('plugin_row_meta', $plugin_admin, 'extraRowMeta', 10, 2);

        $this->loader->addAction('in_plugin_update_message-toplytics/toplytics.php', $plugin_admin, 'pluginUpgradeNotice', 10, 2);
        $this->loader->addAction('upgrader_process_complete', $plugin_admin, 'pluginUpgradeComplete', 10, 2);

        $this->backend = $plugin_admin;
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    4.0.0
     * @access   private
     */
    private function definePublicHooks()
    {

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        $plugin_public = new \Toplytics\Frontend($this->getPluginBasename(), $this->getVersion(), $this->window, $this->settings);
        $shortcodes = new \Toplytics\Shortcode($plugin_public, $this->settings);

        // TODO: We should completely remove these once we make sure everythig is working ok
        $this->loader->addAction('wp_loaded', $plugin_public, 'addEndpoint');
        $this->loader->addAction('template_redirect', $plugin_public, 'handleEndpoint');

        $this->loader->addAction('init', $shortcodes, 'shortcodeInit');
        $this->loader->addAction('rest_api_init', $plugin_public, 'restApiInit');
        $this->loader->addAction('widgets_init', $plugin_public, 'registerWidget');
        $this->loader->addAction('wp_enqueue_scripts', $plugin_public, 'enqueueStyles');
        $this->loader->addAction('wp_enqueue_scripts', $plugin_public, 'enqueueScripts');

        $this->frontend = $plugin_public;
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    4.0.0
     */
    public function start()
    {
        do_action('toplytics_start_initialization');
        $this->loader->init();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     4.0.0
     * @return    string    The name of the plugin.
     */
    public function getPluginBasename()
    {
        return $this->plugin_basename;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     4.0.0
     * @return    \Toplytics\Loader    Orchestrates the hooks of the plugin.
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     4.0.0
     * @return    string    The version number of the plugin.
     */
    public function getVersion()
    {
        return $this->version;
    }
}
