<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}blm_analytics" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}blm_ctas" );

delete_option( 'blm_db_version' );
delete_option( 'blm_floating_bar' );
