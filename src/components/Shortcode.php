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
            'showimage'   => false,
        ), $atts);
        return $this->showTheTop($atts);
    }

    private function validateArgs($args)
    {
        // filter_var(..., FILTER_VALIDATE_BOOLEAN) correctly treats the string "false" (and "0", "no", "off", "")
        // as false, unlike empty(), which only "false" as a non-empty string would otherwise fail to catch.
        $args['showviews'] = filter_var( $args['showviews'], FILTER_VALIDATE_BOOLEAN );
        $args['showimage'] = filter_var( $args['showimage'], FILTER_VALIDATE_BOOLEAN );
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
        $out = '<ul style="list-style:none;margin:0;padding:0;">';
        foreach ( $results as $post_id => $post_data ) {
            // Don't add post to list if must render category posts and post not in category.
            if ( $category && ! in_array( $category, $post_data['categories'] ) ) {
                continue;
            }
            $counter++;
            $out .= '<li style="margin-bottom:10px;"><a href="' . $post_data['permalink']
                . '" title="' . esc_attr( $post_data['title'] ) . '" style="display:flex;align-items:center;gap:10px;text-decoration:none;">';
            if ( $args['showimage'] && ! empty( $post_data['featured_image'] ) ) {
                $out .= '<img class="toplytics-featured-image" src="' . esc_url( $post_data['featured_image'] )
                    . '" alt="' . esc_attr( $post_data['title'] ) . '">';
            }
            $out .= $post_data['title'];

            if ($args['showviews']) {
                $out .= '<span class="post-views">&nbsp;'
                    . sprintf( __( '%d Views', 'toplytics' ), $post_data['pageviews'] )
                    . '</span>';
            }
            $out .= '</a></li>';
            if ($args['numberposts'] == $counter) {
                break;
            }
        }
        $out .= '</ul>';

        // If no posts to render in the shortcode (e.g. no post fits the shortcode category), return no HTML.
        if ( $counter == 0 ) {
            $out = '';
        }

        return apply_filters('toplytics_shortcode_filter', $out, $args);
    }
}
