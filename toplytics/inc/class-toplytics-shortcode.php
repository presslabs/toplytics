<?php
/*  Copyright 2014-2015 Presslabs SRL <ping@presslabs.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Toplytics_Shortcode {
	private $toplytics;

	public function __construct() {
		global $toplytics;
		$this->toplytics = $toplytics;

		add_shortcode( 'toplytics', array( $this, 'shortcode' ) );
	}

	public function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'period'      => 'today',
			'numberposts' => '15',
			'showviews'   => false,
		), $atts );
		return $this->_show_the_top( $atts );
	}

	private function _validate_args( $args ) {
		if ( isset( $args['showviews'] ) ) { // showviews (true/false - default=false)
			$args['showviews'] = true;
		} else {
			$args['showviews'] = false;
		}
		if ( ! isset( $args['period'] ) ) { // set default value
			$args['period'] = 'month';
		}
		if ( ! isset( $args['numberposts'] ) ) { // set default value
			$args['numberposts'] = Toplytics::DEFAULT_POSTS;
		}
		if ( 0 > $args['numberposts'] ) {
			$args['numberposts'] = Toplytics::DEFAULT_POSTS;
		}
		if ( Toplytics::MIN_POSTS > $args['numberposts'] ) {
			$args['numberposts'] = Toplytics::MIN_POSTS;
		}
		if ( Toplytics::MAX_POSTS < $args['numberposts'] ) {
			$args['numberposts'] = Toplytics::MAX_POSTS;
		}
		return ( array ) $args;
	}

	private function _show_the_top( $args ) {
		$args    = $this->_validate_args( $args );
		$results = $this->toplytics->get_result( $args['period'] );
		if ( ! $results ) { return ''; }

		$counter = 0;
		$out = '<ol>';
		foreach ( $results as $post_id => $post_views ) {
			$counter++;
			$out .= '<li><a href="' . get_permalink( $post_id )
				. '" title="' . esc_attr( get_the_title( $post_id ) ) . '">'
				. get_the_title( $post_id ) . '</a>';

			if ( $args['showviews'] ) {
				$out .= '<span class="post-views">&nbsp;'
					. sprintf( __( '%d Views', 'toplytics' ), $post_views )
					. '</span>';
			}
			$out .= '</li>';
			if ( $args['numberposts'] == $counter ) { break; }
		}
		$out .= '</ol>';

		return apply_filters( 'toplytics_shortcode_filter', $out, $args );
	}
}
new Toplytics_Shortcode();
