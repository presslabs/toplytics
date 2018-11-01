<?php

namespace Toplytics;

/**
 * This is the main Widget class used to setup up the widget,
 * it's settings and load it's view.
 *
 * @link       https://www.presslabs.com/
 * @since      4.0.0
 *
 * @package    Toplytics
 * @subpackage Toplytics/components
 * @author     Presslabs <support@presslabs.com>
 */

class Widget extends \WP_Widget
{
    /**
     * The frontend instance required for output.
     */
    private $frontend;

    private $settings;

    /**
     * Main constructor method used to initialize all the basic options and
     * requirmenets for the widget.
     *
     * @since 3.0.0
     * @param Frontend $frontend The frontend instance of Toplytics
     *
     * @return void
     */
    public function __construct(\Toplytics\Frontend $frontend, $settings)
    {
        $options = array(
            'classname' => 'toplytics_widget',
            'description' => __('The most popular posts on your site from Google Analytics', 'toplytics'),
        );
        parent::__construct('toplytics-widget', 'Toplytics', $options);
        $this->alt_option_name = 'toplytics_widget';

        $this->frontend = $frontend;
        $this->settings = $settings;
    }

    /**
     * We output the real-time script arguments so our JS which we
     * already included can use these arguments to update the widget
     * live on the page.
     *
     * @since 3.0.0
     * @param array $args The arguments to be put in the page
     *
     * @return void
     */
    private function realtimeScriptArgs($args)
    {
        $args = apply_filters('toplytics_widget_args', $args);
        $toplytics_args = '';
        foreach ($args as $key => $data) {
            if (is_string($data)) {
                $data = trim($data);
                $toplytics_args .= "$key : '$data',";
            } elseif (is_integer($data) || is_bool($data) || is_float($data)) {
                $toplytics_args .= "$key : $data,";
            }
        }

        echo "<script type=\"text/javascript\">toplytics_args = {{$toplytics_args}};</script>";
    }

    /**
     * Echoes the widget content.
     *
     * @since 3.0.0
     *
     * @param array $args     Display arguments including 'before_title', 'after_title',
     *                        'before_widget', and 'after_widget'.
     * @param array $instance The settings for the particular instance of the widget.
     */
    public function widget($args, $instance)
    {
        ob_start();

        extract($args);
        extract($instance);

        $title = apply_filters(
            'widget_title',
            $title,
            $instance,
            $this->id_base
        );

        $stats_periods = array_keys($this->frontend->ranges);
        if (!in_array($period, $stats_periods)) {
            $period = $stats_periods[0];
        }
        $posts = $this->frontend->getResult($period);
        $posts = array_slice($posts, 0, $numberposts, true);

        // variables for backward compatibilty
        $widget_period = $period;
        $widget_numberposts = $numberposts;
        $widget_showviews = $showviews;
        $widget_loadViaJS = $loadViaJS;

        echo $before_widget;

        if ($title) {
            echo $before_title . $title . $after_title;
        }

        if ($loadViaJS && ($this->frontend->checkSetting('enable_json') || $this->frontend->checkSetting('enable_rest_endpoint'))) {
            $toplytics_args = array(
                'widget_id' => $widget_id . '-inner',
                'period' => $period,
                'numberposts' => $numberposts,
                'showviews' => $showviews,
                'loadViaJS' => $loadViaJS,
                'before_title' => $before_title,
                'title' => $title,
                'after_title' => $after_title,
                'json_url' => $this->frontend->checkSetting('enable_rest_endpoint') ? esc_url(get_rest_url(null, '/toplytics/results')) : (($this->frontend->checkSetting('enable_json') && $this->checkSetting('json_path')) ? esc_url(home_url('/' . $this->settings['json_path'])) : ''),
            );

            $this->realtimeScriptArgs($toplytics_args);
        }

        $template_file = $this->frontend->getCustomTemplateFile();

        if ($template_file && pathinfo($template_file, PATHINFO_EXTENSION) == 'php') {
            // Template inside the theme using default PHP
            include $template_file;
        } elseif (!empty($posts)) {
            // Blade templates inside plugin views folder
            $template = $this->frontend->window->validateView($template_file) ? $template_file : 'frontend.widget';

            $this->frontend->window->open($template, compact('posts', 'showviews', 'loadViaJS', 'widget_id'), true, false);
        }

        echo $after_widget;

        ob_get_flush();
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);

