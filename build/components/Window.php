<?php

namespace Toplytics;

use Jenssegers\Blade\Blade;

/**
 * This class is the window we use to display the end output.
 * From here we generate the HTML structure of all pages that
 * are going to be shown to the end user.
 *
 * @link       https://www.presslabs.com/
 * @since      4.0.0
 *
 * @package    Toplytics
 * @subpackage Toplytics/components
 * @author     Presslabs <support@presslabs.com>
 */

class Window
{

    protected $blade;

    private $tabbed = false;
    private $tabs = [];
    private $frame = 'layout';
    private $activeTab = '';
    private $viewsFolder;
    private $cacheFolder;

    public function __construct($viewsFolder = '', $cacheFolder = '')
    {

        $this->viewsFolder = $viewsFolder ?: plugin_dir_path(__FILE__) . '../resources/views';
        $this->cacheFolder = $cacheFolder ?: plugin_dir_path(__FILE__) . '../storage/cache';

        /**
        * We make sure our cache folder exists for Blade to work correctly.
        * There is no need to check for the views folder as it is required.
        */
        if (!file_exists($this->cacheFolder)) {
            mkdir($this->cacheFolder, 0777, true);
        }

        /**
         * Blade package is responsible for parsing our views.
         */
        $this->blade = new Blade($this->viewsFolder, $this->cacheFolder);
    }

    /**
     * Simple getter to get the current views folder location
     *
     * @since 4.0.0
     *
     * @return string The views folder location on disk
     */
    public function getViewsFolder()
    {
        return $this->viewsFolder;
    }

    /**
     * This function is a wrapper over the blade make function. It
     * will load your view file and return it's contents.
     *
     * @param string $view The view file to load.
     * @param array $content An array containg the content variables to
     * be used inside the view files.
     *
     * @return string The view file content, parsed and cached.
     */
    public function open($view, $content, $echo = false, $checkQueryMessage = true)
    {

        if ($content && !is_array($content)) {
            return false;
        }

        //Checks for any query message and display it if it's present
        if ($checkQueryMessage) {
            $this->displayQueryMessage();
        }

        // This functionality is not yet in use and might
        // be dropped in a future version.
        $content['frame'] = $this->frame;
        $content['tabs'] = $this->tabs;
        $content['activeTab'] = $this->activeTab;

        $output = $this->blade->make($view, $content);

        if ($echo) {
            echo $output;
        }

        return $output;
    }

    /**
     * Simple wrapper to check if a view exists or not.
     *
     * @since 4.0.0
     * @param string $view The view path using the dot (.) syntax
     *
     * @return bool
     */
    public function validateView($view)
    {
        return $this->blade->exists($view);
    }

    /**
     * This will display any message to the user that usually is
     * being added at the redirect stage.
     *
     * @since 4.0.0
     *
     * @return bool|
     */
    protected function displayQueryMessage()
    {

        $message = filter_input(INPUT_GET, 'message', FILTER_SANITIZE_STRING) ?: false;

        if (! $message) {
            return false;
        }

        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?: 'info';
        $message = get_transient('message_' . $message);
        delete_transient('message_' . $message);

        if ($message) {
            return $this->notifyAdmins($status, $message, true, '', true);
        }

        return false;
    }

    /**
     * We need to know which frame should our Window use.
     * Consider this the layout we are going to have.
     */
    public function setFrame($frame = 'layout')
    {
        $this->frame = $frame;
        return $this;
    }

    /**
     * We are going to used this variable to determine if we need to load a tabbed layout
     * or a normal one. This should be set once and can be chained.
     */
    public function setTabs($tabs = [])
    {
        // $tabs = array( 'homepage' => 'Home Settings', 'general' => 'General', 'footer' => 'Footer' );
        if ($tabs) {
            foreach ($tabs as $tab => $name) {
                $class = ( $tab == $current ) ? ' nav-tab-active' : '';
                $tabs .= "<a class='nav-tab$class' href='?page=theme-settings&tab=$tab'>$name</a>";
            }
        }
        
        return $this;
    }

    public function setActiveTab($tab = '')
    {
        $this->activeTab = $tab;
        return $this;
    }

    /**
     * This function is used to display site-wide notifications to the user.
     *
     * @since 4.0.0
     *
     * @param string $type The type of the message (error | warning | success | info )
     * @param string $message The message to be output
     * @param bool $dismiss Is the message dismissable? ( frontend only )
     * @param string $style Any other styling options you want for the box
     * @param bool $lateLoad Has this been loaded after the plugin initialization?
     *
     * @return bool If the message has been displayed or not.
     */
    public function notifyAdmins($type, $message, $dismiss = true, $style = '', $lateLoad = false)
    {
        if (!$type || !$message) {
            return false;
        }

        /**
         * We should only display notifications to admin users
         * and make sure we already started initialization
         */
        if (!function_exists('wp_get_current_user') ||
            !current_user_can('activate_plugins') ||
            !is_admin()) {
            return false;
        }

        $notification = $this->open(
            'backend.partials.notification',
            compact('type', 'message', 'dismiss', 'style'),
            false,
            false
        );

        if ($lateLoad) {
            echo $notification;
            return true;
        }

        /**
         * We add the action directly since we know that the initialization
         * process has already started and this function will
         * be called from inside other hooks.
         */
        add_action('admin_notices', function () use ($notification) {
            echo $notification;
        }, -1);

        return true;
    }

    /**
     * Safely redirect using wordpress and also passing in a message as well as other args.
     *
     * Defaults to the message redirect to the plugin settings page.
     * If you need to redirect to other URL go ahead and use an URL in the first param.
     * And anyway you can add args at the end by ignoring the status ofc.
     */
    public function redirect($message = '', $status = 'info', $args = [])
    {
        if (!$message) {
            wp_safe_redirect(admin_url());
            die();
        }
        
        if (filter_var($message, FILTER_VALIDATE_URL)) {
            wp_safe_redirect(add_query_arg($args, $message));
            die();
        }
        
        $message_id = substr(
            md5(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') . time()),
            0,
            8
        );
    
        set_transient('message_' . $message_id, $message, 900);
        
        $url = add_query_arg(
            array_merge([
                'message' => $message_id,
                'status' => $status,
            ], $args),
            $this->getSettingsLink()
        );
        
        wp_safe_redirect($url);
        die();
    }

    /**
     * This is just a wrapper / helper function
     * for the main redirect with message function.
     */
    public function successRedirect($message = '')
    {
        
        $this->redirect($message, 'success');
    }

    /**
     * This is just a wrapper / helper function
     * for the main redirect with message function.
     */
    public function errorRedirect($message = '')
    {
        
        $this->redirect($message, 'error');
    }

    public function getSettingsLink()
    {

        return admin_url(TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN);
    }
}
