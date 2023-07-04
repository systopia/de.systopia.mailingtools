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

  public static function verify_email($op, $objectName, $objectId,&$objectRef) {

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
      if(\voku\helper\EmailCheck::isValid($email, FALSE, FALSE, FALSE, TRUE)) {
        return;
      }
      self::set_email_on_hold($email_id, $email);
    } catch (Exception $e) {
      self::log('Failure to verify Email "{$email}"');
    }
  }

  /**
   * Set email on hold in CiviDB
   * @param $id
   * @param $email
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function set_email_on_hold($id, $email) {
    $result = civicrm_api3('Email', 'create', [
      'id' => $id,
      'on_hold' => 1,
      'hold_date' => date('d.m.Y H:i:s'),
    ]);
    if ($result['is_error'] == '1') {
      self::log("Error setting Email with ID {$id} on hold. Error Message: {$result['error_message']}");
      return false;
    }
    self::log("Set Email {$email} ({$id}) on hold");
    return true;
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