<?php
/**
 * Plugin Name:       EpiFive Website Privacy Kit
 * Plugin URI:        https://github.com/epifive/website-privacy-kit-wp
 * Description:       Connects WordPress to your EpiFive Website Privacy Kit server: DPDP consent banner, script gating, Google Consent Mode defaults, privacy hub links and consent checkboxes for forms (Contact Form 7, WPForms). Not legal advice.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            EpiFive
 * Author URI:        https://www.epifive.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       epifive-website-privacy-kit
 * Domain Path:       /languages
 *
 * @package EpiFive_Website_Privacy_Kit
 */

defined( 'ABSPATH' ) || exit;

define( 'EPIFIVE_WPK_VERSION', '1.0.0' );
define( 'EPIFIVE_WPK_FILE', __FILE__ );
define( 'EPIFIVE_WPK_DIR', plugin_dir_path( __FILE__ ) );
define( 'EPIFIVE_WPK_URL', plugin_dir_url( __FILE__ ) );

require_once EPIFIVE_WPK_DIR . 'includes/class-epifive-wpk-options.php';
require_once EPIFIVE_WPK_DIR . 'includes/class-epifive-wpk-settings.php';
require_once EPIFIVE_WPK_DIR . 'includes/class-epifive-wpk-frontend.php';
require_once EPIFIVE_WPK_DIR . 'includes/class-epifive-wpk-integrations.php';

/**
 * Start the plugin.
 */
function epifive_wpk_init() {
	EpiFive_WPK_Settings::init();
	EpiFive_WPK_Frontend::init();
	EpiFive_WPK_Integrations::init();
}
add_action( 'plugins_loaded', 'epifive_wpk_init' );

/**
 * Settings link on the Plugins screen.
 *
 * @param string[] $links Existing links.
 * @return string[]
 */
function epifive_wpk_action_links( $links ) {
	$url = admin_url( 'options-general.php?page=epifive-website-privacy-kit' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'epifive-website-privacy-kit' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'epifive_wpk_action_links' );
