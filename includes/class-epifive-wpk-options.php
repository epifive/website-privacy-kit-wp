<?php
/**
 * Stored options and helpers shared by the admin screen and the front end.
 *
 * @package EpiFive_Website_Privacy_Kit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option access. All settings live in one array option: epifive_wpk_settings.
 */
class EpiFive_WPK_Options {

	const OPTION = 'epifive_wpk_settings';

	/**
	 * Default values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'server_url'   => '',
			'site_key'     => '',
			'enabled'      => 1,
			'consent_mode' => 1,
			'gate_handles' => '',
			'hub_url'      => '',
			'cf7_purposes' => '',
			'cf7_field'    => 'your-email',
			'wpf_purposes' => '',
			'wpf_field'    => '',
		);
	}

	/**
	 * All settings merged with the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting name.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Server address without a trailing slash, or ''.
	 *
	 * @return string
	 */
	public static function server() {
		return untrailingslashit( (string) self::get( 'server_url' ) );
	}

	/**
	 * True when the banner can be loaded (server and site key set, banner switched on).
	 *
	 * @return bool
	 */
	public static function ready() {
		return (bool) self::get( 'enabled' ) && '' !== self::server() && '' !== (string) self::get( 'site_key' );
	}

	/**
	 * Script handles to gate: handle => purpose key, from "handle=purpose" lines.
	 *
	 * @return array<string, string>
	 */
	public static function gated_handles() {
		$map   = array();
		$lines = preg_split( '/\r\n|\r|\n/', (string) self::get( 'gate_handles' ) );
		foreach ( (array) $lines as $line ) {
			$parts = array_map( 'trim', explode( '=', $line, 2 ) );
			if ( 2 === count( $parts ) && '' !== $parts[0] && preg_match( '/^[a-z0-9_\-]{1,60}$/', $parts[1] ) ) {
				$map[ sanitize_key( $parts[0] ) ] = $parts[1];
			}
		}
		/**
		 * Filters the script handles that wait for consent.
		 *
		 * @param array<string, string> $map Script handle => purpose key (as set in the Privacy Kit dashboard).
		 */
		return (array) apply_filters( 'epifive_wpk_gated_handles', $map );
	}

	/**
	 * Comma-separated purpose keys as a clean list.
	 *
	 * @param string $value Raw value.
	 * @return string[]
	 */
	public static function purpose_list( $value ) {
		$keys = array();
		foreach ( explode( ',', (string) $value ) as $key ) {
			$key = trim( $key );
			if ( preg_match( '/^[a-z0-9_\-]{1,60}$/', $key ) ) {
				$keys[] = $key;
			}
		}
		return array_values( array_unique( $keys ) );
	}

	/**
	 * Banner configuration from the server (cached for 12 hours), or null.
	 *
	 * Only the configured server is ever contacted.
	 *
	 * @param bool $refresh Skip the cache.
	 * @return array<string, mixed>|null
	 */
	public static function remote_config( $refresh = false ) {
		if ( '' === self::server() || '' === (string) self::get( 'site_key' ) ) {
			return null;
		}
		$cache_key = 'epifive_wpk_config_' . md5( self::server() . '|' . self::get( 'site_key' ) );
		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$url      = self::server() . '/api/v1/sites/' . rawurlencode( (string) self::get( 'site_key' ) ) . '/config';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 0,
				'headers'     => array( 'Accept' => 'application/json' ),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['links'] ) ) {
			return null;
		}
		set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Address of the privacy hub: the setting, or derived from the notice link in the banner config.
	 *
	 * @return string
	 */
	public static function hub_url() {
		$hub = (string) self::get( 'hub_url' );
		if ( '' !== $hub ) {
			return $hub;
		}
		$config = self::remote_config();
		if ( is_array( $config ) && ! empty( $config['links']['notice'] ) ) {
			return (string) preg_replace( '#/notice/?$#', '', (string) $config['links']['notice'] );
		}
		return '';
	}
}
