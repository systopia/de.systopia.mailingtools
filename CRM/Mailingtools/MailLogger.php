<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2024 SYSTOPIA                            |
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
 * Class for Logger
 *
 * Shamelessly stolen from APILogger
 * https://lab.civicrm.org/BjoernE/org.civicoop.logapirequests
 */
class CRM_Mailingtools_MailLogger {

  /**
   * @var resource|false */
  private $_logFile = NULL;

  /**
   * CRM_Mailingtools_MailLogger constructor.
   *
   *
   */
  public function __construct() {
    $file = CRM_Mailingtools_Utils::toString(CRM_Core_Config::singleton()->configAndLogDir) . 'mailing.log';
    $this->_logFile = fopen($file, 'a');
  }

  /**
   * @param array<int|string, mixed>|string $recipients
   * @param array<string, mixed> $header
   * @param mixed $body
   * @return void
   */
  public function logMailInfo($recipients, $header, $body) {
    if ($this->_logFile === FALSE) {
      return;
    }
    $log_file = $this->_logFile;
    $config = CRM_Mailingtools_Config::singleton();
    if ((bool) $config->getSetting('mailing_debugging_short')) {
      // check if this is a mailing. Check for X-CiviMail-Bounce
      // header. This should only be set for Mailings afaik
      if (isset($header['X-CiviMail-Bounce'])) {
        // do not log anything
        return;
      }
    }
    if ((bool) $config->getSetting('mailing_debugging_short')) {
      $short_info = [];
      $short_info['FROM'] = $header['From'];
      $short_info['TO'] = $header['To'];
      $short_info['SUBJECT'] = $header['Subject'];
      $this->addMessage((string) json_encode($short_info), 'SHORT');
    }
    if ((bool) $config->getSetting('mailing_debugging_header')) {
      $this->addMessage((string) json_encode($header), 'HEADER');
    }
    if ((bool) $config->getSetting('mailing_debugging_recipients')) {
      $this->addMessage((string) json_encode($recipients), 'RECIPIENTS');
    }
    if ((bool) $config->getSetting('mailing_debugging_body')) {
      $this->addMessage((string) json_encode($body), 'BODY');
    }
    // add empty line for better readablility if debugging is active
    if ((bool) $config->getSetting('mailing_debugging_short')
      || (bool) $config->getSetting('mailing_debugging_header')
      || (bool) $config->getSetting('mailing_debugging_recipients')
      || (bool) $config->getSetting('mailing_debugging_body')) {
      fwrite($log_file, "\n");
    }
  }

  /**
   * Method to log the message
   *
   * @param string $message
   * @param string|null $info
   * @return void
   */
  private function addMessage($message, $info) {
    if ($this->_logFile === FALSE) {
      return;
    }
    $log_file = $this->_logFile;
    fwrite($log_file, date('Y-m-d H:i:s'));
    if ($info !== NULL && $info !== '' && $info !== '0') {
      fwrite($log_file, ' [');
      fwrite($log_file, $info);
      fwrite($log_file, '] ');
    }
    else {
      fwrite($log_file, ' ');
    }
    fwrite($log_file, $message);
    fwrite($log_file, "\n");
  }

}
