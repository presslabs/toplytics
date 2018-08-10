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

    /**
     * Main constructor method used to initialize all the basic options and
     * requirmenets for the widget.
     *
     * @since 3.0.0
     * @param Frontend $frontend The frontend instance of Toplytics
     *
     * @return void
     */
    public function __construct(\Toplytics\Frontend $frontend)
    {
        $options = array(
            'classname' => 'toplytics_widget',
            'description' => __('The most popular posts on your site from Google Analytics', 'toplytics'),
        );
        parent::__construct('toplytics-widget', 'Toplytics', $options);
        $this->alt_option_name = 'toplytics_widget';

        $this->frontend = $frontend;
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
        $toplytics_results = $this->frontend->getResult($period);
        $toplytics_results = array_slice($toplytics_results, 0, $numberposts, true);
        // echo var_export($toplytics_results, true);

        // variables for backward compatibilty
        $widget_period = $period;
        $widget_numberposts = $numberposts;
        $widget_showviews = $showviews;
        $widget_loadViaJS = $loadViaJS;

        echo $before_widget;

        if ($title) {
            echo $before_title . $title . $after_title;
        }
        $toplytics_args = array(
            'widget_id' => $widget_id . '-inner',
            'period' => $period,
            'numberposts' => $numberposts,
            'showviews' => $showviews,
            'loadViaJS' => $loadViaJS,
            'before_title' => $before_title,
            'title' => $title,
            'after_title' => $after_title,
        );
        $this->realtimeScriptArgs($toplytics_args);
        echo "<div id='$widget_id-inner'></div>";

        if ($loadViaJS) {
            // This functionality is still rudimentary and requires a serious rewrite
            echo "<script type=\"text/javascript\">jQuery(document).ready(function(){toplytics_results(toplytics_args)});</script>";
        } else {
            $template_file = $this->frontend->getCustomTemplateFile();

            if ($template_file && pathinfo($template_file, PATHINFO_EXTENSION) == 'php') {
                // Legacy support, bail fast on it. All logic inside template (bad).
                include $template_file;
            } elseif (!empty($toplytics_results)) {
                // Modern approach using Blade templates. Separated logic and template.

                $posts_list = get_posts(array(
                    'posts_per_page' => count($toplytics_results), // the number of posts being displayed
                    'post__in' => array_keys($toplytics_results),
                    'orderby' => 'post__in',
                ));

                $posts = [];

                // We get only the items we need from these posts and append
                // the view count as well, in case we need to display it.
                foreach ($posts_list as $post) {
                    $posts[] = (object) [
                        'permalink' => get_the_permalink($post),
                        'title' => get_the_title($post),
                        'views' => isset($toplytics_results[get_the_ID($post)]) ? $toplytics_results[get_the_ID($post)] : 0,
                    ];
                }

                $template = $this->frontend->window->validateView($template_file) ? $template_file : 'frontend.widget';

                $this->frontend->window->open($template, compact('posts', 'showviews'), true, false);
            }
        }

        echo $after_widget;

        ob_get_flush();
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);

        if (!$widget_numberposts = (int) $new_instance['numberposts']) {
            $widget_numberposts = DEFAULT_POSTS;
        } elseif ($widget_numberposts < MIN_POSTS) {
            $widget_numberposts = MIN_POSTS;
        } elseif ($widget_numberposts > MAX_POSTS) {
            $widget_numberposts = MAX_POSTS;
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
            $widget_numberposts = DEFAULT_POSTS;
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

        <p>
            <input class="checkbox" type="checkbox"<?php echo $loadViaJS_checked; ?> id="<?php echo $this->get_field_id('loadViaJS'); ?>" name="<?php echo $this->get_field_name('loadViaJS'); ?>" /> <label for="<?php echo $this->get_field_id('loadViaJS'); ?>"><?php echo __('Load via Javascript (ignores any custom template)', 'toplytics'); ?>?</label>
        </p>

        <p><?php _e('Template');?>:<br /><?php echo $this->frontend->getCustomTemplateFile() ?: '<strong>Default.</strong>&nbsp;See docs for more info on how to change this.'; ?></p>
        <?php
    }
}
