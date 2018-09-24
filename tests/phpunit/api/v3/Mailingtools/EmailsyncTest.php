<?php

use CRM_Mailingtools_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Mailingtools.Emailsync API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_Mailingtools_EmailsyncTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface {

  private $contact_id;
  private $email_ids;
  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   * See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
   */
  public function setUpHeadless() {
    // Not needed, or rather no schema needed
//    return \Civi\Test::headless()
//      ->installMe(__DIR__)
//      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    // create Contact
    $result = civicrm_api3('Contact', 'create', [
      'contact_type' => "Individual",
      'first_name' => "Mailingtools",
      "middle_name" => "Unittest",
      'last_name' => "Example",
    ]);
    if ($result['is_error'] == '1') {
      throw new Exception("Couldn't create contact.");
    }
    $this->contact_id = $result['id'];
    // create 6 valid emails
    foreach (range(1, 6) as $number) {
      $email = "example_{$number}@systopia.de";
      $this->create_email($email);
    }
    // create 4 invalid emails (dns lookup fails)
    foreach (range(7, 10) as $number) {
      $email = "example_{$number}@systopai.de";
      $this->create_email($email);
    }
    parent::setUp();
  }

  private function create_email($email) {
    $result = civicrm_api3('Email', 'create', [
      'contact_id' => $this->contact_id,
      'email' => $email,
    ]);
    if ($result['is_error'] == '1') {
      throw new Exception("Couldn't create email {$email} for contact {$this->contact_id}");
    }
    $this->email_ids[] = $result['id'];
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() {
    foreach ($this->email_ids as $key => $email_id) {
      $this->delete_entity($email_id, 'Email');
    }
    $this->delete_entity($this->contact_id, 'Contact');
  }

  private function delete_entity($entity_id, $entity) {
    $result = civicrm_api3($entity, 'delete', [
      'id' => $entity_id,
    ]);
    if ($result['is_error'] == '1') {
      throw new Exception("Couldn't delete Entity {$entity} ({$entity_id}). Abroting Test");
    }
    parent::tearDown();
  }

  /**
   * Simple example test case.
   *
   * Note how the function name begins with the word "test".
   */
  public function testEmailVerifier() {
    $result = civicrm_api3('Mailingtools', 'emailsync', [
      'verify_size' => 10,
      'checking_index' => $this->email_ids['0'],
      'debug' => "TRUE",
    ]);
    if ($result['is_error'] == '1') {
      echo "\nError in Mailingtools->emailsync API call. See logs for more details. Message: {$result['error_message']}\n";
      return;
    }
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'email' => ['LIKE' => "example_%@systop%.de"],
    ]);
    if ($result['count'] != 10) {
      throw new Exception("Couldn't Find the appropriate amount of Emails matching the creation pattern. Found {$result['count']} instead of 10");
    }
    $on_hold_counter = 0;
    $activated_email_counter = 0;
    foreach ($result['values'] as $value) {
      if ($value['on_hold'] == '1') {
        $on_hold_counter += 1;
      } else {
        $activated_email_counter += 1;
      }
    }
    if ($on_hold_counter == 4 && $activated_email_counter == 6) {
      echo "Test successful.\n";
    } else {
      throw new Exception("Test unsuccessful. Found {$on_hold_counter} on_hold Emails matching the pattern and {$activated_email_counter} normal emails matching the pattern.");
    }
  }

}
