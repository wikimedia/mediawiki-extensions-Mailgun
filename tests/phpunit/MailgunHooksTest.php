<?php
/**
 * Test for hooks code.
 *
 * @file
 * @author Nikita Volobuev <nikitavbv@gmail.com>
 * @license GPL-2.0-or-later
 */

class MailgunHooksTest extends MediaWikiIntegrationTestCase {

	/**
	 * Test that onAlternateUserMailer returns null if API key is missing.
	 * @covers MailgunHooks::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerNoApiKey() {
		RequestContext::getMain()->setConfig( new MultiConfig( [
			new HashConfig( [
				'MailgunAPIKey' => '',
				'MailgunDomain' => 'example.com'
			] ),
		] ) );

		$headers = [ 'Some header' => 'Some value' ];
		$to = [ new MailAddress( 'receiver@example.com' ) ];
		$from = new MailAddress( 'sender@example.com' );
		$subject = 'Some subject';
		$body = 'Email body';

		$result = MailgunHooks::onAlternateUserMailer( $headers, $to, $from, $subject, $body );

		$this->assertNull( $result, 'Should return null when API key is missing' );
	}

	/**
	 * Test that onAlternateUserMailer returns null if mailgun domain is missing.
	 * @covers MailgunHooks::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerNoDomain() {
		RequestContext::getMain()->setConfig( new MultiConfig( [
			new HashConfig( [
				'MailgunAPIKey' => 'api_key',
				'MailgunDomain' => ''
			] ),
		] ) );

		$headers = [ 'Some header' => 'Some value' ];
		$to = [ new MailAddress( 'receiver@example.com' ) ];
		$from = new MailAddress( 'sender@example.com' );
		$subject = 'Some subject';
		$body = 'Email body';

		$result = MailgunHooks::onAlternateUserMailer( $headers, $to, $from, $subject, $body );

		$this->assertNull( $result, 'Should return null when domain is missing' );
	}

	/**
	 * Test that onAlternateUserMailer properly handles configuration.
	 * @covers MailgunHooks::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerWithConfig() {
		RequestContext::getMain()->setConfig( new MultiConfig( [
			new HashConfig( [
				'MailgunAPIKey' => 'key-test-api-key',
				'MailgunDomain' => 'example.com'
			] ),
		] ) );

		$headers = [
			'Return-Path' => 'bounce@example.com',
			'X-Mailer' => 'MediaWiki mailer'
		];
		$to = [ new MailAddress( 'user@example.com', 'User' ) ];
		$from = new MailAddress( 'noreply@example.com', 'Example Site' );
		$subject = 'Test Subject';
		$body = 'Test email body';

		// With test credentials, expect null (fallback) due to authentication failure
		$result = MailgunHooks::onAlternateUserMailer( $headers, $to, $from, $subject, $body );

		$this->assertNull( $result, 'Should return null with test credentials' );
	}
}
