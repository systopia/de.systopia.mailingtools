<?php
declare(strict_types = 1);

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 *
 * @covers \CRM_Mailingtools_Utils
 */
class CRM_Mailingtools_UtilsTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * @var array<string, mixed> */
  private $original_settings = [];

  /**
   * @return \Civi\Test\CiviEnvBuilder
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $config = CRM_Mailingtools_Config::singleton();
    $this->original_settings = $config->getSettings();
    $settings = $this->original_settings;
    $settings['email_domain_blacklist'] = 'blacklisted.example.org';
    $config->setSettings($settings);
  }

  public function tearDown(): void {
    CRM_Mailingtools_Config::singleton()->setSettings($this->original_settings);
    parent::tearDown();
  }

  /**
   * @return void
   */
  public function testBlacklistCheckToleratesMissingEmail() {
    self::assertFalse(CRM_Mailingtools_Utils::check_email_dns_blacklist(NULL, 42));
    self::assertFalse(CRM_Mailingtools_Utils::check_email_dns_blacklist('', 42));
  }

  /**
   * @return void
   */
  public function testBlacklistCheckIgnoresMissingEmailId() {
    self::assertFalse(CRM_Mailingtools_Utils::check_email_dns_blacklist('someone@blacklisted.example.org', NULL));
    self::assertFalse(CRM_Mailingtools_Utils::check_email_dns_blacklist('someone@blacklisted.example.org', 0));
  }

  /**
   * @return void
   */
  public function testBlacklistCheckIgnoresUnlistedDomain() {
    self::assertFalse(CRM_Mailingtools_Utils::check_email_dns_blacklist('someone@allowed.example.org', 42));
  }

}
