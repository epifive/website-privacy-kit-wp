<?php
/**
 * Contact Form 7 and WPForms: consent checkboxes in forms.
 *
 * @package EpiFive_Website_Privacy_Kit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Optional integrations; each runs only when its plugin is active.
 */
class EpiFive_WPK_Integrations {

	/**
	 * Hooks.
	 */
	public static function init() {
		// Contact Form 7: [wpk_consent marketing_email field:your-email] form tag, or the same checkboxes in every form.
		add_action( 'wpcf7_init', array( __CLASS__, 'cf7_tag' ) );
		add_filter( 'wpcf7_form_elements', array( __CLASS__, 'cf7_all_forms' ) );
		// WPForms: the checkboxes in every form when purposes are set.
		add_action( 'wpforms_display_submit_before', array( __CLASS__, 'wpforms' ) );
	}

	/**
	 * Register the Contact Form 7 form tag.
	 */
	public static function cf7_tag() {
		if ( function_exists( 'wpcf7_add_form_tag' ) ) {
			wpcf7_add_form_tag( 'wpk_consent', array( __CLASS__, 'cf7_render' ) );
		}
	}

	/**
	 * [wpk_consent purpose_a purpose_b field:your-email] in a Contact Form 7 form.
	 *
	 * @param object $tag WPCF7_FormTag.
	 * @return string
	 */
	public static function cf7_render( $tag ) {
		$purposes = array();
		$field    = (string) EpiFive_WPK_Options::get( 'cf7_field' );
		$options  = isset( $tag->options ) && is_array( $tag->options ) ? $tag->options : array();
		$values   = isset( $tag->values ) && is_array( $tag->values ) ? $tag->values : array();
		foreach ( $options as $option ) {
			if ( 0 === strpos( (string) $option, 'field:' ) ) {
				$field = substr( (string) $option, 6 );
			} else {
				$purposes[] = (string) $option;
			}
		}
		$purposes = EpiFive_WPK_Options::purpose_list( implode( ',', array_merge( $purposes, $values ) ) );
		return EpiFive_WPK_Frontend::form_placeholder( $purposes, $field );
	}

	/**
	 * Same checkboxes in every Contact Form 7 form when purposes are set in the settings.
	 *
	 * @param string $html Form elements.
	 * @return string
	 */
	public static function cf7_all_forms( $html ) {
		$purposes = EpiFive_WPK_Options::purpose_list( (string) EpiFive_WPK_Options::get( 'cf7_purposes' ) );
		if ( ! $purposes || false !== strpos( $html, 'wpk-form-consent' ) ) {
			return $html;
		}
		return $html . EpiFive_WPK_Frontend::form_placeholder( $purposes, (string) EpiFive_WPK_Options::get( 'cf7_field' ) );
	}

	/**
	 * WPForms: before the submit button.
	 */
	public static function wpforms() {
		$purposes = EpiFive_WPK_Options::purpose_list( (string) EpiFive_WPK_Options::get( 'wpf_purposes' ) );
		if ( ! $purposes ) {
			return;
		}
		echo EpiFive_WPK_Frontend::form_placeholder( $purposes, (string) EpiFive_WPK_Options::get( 'wpf_field' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes are escaped in form_placeholder().
	}
}
