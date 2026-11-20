<?php
/**
 * Settings → Website Privacy Kit.
 *
 * @package EpiFive_Website_Privacy_Kit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin screen built on the Settings API.
 */
class EpiFive_WPK_Settings {

	const PAGE = 'epifive-website-privacy-kit';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_epifive_wpk_test', array( __CLASS__, 'test_connection' ) );
	}

	/**
	 * Menu entry.
	 */
	public static function menu() {
		add_options_page(
			__( 'Website Privacy Kit', 'epifive-website-privacy-kit' ),
			__( 'Website Privacy Kit', 'epifive-website-privacy-kit' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Register the option, sections and fields.
	 */
	public static function register() {
		register_setting(
			self::PAGE,
			EpiFive_WPK_Options::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => EpiFive_WPK_Options::defaults(),
			)
		);

		add_settings_section( 'epifive_wpk_server', __( 'Connection', 'epifive-website-privacy-kit' ), array( __CLASS__, 'section_server' ), self::PAGE );
		self::field( 'server_url', __( 'Server address', 'epifive-website-privacy-kit' ), 'epifive_wpk_server', 'url', __( 'Where your Privacy Kit runs, e.g. https://privacy.example.com (self-hosted Free or Pro edition).', 'epifive-website-privacy-kit' ) );
		self::field( 'site_key', __( 'Site key', 'epifive-website-privacy-kit' ), 'epifive_wpk_server', 'text', __( 'The public key of this website from Dashboard → Websites in the Privacy Kit (starts with pk_).', 'epifive-website-privacy-kit' ) );
		self::field( 'enabled', __( 'Show the consent banner', 'epifive-website-privacy-kit' ), 'epifive_wpk_server', 'checkbox', __( 'Loads the banner script on every public page.', 'epifive-website-privacy-kit' ) );

		add_settings_section( 'epifive_wpk_scripts', __( 'Scripts that wait for consent', 'epifive-website-privacy-kit' ), array( __CLASS__, 'section_scripts' ), self::PAGE );
		self::field( 'consent_mode', __( 'Google Consent Mode v2 defaults', 'epifive-website-privacy-kit' ), 'epifive_wpk_scripts', 'checkbox', __( 'Prints "denied" defaults for ads and analytics storage at the top of the page, before any Google tag. The banner updates them after a choice.', 'epifive-website-privacy-kit' ) );
		self::field( 'gate_handles', __( 'Gated script handles', 'epifive-website-privacy-kit' ), 'epifive_wpk_scripts', 'textarea', __( 'One per line: script-handle=purpose_key, e.g. google-analytics=analytics. These enqueued scripts run only after the visitor agrees to that purpose.', 'epifive-website-privacy-kit' ) );

		add_settings_section( 'epifive_wpk_links', __( 'Privacy hub', 'epifive-website-privacy-kit' ), array( __CLASS__, 'section_links' ), self::PAGE );
		self::field( 'hub_url', __( 'Privacy hub address', 'epifive-website-privacy-kit' ), 'epifive_wpk_links', 'url', __( 'Optional. Leave empty to use the hub of this site key (found automatically).', 'epifive-website-privacy-kit' ) );

		add_settings_section( 'epifive_wpk_forms', __( 'Form consent checkboxes', 'epifive-website-privacy-kit' ), array( __CLASS__, 'section_forms' ), self::PAGE );
		self::field( 'cf7_purposes', __( 'Contact Form 7: purposes', 'epifive-website-privacy-kit' ), 'epifive_wpk_forms', 'text', __( 'Comma-separated purpose keys added to every Contact Form 7 form, e.g. marketing_email. Leave empty and use the [wpk_consent] form tag per form instead.', 'epifive-website-privacy-kit' ) );
		self::field( 'cf7_field', __( 'Contact Form 7: email field', 'epifive-website-privacy-kit' ), 'epifive_wpk_forms', 'text', __( 'Name of the email field, usually your-email.', 'epifive-website-privacy-kit' ) );
		self::field( 'wpf_purposes', __( 'WPForms: purposes', 'epifive-website-privacy-kit' ), 'epifive_wpk_forms', 'text', __( 'Comma-separated purpose keys added to every WPForms form. Leave empty for none.', 'epifive-website-privacy-kit' ) );
		self::field( 'wpf_field', __( 'WPForms: email field name', 'epifive-website-privacy-kit' ), 'epifive_wpk_forms', 'text', __( 'The name attribute of the email input, e.g. wpforms[fields][1]. Leave empty to use the first email input of the form.', 'epifive-website-privacy-kit' ) );
	}

	/**
	 * Add one field.
	 *
	 * @param string $key     Setting name.
	 * @param string $label   Label.
	 * @param string $section Section id.
	 * @param string $type    url|text|textarea|checkbox.
	 * @param string $help    Help text.
	 */
	private static function field( $key, $label, $section, $type, $help ) {
		add_settings_field(
			'epifive_wpk_' . $key,
			$label,
			array( __CLASS__, 'render_field' ),
			self::PAGE,
			$section,
			array(
				'key'       => $key,
				'type'      => $type,
				'help'      => $help,
				'label_for' => 'epifive_wpk_' . $key,
			)
		);
	}

	/**
	 * Field markup.
	 *
	 * @param array<string, string> $args Field arguments.
	 */
	public static function render_field( $args ) {
		$key   = $args['key'];
		$id    = 'epifive_wpk_' . $key;
		$name  = EpiFive_WPK_Options::OPTION . '[' . $key . ']';
		$value = EpiFive_WPK_Options::get( $key );
		if ( 'checkbox' === $args['type'] ) {
			printf( '<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>', esc_attr( $id ), esc_attr( $name ), checked( (bool) $value, true, false ), esc_html( $args['help'] ) );
			return;
		}
		if ( 'textarea' === $args['type'] ) {
			printf( '<textarea id="%1$s" name="%2$s" rows="4" class="large-text code">%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
		} else {
			printf( '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" />', esc_attr( 'url' === $args['type'] ? 'url' : 'text' ), esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
		}
		printf( '<p class="description">%s</p>', esc_html( $args['help'] ) );
	}

	/**
	 * Clean every value before it is saved.
	 *
	 * @param mixed $input Posted values.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = EpiFive_WPK_Options::defaults();

		$server = isset( $input['server_url'] ) ? esc_url_raw( trim( (string) $input['server_url'] ), array( 'https', 'http' ) ) : '';
		if ( '' !== $server && 0 !== strpos( $server, 'https://' ) && ! preg_match( '#^http://(localhost|127\.0\.0\.1)(:\d+)?(/|$)#', $server ) ) {
			add_settings_error( EpiFive_WPK_Options::OPTION, 'epifive_wpk_https', __( 'Use an https:// server address (plain http only works for localhost).', 'epifive-website-privacy-kit' ) );
			$server = '';
		}
		$out['server_url'] = untrailingslashit( $server );

		$key = isset( $input['site_key'] ) ? trim( sanitize_text_field( (string) $input['site_key'] ) ) : '';
		if ( '' !== $key && ! preg_match( '/^[A-Za-z0-9_]{8,64}$/', $key ) ) {
			add_settings_error( EpiFive_WPK_Options::OPTION, 'epifive_wpk_key', __( 'The site key looks wrong. Copy it from Dashboard → Websites (it starts with pk_).', 'epifive-website-privacy-kit' ) );
			$key = '';
		}
		$out['site_key'] = $key;

		$out['enabled']      = empty( $input['enabled'] ) ? 0 : 1;
		$out['consent_mode'] = empty( $input['consent_mode'] ) ? 0 : 1;
		$out['gate_handles'] = isset( $input['gate_handles'] ) ? sanitize_textarea_field( (string) $input['gate_handles'] ) : '';
		$out['hub_url']      = isset( $input['hub_url'] ) ? esc_url_raw( trim( (string) $input['hub_url'] ), array( 'https', 'http' ) ) : '';
		$out['cf7_purposes'] = implode( ',', EpiFive_WPK_Options::purpose_list( isset( $input['cf7_purposes'] ) ? (string) $input['cf7_purposes'] : '' ) );
		$out['cf7_field']    = isset( $input['cf7_field'] ) ? self::field_name( (string) $input['cf7_field'] ) : 'your-email';
		$out['wpf_purposes'] = implode( ',', EpiFive_WPK_Options::purpose_list( isset( $input['wpf_purposes'] ) ? (string) $input['wpf_purposes'] : '' ) );
		$out['wpf_field']    = isset( $input['wpf_field'] ) ? self::field_name( (string) $input['wpf_field'] ) : '';
		return $out;
	}

	/**
	 * A form field name: letters, digits, - _ [ ] only.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function field_name( $value ) {
		return substr( (string) preg_replace( '/[^A-Za-z0-9_\-\[\]]/', '', $value ), 0, 100 );
	}

	/**
	 * Section intro: connection.
	 */
	public static function section_server() {
		echo '<p>' . esc_html__( 'Install the Privacy Kit on your own server (Free or Pro edition), add this website under Dashboard → Websites, then paste the address and the site key here.', 'epifive-website-privacy-kit' ) . '</p>';
	}

	/**
	 * Section intro: scripts.
	 */
	public static function section_scripts() {
		echo '<p>' . esc_html__( 'Scripts added in a theme or another plugin can also wait for consent: give them type="text/plain" and data-wpk-purpose="analytics" (the purpose key from your dashboard).', 'epifive-website-privacy-kit' ) . '</p>';
	}

	/**
	 * Section intro: links.
	 */
	public static function section_links() {
		echo '<p>' . esc_html__( 'Use the [wpk_privacy_link] and [wpk_privacy_choices] shortcodes or the "Privacy hub link" block, for example in the footer.', 'epifive-website-privacy-kit' ) . '</p>';
	}

	/**
	 * Section intro: forms.
	 */
	public static function section_forms() {
		echo '<p>' . esc_html__( 'Adds unticked consent checkboxes with the notice link above the submit button, and records each choice with the email address in the consent log. In any form you can also place the [wpk_form_consent purposes="marketing_email" field="email"] shortcode.', 'epifive-website-privacy-kit' ) . '</p>';
	}

	/**
	 * Settings page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$status = isset( $_GET['epifive_wpk_test'] ) ? sanitize_key( wp_unslash( $_GET['epifive_wpk_test'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice after a redirect.
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EpiFive Website Privacy Kit', 'epifive-website-privacy-kit' ); ?></h1>
			<p><?php esc_html_e( 'This plugin helps you organise DPDP tasks on your website. It is not legal advice and does not make your site compliant on its own. You remain the Data Fiduciary. The Privacy Kit is not a Consent Manager registered with the Data Protection Board of India.', 'epifive-website-privacy-kit' ); ?></p>
			<?php if ( 'ok' === $status ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Connected: the server answered with this site\'s banner settings.', 'epifive-website-privacy-kit' ); ?></p></div>
			<?php elseif ( 'fail' === $status ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Could not read the banner settings. Check the server address, the site key and that a notice is published.', 'epifive-website-privacy-kit' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::PAGE );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="epifive_wpk_test" />
				<?php wp_nonce_field( 'epifive_wpk_test' ); ?>
				<?php submit_button( __( 'Test connection', 'epifive-website-privacy-kit' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Fetch the banner configuration once and report.
	 */
	public static function test_connection() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'epifive-website-privacy-kit' ) );
		}
		check_admin_referer( 'epifive_wpk_test' );
		$config = EpiFive_WPK_Options::remote_config( true );
		wp_safe_redirect( add_query_arg( 'epifive_wpk_test', $config ? 'ok' : 'fail', admin_url( 'options-general.php?page=' . self::PAGE ) ) );
		exit;
	}
}
