<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2023 SYSTOPIA                            |
| Author: P.Batroff (batroff@systopia.de)                |
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

class CRM_Mailingtools_Utils
{

  public static $debug = True;


  /**
   * @param $op
   * @param $objectName
   * @param $objectId
   * @param $objectRef
   * @return void
   */
  public static function verify_email($op, $objectName, $objectId, &$objectRef) {

    // check if this feature is enabled
    $config = CRM_Mailingtools_Config::singleton();
    if( !$config->getSetting('enable_automatic_email_check'))  {
      return;
    }
    if (!file_exists(__DIR__ . '/../../resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php')) {
      self::log("Voku Email Checker not found. Please install via Composer");
      return;
    }
    try{
      require_once (__DIR__ . '/../../resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php');
      $email = $objectRef->email;
      $email_id = $objectRef->id;
      if (empty($email) || empty($email_id)) {
        return;
      }
      if(\voku\helper\EmailCheck::isValid($email, FALSE, FALSE, FALSE, TRUE)) {
        return;
      }
      self::set_email_on_hold($email_id, $email, "DNS Error");
    } catch (Exception $e) {
      self::log('Failure to verify Email "{$email}"');
    }
  }

  /**
   * @param $email
   * @param $email_id
   */
  public static function check_email_dns_blacklist($email, $email_id): bool {
    $config = CRM_Mailingtools_Config::singleton();
    $email_domain_blacklist = $config->getSetting('email_domain_blacklist');
    if(empty($email_domain_blacklist))  {
      return true;
    }
    $email_domains = explode(',', $email_domain_blacklist);

    try {
      $email_domain = substr($email, strpos($email, '@') + 1);
      foreach ($email_domains as $domain) {
        if ($domain == $email_domain) {
          self::set_email_on_hold($email_id, $email, "blacklisted");
          self::set_tag_for_blacklisted_email($email_id);
          return true;
        }
      }
    } catch (Exception $e) {
      self::log('Failure to blacklist Email "{$email}. Message: " . $e');
    }
    return false;
  }

  /**
   * Set email on hold in CiviDB
   * @param $id
   * @param $email
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function set_email_on_hold($id, $email, $reason = ""): bool {
    $result = civicrm_api3('Email', 'create', [
      'id' => $id,
      'on_hold' => 1,
      'hold_date' => date('d.m.Y H:i:s'),
    ]);
    if ($result['is_error'] == '1') {
      self::log("Error setting Email with ID {$id} on hold. Error Message: {$result['error_message']}");
      return false;
    }
    self::log("Set Email {$email} ({$id}) on hold ({$reason})");
    return true;
  }

  public static function set_tag_for_blacklisted_email($email_id) {
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'return' => ["contact_id"],
      'id' => $email_id,
    ]);
    $contact_id = 0;
    foreach ($result['values'] as $contact) {
      $contact_id = $contact['contact_id'];
    }
    // check if tag is available
    $result = civicrm_api3('Tag', 'get', [
      'sequential' => 1,
      'name' => "blacklisted_email_domain",
    ]);
    if ($result['count'] == 0) {
      // create tag
      $result = civicrm_api3('Tag', 'create', [
        'name' => "blacklisted_email_domain",
      ]);
    }

//    create tag for contact
    $result = civicrm_api3('EntityTag', 'create', [
      'tag_id' => "blacklisted_email_domain",
      'contact_id' => $contact_id,
      'entity_table' => "civicrm_contact",
    ]);
  }


  /**
   * @param $message
   * @param $loglevel
   * @return void
   *
   */
  public static function log($message, $loglevel = "DEBUG")
  {
    if (self::$debug) {
      Civi::log()->log($loglevel, "[de.systopia.mailingtools] " . $message);
    }
  }

}