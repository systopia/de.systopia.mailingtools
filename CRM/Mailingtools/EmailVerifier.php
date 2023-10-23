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


use CRM_Mailingtools_ExtensionUtil as E;

/**
 * Class CRM_Mailingtools_EmailVerifier
 */
class CRM_Mailingtools_EmailVerifier {

  private $verify_size;
  private $checking_index;
  private $debug;
  private $email_lookup_values;
  private $result_stats;

  /**
   * CRM_Mailingtools_EmailVerifier constructor.
   *
   * @param $verify_size
   * @param $checking_index
   * @param $debug
   *
   * @throws \API_Exception
   */
  public function __construct($verify_size, $checking_index, $debug) {
    $this->check_voku_email_checker_include();
    $this->verify_size = $verify_size;
    $this->debug = $debug;
    if (isset($checking_index)) {
      $this->checking_index = $checking_index;
    } else {
      $this->checking_index = $this->get_address_index();
    }
    $this->result_stats = ['on_hold' => 0, 'processed' =>0];
  }

  /**
   * process configured amount of emails from the database with an index
   * @throws \API_Exception
   */
  public function process() {
    $this->get_email_addresses($this->checking_index +1);
    $last_email_id = $this->checking_index;
    foreach ($this->email_lookup_values as $email_val) {
      if (CRM_Mailingtools_Utils::check_email_dns_blacklist($email_val['email'],$email_val['id'])) {
        $this->result_stats['on_hold'] += 1;
        continue; // email was set on hold because of blacklist, no further validation needed
      }

      // clear spaces and non-breaking spaces
      if (!$this->check_email(trim($email_val['email'],"\xc2\xa0\x20"))) {
        if (CRM_Mailingtools_Utils::set_email_on_hold($email_val['id'], $email_val['email'], "DNS Error")) {
          $this->result_stats['on_hold'] += 1;
        }
      }
      $last_email_id = $email_val['id'];
      $this->result_stats['processed'] += 1;
    }
    $this->set_address_index($last_email_id);
    return $this->result_stats;
  }

  /**
   * @throws \API_Exception
   */
  private function check_voku_email_checker_include() {
    if (!file_exists(__DIR__ . '/../../resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php')) {
      throw new API_Exception("Didn't find resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php. Please install library via composer (see Readme) in the resources folder");
    }
  }

  /**
   * Get Email Addresses/IDs from CiviDB
   * @param $index
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   */
  private function get_email_addresses($index) {
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'return' => ["id", "email"],
      'id' => ['>=' => $index],
      'options' => ['limit' => $this->verify_size],
    ]);
    if ($result['is_error'] == '1') {
      throw new API_Exception("Error Occured while looking up Emails. Parameters: Index->{$index}, Verify_size->{$this->verify_size}, Error Message: {$result['error_message']}");
    }
    $this->email_lookup_values = $result['values'];
  }

  /**
   * Check Email via voku/email-check
   * @param $email
   *
   * @return bool
   *
   * TODO: Verify the files are available (composer)
   */
  private function check_email($email) {
    require_once (__DIR__ . '/../../resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php');
    return \voku\helper\EmailCheck::isValid($email, FALSE, FALSE, FALSE, TRUE);
  }

  /**
   * Set email on hold in CiviDB
   * @param $id
   * @param $email
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function set_email_on_hold($id, $email) {
    $result = civicrm_api3('Email', 'create', [
      'id' => $id,
      'on_hold' => 1,
      'hold_date' => date('d.m.Y H:i:s'),
    ]);
    if ($result['is_error'] == '1') {
      CRM_Mailingtools_Utils::log("Error setting Email with ID {$id} on hold. Error Message: {$result['error_message']}");
      return;
    }
    CRM_Mailingtools_Utils::log("Set Email {$email} ({$id}) on hold");
    $this->result_stats['on_hold'] += 1;
  }

  /**
   * get saved email index from Database
   * @return int
   */
  private function get_address_index() {
    $config = CRM_Mailingtools_Config::singleton();
    $settings = $config->getSettings();
    return $settings['email_verifier_index'] ?? 1;
  }

  /**
   * save the index to mailingtools/settings
   * @param $index
   */
  private function set_address_index($index) {
    CRM_Mailingtools_Utils::log("Setting last Email Index to {$index}");
    $config = CRM_Mailingtools_Config::singleton();
    $settings = $config->getSettings();
    $settings['email_verifier_index'] = $index;
    $config->setSettings($settings);
  }

}