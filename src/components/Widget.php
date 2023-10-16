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

        $title = '';
        $period = 'week';
        $numberposts = 20;
        $showviews = 0;
        $loadViaJS = 0;
        $category = 0;
        $fallback_not_enough_ga_posts = 'recent';
    
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
        // If posts need to be filtered by category, pick just the needed ones.
        $using_fallback_posts = false;
        if ( $category ) {
            $cat_posts = array();
            foreach ( $posts as $key => $post ) {
                if ( in_array( $category, $post['categories'] ) !== false ) {
                    $cat_posts[ $key ] = $post;
                }
            }
            // Now check if there are enough posts to render.
            if ( count( $cat_posts ) < $numberposts ) {
                $posts = array();
                // Set flag indicating fallback posts need to be rendered.
                $using_fallback_posts = true;
                switch ( $fallback_not_enough_ga_posts ) {
                    case 'recent' :
                        // Fetch the category posts from the DB.
                        $cat_posts = $this->frontend->getResult( 'categories' );
                        if ( isset( $cat_posts[ $category ] ) ) {
                            $posts = $cat_posts[ $category ];
                        }
                        break;
                    case 'top' :
                        // Fetch the most popular posts instead.
                        $posts = $this->frontend->getResult( 'top_posts' );
                        break;
                }
            } else {
                // A high-enough number of category posts have been retrieved from GA, for this period. Render them.
                $posts = $cat_posts;
            }
        }

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

            // make sure id is defined
            $widget_id = isset($widget_id) ? $widget_id : 'widget-' . uniqid();

            $toplytics_args = array(
                'widget_id' => $widget_id . '-inner',
                'period' => $period,
                'numberposts' => $numberposts,
                'category' => $category,
                'fallback_not_enough_ga_posts' => $fallback_not_enough_ga_posts,
                'showviews' => $showviews,
                'loadViaJS' => $loadViaJS,
                'before_title' => $before_title,
                'title' => $title,
                'after_title' => $after_title,
                'json_url' => $this->frontend->checkSetting('enable_rest_endpoint') ? esc_url(get_rest_url(null, '/toplytics/results')) : (($this->frontend->checkSetting('enable_json') && $this->frontend->checkSetting('json_path')) ? esc_url(home_url('/' . $this->settings['json_path'])) : ''),
            );

            $this->realtimeScriptArgs($toplytics_args);
        }

        $template_file = $this->frontend->getCustomTemplateFile();

        if ($template_file && pathinfo($template_file, PATHINFO_EXTENSION) == 'php') {
            // Initialize $toplytics_results, for backward compatibility.
            $toplytics_results = $posts;
            // Template inside the theme using default PHP.
            include $template_file;
        } elseif ( ! empty( $posts ) ) {
            // Load the default widget template.
            include $this->frontend->window->getViewsFolder() . '/frontend/widget.template.php';
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

        $instance['category'] = isset($new_instance['category']) ? intval( $new_instance['category'] ) : 0;
        $instance['fallback_not_enough_ga_posts'] = ( isset( $new_instance['fallback_not_enough_ga_posts'] ) ) ? $new_instance['fallback_not_enough_ga_posts'] : 'none';

        $instance['showviews'] = isset($new_instance['showviews']) ? 1 : 0;
        $instance['loadViaJS'] = isset($new_instance['loadViaJS']) ? 1 : 0;

        // If the category is set, add hook for refreshing the Toplytics posts for the categories.
        if (isset($instance['category']) && isset($old_instance['category']) && $instance['category'] != $old_instance['category']) {
            add_action('update_option_widget_toplytics-widget', array($this, 'update_additional_posts_data'), 10, 3);
        }
        
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

        $category = isset($instance['category']) ? intval( $instance['category'] ) : 0;

        $fallback_not_enough_ga_posts = ( isset( $instance['fallback_not_enough_ga_posts'] ) ) ? $instance['fallback_not_enough_ga_posts'] : 'none';

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
            <label for="<?php echo $this->get_field_id('category'); ?>"><?php echo __('Show posts from category', 'toplytics'); ?>:</label>
            <?php wp_dropdown_categories( array(
                'show_option_all' => 'Any category',
                'id'       => $this->get_field_id( 'category' ),
                'name'     => $this->get_field_name( 'category' ),
                'orderby'  => 'name',
                'selected' => $category,
            )); ?>
            <br />
            <?php echo __('Display other posts as fallback, in case not enough posts get returned by Google Analytics, for the selected category', 'toplytics'); ?>?
            <br />
            <label for="<?php echo $this->get_field_id('fallback_not_enough_ga_posts'); ?>-none"><input class="none" type="radio" id="<?php echo $this->get_field_id('fallback_not_enough_ga_posts'); ?>-none" name="<?php echo $this->get_field_name('fallback_not_enough_ga_posts'); ?>" value="none" <?php checked( $fallback_not_enough_ga_posts, 'none' ); ?> /><?php echo __('Don\'t display the widget', 'toplytics'); ?></label>
            <br />
            <label for="<?php echo $this->get_field_id('fallback_not_enough_ga_posts'); ?>-recent"><input class="radio" type="radio" id="<?php echo $this->get_field_id('fallback_not_enough_ga_posts'); ?>-recent" name="<?php echo $this->get_field_name('fallback_not_enough_ga_posts'); ?>" value="recent" <?php checked( $fallback_not_enough_ga_posts, 'recent' ); ?> /><?php echo __('Most recent posts from the category', 'toplytics'); ?></label>
            <br />
            <label for="<?php echo $this->get_field_id('fallback_not_enough_ga_posts'); ?>-top"><input class="radio" type="radio" id="<?php echo $this->get_field_id('fallback_not_enough_ga_posts'); ?>-top" name="<?php echo $this->get_field_name('fallback_not_enough_ga_posts'); ?>" value="top" <?php checked( $fallback_not_enough_ga_posts, 'top' ); ?> /><?php echo __('Top posts from all categories', 'toplytics'); ?></label>
            <br />
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



    /**
     * Hook to post option update action hook, in order to trigger
     * an update of the additional, fallback, posts data.
     *
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     * @param string $option    Option name.
     */
    public function update_additional_posts_data( $old_value, $value, $option ) {
        global $toplytics_engine;
        $toplytics_engine->backend->update_additional_posts_data();
    }
}
