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
  private $mail_folders = array('CiviMail.ignored', 'CiviMail.processed');


  public function __construct() {
    // get Mailstore Config
    $this->get_mailstore_retention();
    // get smtp Config
    $this->get_bounce_mail_config();
  }

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

  private function get_bounce_mail_config() {
    $dao = new CRM_Core_DAO_MailSettings();
    $dao->domain_id = CRM_Core_Config::domainID();
    $dao->is_default = TRUE;
    $dao->find();
    $dao->fetch();

    $port = $dao->port;
    if (!$port) {
      // set default port here
      $port = "143";
    }

    // TODO: Apparently the port is empty - either learn how to configure port in mail account, or put it in statically
    if ($dao->is_ssl) {
      $host = "{" . $dao->server . ":" . $port . "/imap/ssl}";
    } else {
      // FIXME: is novalidate-cert necessary? Might be good to have with lower PHP Versions and/or self signed certs
      $host = "{" . $dao->server . ":" . $port . "/imap/novalidate-cert}";
    }
    $this->imap_login['hostname'] = $host;
    $this->imap_login['username'] = $dao->username;
    $this->imap_login['password'] = $dao->password;
  }

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
      // IGNORED Folder
      try {
        $inbox = imap_open($this->imap_login['hostname'] . $folder, $this->imap_login['username'], $this->imap_login['password']);
        $time = strtotime("now - {$this->mailStore_retention['ignored_retention']} days");
        $date = date("j-F-Y", $time);
        $emails_delete_ignored = imap_search($inbox, 'BEFORE "' . $date . '"');
        // TODO: for debug reasons:
        foreach ($emails_delete_ignored as $email_index) {
          $header = imap_fetchheader($inbox, $email_index);
          error_log("DEBUG HEADER (IGNORED): " . json_encode($header));
        }
      } catch (Exception $e) {
        // something went wrong
        throw new API_Exception("Exception: " . $e->getMessage() . "; Couldn't connect to IMAP Mailbox. Error: " . imap_last_error());
      }
    }

    // TODO: Execute for CiviMail/process folder as well!
  }


}