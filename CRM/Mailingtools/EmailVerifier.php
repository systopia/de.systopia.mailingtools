<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Mailingtools_ExtensionUtil as E;

/**
 * Class CRM_Mailingtools_EmailVerifier
 */
class CRM_Mailingtools_EmailVerifier {

  /**
   * @var int */
  private $verify_size;

  /**
   * @var int */
  private $checking_index;

  /**
   * @var array<int, array<string, mixed>> */
  private $email_lookup_values;

  /**
   * @var array<string, int> */
  private $result_stats;

  /**
   * CRM_Mailingtools_EmailVerifier constructor.
   *
   * @param int $verify_size
   * @param int|null $checking_index
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct($verify_size, $checking_index) {
    $this->check_voku_email_checker_include();
    $this->verify_size = $verify_size;
    if (isset($checking_index)) {
      $this->checking_index = $checking_index;
    }
    else {
      $this->checking_index = $this->get_address_index();
    }
    $this->result_stats = ['on_hold' => 0, 'processed' => 0];
  }

  /**
   * process configured amount of emails from the database with an index
   * @return array<string, int>
   * @throws \CRM_Core_Exception
   */
  public function process() {
    $this->get_email_addresses($this->checking_index + 1);
    $last_email_id = $this->checking_index;
    foreach ($this->email_lookup_values as $email_val) {
      $email_address = CRM_Mailingtools_Utils::toString($email_val['email'] ?? '');
      $email_id = CRM_Mailingtools_Utils::toInt($email_val['id'] ?? 0);
      if (CRM_Mailingtools_Utils::check_email_dns_blacklist($email_address, $email_id)) {
        $this->result_stats['on_hold'] += 1;
        // email was set on hold because of blacklist, no further validation needed
        continue;
      }

      // clear spaces and non-breaking spaces
      if (!$this->check_email(trim($email_address, "\xc2\xa0\x20"))) {
        if (CRM_Mailingtools_Utils::set_email_on_hold($email_id, $email_address, 'DNS Error')) {
          $this->result_stats['on_hold'] += 1;
        }
      }
      $last_email_id = $email_id;
      $this->result_stats['processed'] += 1;
    }
    $this->set_address_index($last_email_id);
    return $this->result_stats;
  }

  /**
   * @return void
   * @throws \CRM_Core_Exception
   */
  private function check_voku_email_checker_include() {
    if (!file_exists(__DIR__ . '/../../resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php')) {
      throw new CRM_Core_Exception("Didn't find resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php. "
        . 'Please install library via composer (see Readme) in the resources folder');
    }
  }

  /**
   * Get Email Addresses/IDs from CiviDB
   * @param int $index
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  private function get_email_addresses($index) {
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'return' => ['id', 'email'],
      'id' => ['>=' => $index],
      'options' => ['limit' => $this->verify_size],
    ]);
    if ((string) $result['is_error'] === '1') {
      throw new CRM_Core_Exception("Error Occured while looking up Emails. Parameters: Index->{$index}, "
        . "Verify_size->{$this->verify_size}, Error Message: {$result['error_message']}");
    }
    $this->email_lookup_values = $result['values'];
  }

  /**
   * Check Email via voku/email-check
   * @param string $email
   *
   * @return bool
   *
   *   TODO: Verify the files are available (composer)
   */
  private function check_email($email) {
    require_once __DIR__ . '/../../resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php';
    return \voku\helper\EmailCheck::isValid($email, FALSE, FALSE, FALSE, TRUE);
  }

  /**
   * get saved email index from Database
   * @return int
   */
  private function get_address_index() {
    $config = CRM_Mailingtools_Config::singleton();
    $settings = $config->getSettings();
    return isset($settings['email_verifier_index'])
      ? CRM_Mailingtools_Utils::toInt($settings['email_verifier_index'])
      : 1;
  }

  /**
   * save the index to mailingtools/settings
   * @param int $index
   * @return void
   */
  private function set_address_index($index) {
    CRM_Mailingtools_Utils::log("Setting last Email Index to {$index}");
    $config = CRM_Mailingtools_Config::singleton();
    $settings = $config->getSettings();
    $settings['email_verifier_index'] = $index;
    $config->setSettings($settings);
  }

}
