<?php
/**
 * Removes the plugin's settings and cached banner configuration when the plugin is deleted.
 * Consent records live on your Privacy Kit server and are not touched.
 *
 * @package EpiFive_Website_Privacy_Kit
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'epifive_wpk_settings' );

global $wpdb;
// Cached banner configuration (transients named epifive_wpk_config_<hash>).
$epifive_wpk_like = $wpdb->esc_like( '_transient_epifive_wpk_config_' ) . '%';
$epifive_wpk_tlike = $wpdb->esc_like( '_transient_timeout_epifive_wpk_config_' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $epifive_wpk_like, $epifive_wpk_tlike ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
