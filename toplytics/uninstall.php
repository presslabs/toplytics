<?php

// if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// delete all toplytics options from DB
global $wpdb;
$toplytics_options = $wpdb->get_results( "SELECT option_name FROM {$wpdb->prefix}options WHERE option_name LIKE 'toplytics%'" );
foreach ( $toplytics_options as $option ) :
	delete_option( $option->option_name );
endforeach;

// remove the cron event
wp_clear_scheduled_hook( 'toplytics_cron_event' );

// flush rewrite rules in order to remove the rewrite rule
global $wp_rewrite;
$wp_rewrite->flush_rules();