        if (!$widget_numberposts = (int) $new_instance['numberposts']) {
            $widget_numberposts = TOPLYTICS_DEFAULT_POSTS;
        } elseif ($widget_numberposts < TOPLYTICS_MIN_POSTS) {
            $widget_numberposts = TOPLYTICS_MIN_POSTS;
        } elseif ($widget_numberposts > TOPLYTICS_MAX_POSTS) {
            $widget_numberposts = TOPLYTICS_MAX_POSTS;
        }

        $instance['numberposts'] = $widget_numberposts;

        $instance['period'] = $new_instance['period'];
        $stats_periods = array_keys($this->frontend->ranges);
        if (!in_array($instance['period'], $stats_periods)) {
            $instance['period'] = $stats_periods[0];
        }

        $instance['showviews'] = isset($new_instance['showviews']) ? 1 : 0;
        $instance['loadViaJS'] = isset($new_instance['loadViaJS']) ? 1 : 0;
        
        return $instance;
    }

    public function form($instance)
    {
        $widget_title = isset($instance['title']) ? esc_attr($instance['title']) : '';

        if (!isset($instance['numberposts']) || !$widget_numberposts = (int) $instance['numberposts']) {
            $widget_numberposts = TOPLYTICS_DEFAULT_POSTS;
        }

        $stats_periods = array_keys($this->frontend->ranges);
        $period = isset($instance['period']) ? $instance['period'] : $stats_periods[0];

        $showviews_checked = '';
        if (isset($instance['showviews'])) {
            $showviews_checked = $instance['showviews'] ? ' checked="checked"' : '';
        }
        $loadViaJS_checked = '';
        if (isset($instance['loadViaJS'])) {
            $loadViaJS_checked = $instance['loadViaJS'] ? ' checked="checked"' : '';
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title');?>:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $widget_title; ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('numberposts'); ?>"><?php _e('Number of posts to show', 'toplytics');?>:</label>
            <input id="<?php echo $this->get_field_id('numberposts'); ?>" name="<?php echo $this->get_field_name('numberposts'); ?>" type="text" value="<?php echo $widget_numberposts; ?>" size="3" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('period'); ?>"><?php _e('Statistics period', 'toplytics');?>:</label>
            <select id="<?php echo $this->get_field_id('period'); ?>" name="<?php echo $this->get_field_name('period'); ?>">
        <?php
        foreach (array_keys($this->frontend->ranges) as $key) {
            ?>
            <option value="<?php echo $key; ?>"<?php
            if ($period == $key) {
                echo ' selected="selected"';
            }
            echo '>' . __($key, 'toplytics'); ?>
            </option>
        <?php }?>
            </select>
        </p>

        <p>
            <input class="checkbox" type="checkbox"<?php echo $showviews_checked; ?> id="<?php echo $this->get_field_id('showviews'); ?>" name="<?php echo $this->get_field_name('showviews'); ?>" /> <label for="<?php echo $this->get_field_id('showviews'); ?>"><?php echo __('Display post views', 'toplytics'); ?>?</label>
        </p>
        
        <?php
        if ($this->frontend->checkSetting('enable_json') || $this->frontend->checkSetting('enable_rest_endpoint')) {
            ?>
        <p>
            <input class="checkbox" type="checkbox"<?php echo $loadViaJS_checked; ?> id="<?php echo $this->get_field_id('loadViaJS'); ?>" name="<?php echo $this->get_field_name('loadViaJS'); ?>" /> <label for="<?php echo $this->get_field_id('loadViaJS'); ?>"><?php echo __('Load via Javascript AJAX', 'toplytics'); ?>?</label>
        </p>
            <?php
        }
        ?>

        <p><?php _e('Template');?>:<br /><?php echo $this->frontend->getCustomTemplateFile() ?: '<strong>Default.</strong>&nbsp;See docs for more info on how to change this.'; ?></p>
        <?php
    }
}
