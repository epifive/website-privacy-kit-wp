<?php
/**
 * Front end: banner script, Consent Mode defaults, script gating, shortcodes and the block.
 *
 * @package EpiFive_Website_Privacy_Kit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public-facing hooks.
 */
class EpiFive_WPK_Frontend {

	const BANNER = 'epifive-wpk-banner';
	const GCM    = 'epifive-wpk-consent-mode';
	const FORMS  = 'epifive-wpk-forms';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'consent_mode' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'banner' ), 5 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'script_tag' ), 10, 3 );
		add_shortcode( 'wpk_privacy_link', array( __CLASS__, 'shortcode_link' ) );
		add_shortcode( 'wpk_privacy_choices', array( __CLASS__, 'shortcode_choices' ) );
		add_shortcode( 'wpk_form_consent', array( __CLASS__, 'shortcode_form_consent' ) );
		add_action( 'init', array( __CLASS__, 'register_block' ) );
	}

	/**
	 * Google Consent Mode v2: everything except necessary storage denied until the visitor chooses.
	 * Printed first in the head so it runs before any Google tag.
	 */
	public static function consent_mode() {
		if ( is_admin() || ! EpiFive_WPK_Options::ready() || ! EpiFive_WPK_Options::get( 'consent_mode' ) ) {
			return;
		}
		wp_register_script( self::GCM, false, array(), EPIFIVE_WPK_VERSION, false );
		wp_enqueue_script( self::GCM );
		wp_add_inline_script(
			self::GCM,
			'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("consent","default",{ad_storage:"denied",ad_user_data:"denied",ad_personalization:"denied",analytics_storage:"denied",personalization_storage:"denied",functionality_storage:"granted",security_storage:"granted",wait_for_update:500});'
		);
	}

	/**
	 * The banner script from the configured server (async, in the head).
	 */
	public static function banner() {
		if ( is_admin() || ! EpiFive_WPK_Options::ready() ) {
			return;
		}
		// The server sets its own cache headers for w.js; a version query would only defeat them.
		wp_enqueue_script( self::BANNER, EpiFive_WPK_Options::server() . '/w.js', array(), null, array( 'strategy' => 'async', 'in_footer' => false ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}

	/**
	 * Add data-site to the banner tag and turn gated scripts into type="text/plain" data-wpk-purpose="…".
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script URL.
	 * @return string
	 */
	public static function script_tag( $tag, $handle, $src ) {
		unset( $src );
		if ( self::BANNER === $handle ) {
			return str_replace( '<script ', '<script data-site="' . esc_attr( (string) EpiFive_WPK_Options::get( 'site_key' ) ) . '" ', $tag );
		}
		if ( ! EpiFive_WPK_Options::ready() ) {
			return $tag;
		}
		$gated = EpiFive_WPK_Options::gated_handles();
		if ( isset( $gated[ $handle ] ) ) {
			return self::gate( $tag, $gated[ $handle ] );
		}
		return $tag;
	}

	/**
	 * Rewrite every <script> in a tag string so it waits for a purpose.
	 *
	 * @param string $html    One or more script tags (WordPress may print inline before/after scripts too).
	 * @param string $purpose Purpose key.
	 * @return string
	 */
	public static function gate( $html, $purpose ) {
		return (string) preg_replace_callback(
			'/<script\b([^>]*)>/i',
			function ( $m ) use ( $purpose ) {
				$attrs = preg_replace( '/\stype=("|\')[^"\']*\1/i', '', $m[1] );
				return '<script type="text/plain" data-wpk-purpose="' . esc_attr( $purpose ) . '"' . $attrs . '>';
			},
			$html
		);
	}

	/**
	 * [wpk_privacy_link text="Privacy centre"]: link to the privacy hub (notice, rights, withdraw).
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode_link( $atts ) {
		$atts = shortcode_atts( array( 'text' => __( 'Privacy centre', 'epifive-website-privacy-kit' ) ), $atts, 'wpk_privacy_link' );
		$url  = EpiFive_WPK_Options::hub_url();
		if ( '' === $url ) {
			return '';
		}
		return '<a class="wpk-privacy-link" href="' . esc_url( $url ) . '">' . esc_html( $atts['text'] ) . '</a>';
	}

	/**
	 * [wpk_privacy_choices text="Privacy choices"]: reopens the banner's preference centre.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode_choices( $atts ) {
		$atts = shortcode_atts( array( 'text' => __( 'Privacy choices', 'epifive-website-privacy-kit' ) ), $atts, 'wpk_privacy_choices' );
		return '<a class="wpk-privacy-choices" href="#" role="button" data-wpk-open>' . esc_html( $atts['text'] ) . '</a>';
	}

	/**
	 * [wpk_form_consent purposes="marketing_email" field="email"]: unticked consent checkboxes for the
	 * surrounding form, recorded with the value of the email (or phone) field on submit.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode_form_consent( $atts ) {
		$atts = shortcode_atts(
			array(
				'purposes' => '',
				'field'    => 'email',
			),
			$atts,
			'wpk_form_consent'
		);
		return self::form_placeholder( EpiFive_WPK_Options::purpose_list( $atts['purposes'] ), (string) $atts['field'] );
	}

	/**
	 * Hidden marker that assets/wpk-forms.js turns into wpk.form(form, …).
	 *
	 * @param string[] $purposes Purpose keys (empty = every consent purpose of the site).
	 * @param string   $field    Name of the email or phone input.
	 * @return string
	 */
	public static function form_placeholder( $purposes, $field ) {
		if ( ! EpiFive_WPK_Options::ready() ) {
			return '';
		}
		self::enqueue_forms();
		$field = substr( (string) preg_replace( '/[^A-Za-z0-9_\-\[\]]/', '', $field ), 0, 100 );
		return '<span class="wpk-form-consent" hidden data-wpk-purposes="' . esc_attr( implode( ',', $purposes ) ) . '" data-wpk-field="' . esc_attr( $field ) . '"></span>';
	}

	/**
	 * The small helper that connects placeholders to the banner's form API.
	 */
	public static function enqueue_forms() {
		wp_enqueue_script( self::FORMS, EPIFIVE_WPK_URL . 'assets/wpk-forms.js', array(), EPIFIVE_WPK_VERSION, array( 'in_footer' => true ) );
	}

	/**
	 * "Privacy hub link" block (dynamic, rendered by the shortcode logic).
	 */
	public static function register_block() {
		register_block_type(
			EPIFIVE_WPK_DIR . 'blocks/privacy-link',
			array( 'render_callback' => array( __CLASS__, 'render_block' ) )
		);
	}

	/**
	 * Block output.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render_block( $attributes ) {
		$text = isset( $attributes['text'] ) && '' !== $attributes['text'] ? (string) $attributes['text'] : __( 'Privacy centre', 'epifive-website-privacy-kit' );
		$html = self::shortcode_link( array( 'text' => $text ) );
		if ( ! empty( $attributes['showChoices'] ) ) {
			$html .= ' · ' . self::shortcode_choices( array( 'text' => __( 'Privacy choices', 'epifive-website-privacy-kit' ) ) );
		}
		return '' === $html ? '' : '<p ' . get_block_wrapper_attributes() . '>' . $html . '</p>';
	}
}
