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

use CRM_Mailingtools_ExtensionUtil as E;


/**
 * Class for Logger
 *
 * Shamelessly stolen from APILogger
 * https://lab.civicrm.org/BjoernE/org.civicoop.logapirequests
 */
class CRM_Mailingtools_MailLogger {

  private $_logFile = null;

  /**
   * CRM_Mailingtools_MailLogger constructor.
   *
   *
   */
  function __construct() {
    $file = CRM_Core_Config::singleton()->configAndLogDir . 'mailing.log';
    $this->_logFile = fopen($file, 'a');
  }

  public function logMailInfo($recipients, $header, $body) {
    $config = CRM_Mailingtools_Config::singleton();
    if ($config->getSetting('mailing_debugging_short')) {
      // check if this is a mailing. Check for X-CiviMail-Bounce
      // header. This should only be set for Mailings afaik
      if (isset($header['X-CiviMail-Bounce'])) {
        // do not log anything
        return;
      }
    }
    if ($config->getSetting('mailing_debugging_short')) {
      $short_info = [];
      $short_info['FROM'] = $header['From'];
      $short_info['TO'] = $header['To'];
      $short_info['SUBJECT'] = $header['Subject'];
      $this->addMessage(json_encode($short_info), "SHORT");
    }
    if ($config->getSetting('mailing_debugging_header')) {
      $this->addMessage(json_encode($header), "HEADER");
    }
    if ($config->getSetting('mailing_debugging_recipients')) {
      $this->addMessage(json_encode($recipients), "RECIPIENTS");
    }
    if ($config->getSetting('mailing_debugging_body')) {
      $this->addMessage(json_encode($body), "BODY");
    }
    // add empty line for better readablility if debugging is active
    if ($config->getSetting('mailing_debugging_short')
      || $config->getSetting('mailing_debugging_header')
      || $config->getSetting('mailing_debugging_recipients')
      || $config->getSetting('mailing_debugging_body')) {
      fputs($this->_logFile, "\n");
    }
  }


  /**
   * Method to log the message
   *
   * @param $message
   */
  private function addMessage($message, $info) {
    fputs($this->_logFile, date('Y-m-d H:i:s'));
    if (!empty($info)) {
      fputs($this->_logFile, ' [');
      fputs($this->_logFile, $info);
      fputs($this->_logFile, '] ');
    } else {
      fputs($this->_logFile, ' ');
    }
    fputs($this->_logFile, $message);
    fputs($this->_logFile, "\n");
  }
}
