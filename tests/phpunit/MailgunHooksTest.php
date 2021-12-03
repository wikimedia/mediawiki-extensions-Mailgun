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
	 * Test that onAlternateUserMailer throws Exception if api key is missing.
	 * @covers MailgunHooks::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerNoApiKey() {
		$this->expectException( MWException::class );
		$this->expectExceptionMessage(
			'Please update your LocalSettings.php with the correct Mailgun API configuration'
		);

		RequestContext::getMain()->setConfig( new MultiConfig( [
			new HashConfig( [
				'MailgunAPIKey' => '',
				'MailgunDomain' => 'example.com'
			] ),
		] ) );

		MailgunHooks::onAlternateUserMailer(
			[ 'Some header' => 'Some value' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
	}

	/**
	 * Test that onAlternateUserMailer throws Exception if mailgun domain is missing.
	 * @covers MailgunHooks::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerNoDomain() {
		$this->expectException( MWException::class );
		$this->expectExceptionMessage(
			'Please update your LocalSettings.php with the correct Mailgun API configuration'
		);

		RequestContext::getMain()->setConfig( new MultiConfig( [
			new HashConfig( [
				'MailgunAPIKey' => 'api_key',
				'MailgunDomain' => ''
			] ),
		] ) );

		MailgunHooks::onAlternateUserMailer(
			[ 'Some header' => 'Some value' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
	}

	/**
	 * Test sending mail in onAlternateUserMailer hook.
	 * @covers MailgunHooks::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailer() {
		RequestContext::getMain()->setConfig( new MultiConfig( [
			new HashConfig( [
				'MailgunAPIKey' => 'api_key',
				'MailgunDomain' => 'example.com'
			] ),
		] ) );

		$mock = $this->getMockBuilder( \Mailgun\Mailgun::class )
				->onlyMethods( [ 'messages' ] )
				->disableOriginalConstructor()
				->getMock();

		$message = $this->getMockBuilder( \Mailgun\Api\Message::class )
			->onlyMethods( [ 'getBatchMessage' ] )
			->disableOriginalConstructor()
			->getMock();

		$batchMessage = $this->getMockBuilder( \Mailgun\Message\BatchMessage::class )
				->onlyMethods( [
					'setFromAddress', 'setSubject', 'setTextBody', 'setReplyToAddress',
					'addCustomHeader', 'addToRecipient', 'finalize'
				] )
				->disableOriginalConstructor()
				->getMock();

		$mock->expects( $this->once() )->method( 'messages' )
		->will( $this->returnValue( $message ) );

		$message->expects( $this->once() )->method( 'getBatchMessage' )
				->with( $this->equalTo( 'example.com' ) )
				->will( $this->returnValue( $batchMessage ) );

		$batchMessage->expects( $this->once() )
				->method( 'setFromAddress' )
				->with( $this->equalTo( 'sender@example.com' ) );
		$batchMessage->expects( $this->once() )
				->method( 'setSubject' )
				->with( $this->equalTo( 'Some subject' ) );
		$batchMessage->expects( $this->once() )
				->method( 'setTextBody' )
				->with( $this->equalTo( 'Email body' ) );
		$batchMessage->expects( $this->once() )
				->method( 'setReplyToAddress' )
				->with( $this->equalTo( 'Return-Path-value' ) );
		$batchMessage->expects( $this->exactly( 2 ) )
				->method( 'addCustomHeader' )
				->withConsecutive(
					[ 'X-Mailer', 'X-Mailer-value' ],
					[ 'List-Unsubscribe', 'List-Unsubscribe-value' ]
				);
		$batchMessage->expects( $this->once() )
				->method( 'addToRecipient' )
				->with( $this->equalTo( 'receiver@example.com' ) );
		$batchMessage->expects( $this->once() )
				->method( 'finalize' );

		$result = MailgunHooks::sendBatchMessage(
			$mock,
			'example.com',
			[
				'Return-Path' => 'Return-Path-value',
				'X-Mailer' => 'X-Mailer-value',
				'List-Unsubscribe' => 'List-Unsubscribe-value'
			],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
		$this->assertSame( false, $result );
	}
}
