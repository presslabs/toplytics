<?php
/**
 * Backward compatibility code
 */

define( 'TOPLYTICS_TEXTDOMAIN', 'toplytics' );

function toplytics_get_results( $args = array() ) {
	$new_args = array_merge( array( 'period' => 'week', 'numberposts' => 5 ), $args );

	global $toplytics;
	$data = $toplytics->get_result( $new_args['period'] );
	if ( empty( $data ) ) {
		return false;
	}
	$counter = 1;
	$new_data = array();
	foreach ( $data as $post_id => $post_views ) {
		if ( $counter > $new_args['numberposts'] ) { break; }
		$new_data[ $post_id ] = $post_views;
		$counter++;
	}
	return $new_data;
}
