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
            'category'    => 0,
            'period'      => 'today',
            'numberposts' => '15',
            'showviews'   => false,
        ), $atts);
        return $this->showTheTop($atts);
    }

    private function validateArgs($args)
    {
        if ( ! empty( $args['showviews'] ) ) { // showviews (true/false - default=false)
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
        $args = $this->validateArgs($args);
        // Parse category argument as integer, if present in the shortcode.
        $category = ( ! empty( $args['category'] ) ) ? intval( $args['category'] ) : 0;
        
        $results = $this->frontend->getResult($args['period']);
        if (! $results) {
            return '';
        }

        $counter = 0;
        $out = '<ol>';
        foreach ( $results as $post_id => $post_data ) {
            // Don't add post to list if must render category posts and post not in category.
            if ( $category && ! in_array( $category, $post_data['categories'] ) ) {
                continue;
            }
            $counter++;
            $out .= '<li><a href="' . $post_data['permalink']
                . '" title="' . esc_attr( $post_data['title'] ) . '">'
                . $post_data['title'] . '</a>';

            if ($args['showviews']) {
                $out .= '<span class="post-views">&nbsp;'
                    . sprintf( __( '%d Views', 'toplytics' ), $post_data['pageviews'] )
                    . '</span>';
            }
            $out .= '</li>';
            if ($args['numberposts'] == $counter) {
                break;
            }
        }
        $out .= '</ol>';

        // If no posts to render in the shortcode (e.g. no post fits the shortcode category), return no HTML.
        if ( $counter == 0 ) {
            $out = '';
        }

        return apply_filters('toplytics_shortcode_filter', $out, $args);
    }
}
