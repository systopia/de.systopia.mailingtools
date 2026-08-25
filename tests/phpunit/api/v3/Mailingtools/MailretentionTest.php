<?php
declare(strict_types = 1);

use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\Invasive;
use Civi\Test\TransactionalInterface;

/**
 * Mailingtools.Mailretention API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 *
 * @covers \CRM_Mailingtools_CheckMailstore
 */
// phpcs:ignore Generic.Files.LineLength.TooLong
class api_v3_Mailingtools_MailretentionTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   * See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return mixed
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp(): void {
    parent::setUp();
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Simple example test case.
   *
   * Note how the function name begins with the word "test".
   *
   * @return void
   */
  public function testApiExample() {
    $result = civicrm_api3('Mailingtools', 'Mailretention', []);
    self::assertNull($result['values']);
  }

  /**
   * @return void
   */
  public function testImapSuffixAndPortFollowSslFlag() {
    $mailstore = new CRM_Mailingtools_CheckMailstore();
    $dao = new CRM_Core_DAO_MailSettings();

    $dao->is_ssl = TRUE;
    self::assertSame('/imap/ssl', Invasive::call([$mailstore, 'create_imap_suffix'], [$dao]));
    self::assertSame(993, Invasive::call([$mailstore, 'get_server_port'], [$dao]));

    $dao->is_ssl = FALSE;
    self::assertSame('/imap/novalidate-cert', Invasive::call([$mailstore, 'create_imap_suffix'], [$dao]));
    self::assertSame(143, Invasive::call([$mailstore, 'get_server_port'], [$dao]));
  }

  /**
   * @return void
   */
  public function testRetentionTimestampUsesImapDateFormat() {
    $mailstore = new CRM_Mailingtools_CheckMailstore();
    Invasive::set([$mailstore, 'mailStore_retention'], ['INBOX.CiviMail.ignored' => 30]);

    $timestamp = Invasive::call([$mailstore, 'create_retention_timestamp'], ['INBOX.CiviMail.ignored']);

    self::assertIsString($timestamp);
    self::assertMatchesRegularExpression('/^\d{1,2}-[A-Z][a-z]{2}-\d{4}$/', $timestamp);
    self::assertSame(date('j-M-Y', strtotime('now - 30 days')), $timestamp);
  }

}
