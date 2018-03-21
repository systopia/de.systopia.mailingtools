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
 * Class CRM_Mailingtools_CheckMailstore
 */
class CRM_Mailingtools_CheckMailstore {

  private $mailStore_retention = array();
  private $retention_configured = FALSE;
  private $imap_login = array();
  private $mail_folders = array('INBOX.CiviMail.ignored', 'INBOX.CiviMail.processed');
  private $errors = array();
  private $results = array();


  /**
   * CRM_Mailingtools_CheckMailstore constructor.
   */
  public function __construct() {
    // get Mailstore Config
    $this->get_mailstore_retention();
    // get smtp Config
    $this->get_bounce_mail_config();
  }

  /**
   * get mailstore retention
   * can be configured on the settings page
   */
  private function get_mailstore_retention() {
    $config = CRM_Mailingtools_Config::singleton();
    $settings = $config->getSettings();

    if (!isset($settings['processed_retention_value']) || $settings['processed_retention_value'] == 0
      || !isset($settings['ignored_retention_value']) || $settings['ignored_retention_value'] == 0) {
      // we are done here. Nothing left to do.
      return;
    }
    $this->mailStore_retention['ignored_retention'] = $settings['ignored_retention_value'];
    $this->mailStore_retention['processed_retention'] = $settings['processed_retention_value'];
    $this->retention_configured = TRUE;
  }

  /**
   * get bounce mail config from CiviCRM
   *
   * TODO: Maybe add additional config to settings page if this doesn't work properly
   */
  private function get_bounce_mail_config() {
    $dao = new CRM_Core_DAO_MailSettings();
    $dao->domain_id = CRM_Core_Config::domainID();
    $dao->is_default = TRUE;
    $dao->find();
    $dao->fetch();

    $this->imap_login['hostname'] = $this->create_mailbox_hostname($dao->server, $dao->is_ssl, $dao->port);
    $this->imap_login['username'] = $dao->username;
    $this->imap_login['password'] = $dao->password;
  }

  /**
   * Gets port either from configured serverURL, from the DAO object or prepares default values
   * @param $serverUrl
   * @param $is_ssl
   * @param $dao_port
   * @return string
   */
  private function create_mailbox_hostname($serverUrl, $is_ssl, $dao_port) {

    // imap connection parameters
    if ($is_ssl) {
      $suffix =  "/imap/ssl";
    } else {
      $suffix ="/imap/novalidate-cert";
    }

    $port_from_serverUrl = explode(":", $serverUrl);
    if (isset($port_from_serverUrl[1])) {
      return "{" . $serverUrl . $suffix . "}";
    }
    // $dao_port seems to be always empty
    if ($dao_port) {
      return "{" . $serverUrl . ":" . $dao_port .  $suffix . "}";
    }
    // URL with default ports, SSL and TLS
    if ($is_ssl) {
      return "{" . $serverUrl . ":" . "993" .  $suffix . "}";
    }
    return "{" . $serverUrl . ":" . "143" .  $suffix . "}";
  }

  /**
   * Main function. Checks mailstore, deletes mails older than retention in
   * configured folders (default is CiviMail.(ignored|processed))
   *
   * @return string|void
   */
  public function check_mailstore() {

    // TODO:
    // - log into imap Folder, get mailbox folder,
    // - check for old mails
    // - delete mails (FixMe: First only debug Mails --> output)
    if (!$this->retention_configured) {
      // nothing to do here.
      return;
    }

    foreach ($this->mail_folders as $folder) {
      $this->results[$folder] = 0;
      $imap = imap_open($this->imap_login['hostname'] . $folder, $this->imap_login['username'], $this->imap_login['password']);
      if (!$imap) {
        error_log("Error Connecting to " . $this->imap_login['hostname'] . $folder);
        $this->errors[$folder] = imap_last_error();
        continue;
      }
      $time = strtotime("now - {$this->mailStore_retention['ignored_retention']} days");
      $date = date("j-F-Y", $time);
      $emails_delete_ignored = imap_search($imap, 'BEFORE "' . $date . '"');
      // TODO: for debug reasons:
      foreach ($emails_delete_ignored as $email_index) {
        $header = imap_fetchheader($imap, $email_index);
// DEBUG ONLY
//        error_log("DEBUG HEADER ({$folder}): " . json_encode($header));
        // after debug phase:
        // imap_delete($imap, $email_index);
        // imap_expunge($imap);
        $this->results[$folder] += 1;
      }
    }

    if (empty($this->errors)) {
      return json_encode($this->results);
    }
    return json_encode($this->errors);
  }


}