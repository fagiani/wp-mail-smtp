<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\WP;
use WPMailSMTP\Admin\PageAbstract;

/**
 * Class Test is part of Area, displays email testing page of the plugin.
 */
class Test extends PageAbstract {

	/**
	 * @var string Slug of a subpage.
	 */
	protected $slug = 'test';

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return __( 'Email Test', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return __( 'Send a Test Email', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function display() {
		?>

		<form method="POST" action="">
			<?php $this->wp_nonce_field(); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-test-email"><?php _e( 'Send To', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[test_email]" type="email" id="wp-mail-smtp-test-email" required class="regular-text" spellcheck="false" />
						<p class="description"><?php _e( 'Type an email address here and then click a button below to generate a test email.', 'wp-mail-smtp' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="wp-mail-smtp[test_submit]" class="button-primary" value="<?php _e( 'Send Email', 'wp-mail-smtp' ); ?>"/>
			</p>
		</form>

		<?php
	}

	/**
	 * @inheritdoc
	 */
	public function process( $data ) {

		$this->check_admin_referer();

		if ( isset( $data['test_email'] ) ) {
			$data['test_email'] = filter_var( $data['test_email'], FILTER_VALIDATE_EMAIL );
		}

		if (
			! isset( $data['test_submit'] ) ||
			empty( $data['test_email'] )
		) {
			WP::add_admin_notice(
				__( 'Test failed. Please use a valid email address and try to resend the test email.', 'wp-mail-smtp' ),
				WP::ADMIN_NOTICE_WARNING
			);
			return;
		}

		global $phpmailer;

		// Make sure the PHPMailer class has been instantiated.
		if ( ! is_object( $phpmailer ) || ! is_a( $phpmailer, 'PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$phpmailer = new \PHPMailer( true );
		}

		// Set SMTPDebug level, default is 0 (no output).
		$phpmailer->SMTPDebug = apply_filters( 'wp_mail_smtp_test_email_smtp_debug', 0 );

		// Start output buffering to grab smtp debugging output.
		ob_start();

		// Send the test mail.
		$result = wp_mail(
			$data['test_email'],
			/* translators: %s - email address a test email will be sent to. */
			'WP Mail SMTP: ' . sprintf( __( 'Test mail to %s', 'wp-mail-smtp' ), $data['test_email'] ),
			__( 'This is a test email generated by the WP Mail SMTP WordPress plugin.', 'wp-mail-smtp' )
		);

		// Grab the smtp debugging output.
		$smtp_debug = ob_get_clean();

		/*
		 * Do the actual sending.
		 */
		if ( $result ) {
			WP::add_admin_notice(
				__( 'Your email was sent successfully!', 'wp-mail-smtp' ),
				WP::ADMIN_NOTICE_SUCCESS
			);
		} else {
			WP::add_admin_notice(
				'<p><strong>' . __( 'There was a problem while sending a test email.', 'wp-mail-smtp' ) . '</strong></p>' .
				'<p>' . __( 'The full debugging output is shown below:', 'wp-mail-smtp' ) . '</p>' .
				'<p><pre>' . print_r( $phpmailer, true ) . '</pre></p>' .
				'<p>' . __( 'The SMTP debugging output is shown below:', 'wp-mail-smtp' ) . '</p>' .
				'<p><pre>' . $smtp_debug . '</pre></p>',
				WP::ADMIN_NOTICE_ERROR
			);
		}
	}
}
