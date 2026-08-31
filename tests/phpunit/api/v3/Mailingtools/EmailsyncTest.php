<?php
declare(strict_types = 1);

use CRM_Mailingtools_ExtensionUtil as E;
use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Mailingtools.Emailsync API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 *
 * @covers \CRM_Mailingtools_EmailVerifier
 */
class api_v3_Mailingtools_EmailsyncTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * @var int|string|null */
  private $contact_id;

  /**
   * @var array<int, mixed> */
  private $email_ids;

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   * See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
   *
   * @return \Civi\Test\CiviEnvBuilder
   */
  public function setUpHeadless() {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp(): void {
    // create Contact
    $result = civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Mailingtools',
      'middle_name' => 'Unittest',
      'last_name' => 'Example',
    ]);
    if ((string) $result['is_error'] === '1') {
      throw new \RuntimeException("Couldn't create contact.");
    }
    $this->contact_id = $result['id'];
    // create 6 valid emails
    foreach (range(1, 6) as $number) {
      $email = "example_{$number}@systopia.de ";
      $this->create_email($email);
    }
    // create 4 invalid emails (dns lookup fails)
    foreach (range(7, 10) as $number) {
      $email = "example_{$number}@systopai.de";
      $this->create_email($email);
    }
    parent::setUp();
  }

  /**
   * @param string $email
   * @return void
   */
  private function create_email($email) {
    $result = civicrm_api3('Email', 'create', [
      'contact_id' => $this->contact_id,
      'email' => $email,
    ]);
    if ((string) $result['is_error'] === '1') {
      throw new \RuntimeException("Couldn't create email {$email} for contact {$this->contact_id}");
    }
    $this->email_ids[] = $result['id'];
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown(): void {
    foreach ($this->email_ids as $email_id) {
      $this->delete_entity($email_id, 'Email');
    }
    $this->delete_entity($this->contact_id, 'Contact');
    parent::tearDown();
  }

  /**
   * @param mixed $entity_id
   * @param string $entity
   * @return void
   */
  private function delete_entity($entity_id, $entity) {
    $result = civicrm_api3($entity, 'delete', [
      'id' => $entity_id,
    ]);
    if ((string) $result['is_error'] === '1') {
      $entity_id_string = CRM_Mailingtools_Utils::toString($entity_id);
      throw new \RuntimeException("Couldn't delete Entity {$entity} ({$entity_id_string}). Abroting Test");
    }
  }

  /**
   * Simple example test case.
   *
   * Note how the function name begins with the word "test".
   *
   * @return void
   */
  public function testEmailVerifier() {
    $result = civicrm_api3('Mailingtools', 'emailsync', [
      'verify_size' => 10,
      'checking_index' => $this->email_ids['0'],
      'debug' => 'TRUE',
    ]);
    self::assertSame(1, $result['is_error']);
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'email' => ['LIKE' => 'example_%@systop%.de%'],
    ]);
    self::assertSame(10, $result['count']);
    $on_hold_counter = 0;
    $activated_email_counter = 0;
    foreach ($result['values'] as $value) {
      if ((string) $value['on_hold'] === '1') {
        $on_hold_counter += 1;
      }
      else {
        $activated_email_counter += 1;
      }
    }
    self::assertSame(4, $on_hold_counter);
    self::assertSame(6, $activated_email_counter);
  }

}
