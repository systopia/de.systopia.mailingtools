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

declare(strict_types = 1);

use CRM_Mailingtools_ExtensionUtil as E;

class CRM_Mailingtools_Utils {

  /**
   * @var bool */
  public static $debug = TRUE;

  /**
   * Safely cast a possibly-mixed value (e.g. a setting or array value of
   * unknown origin) to int. Non-scalar values (arrays, objects, null) become 0.
   *
   * @param mixed $value
   * @return int
   */
  public static function toInt($value): int {
    return is_scalar($value) ? (int) $value : 0;
  }

  /**
   * Safely cast a possibly-mixed value (e.g. a setting or array value of
   * unknown origin) to string. Non-scalar values (arrays, objects, null)
   * become ''.
   *
   * @param mixed $value
   * @return string
   */
  public static function toString($value): string {
    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * @param string $op
   * @param string $objectName
   * @param int $objectId
   * @param object $objectRef
   * @return void
   */
  public static function verify_email($op, $objectName, $objectId, &$objectRef) {

    // check if this feature is enabled
    $config = CRM_Mailingtools_Config::singleton();
    if (!(bool) $config->getSetting('enable_automatic_email_check')) {
      return;
    }
    if (!file_exists(__DIR__ . '/../../resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php')) {
      self::log('Voku Email Checker not found. Please install via Composer');
      return;
    }
    try {
      require_once __DIR__ . '/../../resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php';
      // @phpstan-ignore property.notFound
      $email = $objectRef->email;
      // @phpstan-ignore property.notFound
      $email_id = $objectRef->id;
      if ($email === NULL || $email === '' || $email === '0' || (int) ($email_id ?? 0) === 0) {
        return;
      }
      if (\voku\helper\EmailCheck::isValid($email, FALSE, FALSE, FALSE, TRUE)) {
        return;
      }
      self::set_email_on_hold($email_id, $email, 'DNS Error');
    }
    catch (Exception $e) {
      // @ignoreException
      self::log('Failure to verify Email: ' . $e->getMessage());
    }
  }

  /**
   * @param mixed $email
   * @param mixed $email_id
   */
  public static function check_email_dns_blacklist($email, $email_id): bool {
    if (!is_string($email) || $email === '') {
      return FALSE;
    }
    $email_id = self::toInt($email_id);
    if ($email_id === 0) {
      return FALSE;
    }
    $config = CRM_Mailingtools_Config::singleton();
    $email_domain_blacklist = $config->getSetting('email_domain_blacklist');
    if ($email_domain_blacklist === NULL || $email_domain_blacklist === '' || $email_domain_blacklist === '0') {
      return FALSE;
    }
    $email_domains = explode(',', CRM_Mailingtools_Utils::toString($email_domain_blacklist));

    try {
      $at_position = strpos($email, '@');
      if ($at_position === FALSE) {
        return FALSE;
      }
      $email_domain = substr($email, $at_position + 1);
      foreach ($email_domains as $domain) {
        if ($domain === $email_domain) {
          self::set_email_on_hold($email_id, $email, 'blacklisted');
          self::set_tag_for_blacklisted_email($email_id);
          return TRUE;
        }
      }
    }
    catch (Exception $e) {
      // @ignoreException
      self::log("Failure to blacklist Email \"{$email}\": " . $e->getMessage());
    }
    return FALSE;
  }

  /**
   * Set email on hold in CiviDB
   * @param int $id
   * @param string $email
   * @param string $reason
   *
   * @throws \CRM_Core_Exception
   */
  public static function set_email_on_hold($id, $email, $reason = ''): bool {
    $result = civicrm_api3('Email', 'create', [
      'id' => $id,
      'on_hold' => 1,
      'hold_date' => date('d.m.Y H:i:s'),
    ]);
    if ((string) $result['is_error'] === '1') {
      self::log("Error setting Email with ID {$id} on hold. Error Message: {$result['error_message']}");
      return FALSE;
    }
    self::log("Set Email {$email} ({$id}) on hold ({$reason})");
    return TRUE;
  }

  /**
   * @param int $email_id
   * @return void
   */
  public static function set_tag_for_blacklisted_email($email_id) {
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'return' => ['contact_id'],
      'id' => $email_id,
    ]);
    $contact_id = 0;
    foreach ($result['values'] as $contact) {
      $contact_id = $contact['contact_id'];
    }
    // check if tag is available
    $result = civicrm_api3('Tag', 'get', [
      'sequential' => 1,
      'name' => 'blacklisted_email_domain',
    ]);
    if ($result['count'] === 0) {
      // create tag
      $result = civicrm_api3('Tag', 'create', [
        'name' => 'blacklisted_email_domain',
      ]);
    }

    civicrm_api3('EntityTag', 'create', [
      'tag_id' => 'blacklisted_email_domain',
      'contact_id' => $contact_id,
      'entity_table' => 'civicrm_contact',
    ]);
  }

  /**
   * @param string $message
   * @param string $loglevel
   * @return void
   *
   */
  public static function log($message, $loglevel = 'debug') {
    if (self::$debug) {
      Civi::log()->log($loglevel, '[de.systopia.mailingtools] ' . $message);
    }
  }

}
