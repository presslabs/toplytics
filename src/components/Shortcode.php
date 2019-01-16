<?php

namespace Toplytics;

class Shortcode
{
    private $frontend;
    private $settings;

    public function __construct(\Toplytics\Frontend $frontend, $settings)
    {
        $this->frontend = $frontend;
        $this->settings = $settings;
    }

    public function shortcodeInit()
    {
        add_shortcode('toplytics', array( $this, 'doShortcode' ));
    }
    
    public function doShortcode($atts)
    {
        $atts = shortcode_atts(array(
            'period'      => 'today',
            'numberposts' => '15',
            'showviews'   => false,
        ), $atts);
        return $this->showTheTop($atts);
    }

    private function validateArgs($args)
    {
        if (isset($args['showviews'])) { // showviews (true/false - default=false)
            $args['showviews'] = true;
        } else {
            $args['showviews'] = false;
        }
        if (! isset($args['period'])) { // set default value
            $args['period'] = 'month';
        }
        if (! isset($args['numberposts'])) { // set default value
            $args['numberposts'] = TOPLYTICS_DEFAULT_POSTS;
        }
        if (0 > $args['numberposts']) {
            $args['numberposts'] = TOPLYTICS_DEFAULT_POSTS;
        }
        if (TOPLYTICS_MIN_POSTS > $args['numberposts']) {
            $args['numberposts'] = TOPLYTICS_MIN_POSTS;
        }
        if (TOPLYTICS_MAX_POSTS < $args['numberposts']) {
            $args['numberposts'] = TOPLYTICS_MAX_POSTS;
        }
        return ( array ) $args;
    }

    private function showTheTop($args)
    {
        $args    = $this->validateArgs($args);
        $results = $this->frontend->getResult($args['period']);
        if (! $results) {
            return '';
        }

        $counter = 0;
        $out = '<ol>';
        foreach ($results as $post_id => $post_views) {
            $counter++;
            $out .= '<li><a href="' . get_permalink($post_id)
                . '" title="' . esc_attr(get_the_title($post_id)) . '">'
                . get_the_title($post_id) . '</a>';

            if ($args['showviews']) {
                $out .= '<span class="post-views">&nbsp;'
                    . sprintf(__('%d Views', 'toplytics'), $post_views)
                    . '</span>';
            }
            $out .= '</li>';
            if ($args['numberposts'] == $counter) {
                break;
            }
        }
        $out .= '</ol>';

        return apply_filters('toplytics_shortcode_filter', $out, $args);
    }
}
