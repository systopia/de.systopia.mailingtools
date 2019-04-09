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
    $this->read_mailstore_retention();
    // get smtp Config
    $this->read_bounce_mail_config();
  }

  /**
   * get mailstore retention
   * can be configured on the settings page
   */
  private function read_mailstore_retention() {
    $config = CRM_Mailingtools_Config::singleton();
    $settings = $config->getSettings();

    if ($this->verify_settings($settings)) {
      return;
    }
    $this->mailStore_retention['INBOX.CiviMail.ignored'] = $settings['ignored_retention_value'];
    $this->mailStore_retention['INBOX.CiviMail.processed'] = $settings['processed_retention_value'];
    $this->retention_configured = TRUE;
  }

  /**
   * get bounce mail config from CiviCRM
   *
   * TODO: Maybe add additional config to settings page if this doesn't work properly
   */
  private function read_bounce_mail_config() {
    $dao = new CRM_Core_DAO_MailSettings();
    $dao->domain_id = CRM_Core_Config::domainID();
    $dao->is_default = TRUE;
    $dao->find();
    $dao->fetch();

    $this->imap_login['hostname'] = $this->create_mailbox_hostname($dao);
    $this->imap_login['username'] = $dao->username;
    $this->imap_login['password'] = $dao->password;
  }

  /**
   * Gets port either from configured serverURL, from the DAO object or prepares default values
   * @param $dao
   * @return string
   */
  private function create_mailbox_hostname($dao) {

    $suffix = $this->create_imap_suffix($dao);

    $port_from_serverUrl = explode(":", $dao->server);
    if (isset($port_from_serverUrl[1])) {
      return "{" . $dao->server . $suffix . "}";
    }
    $port = $this->get_server_port($dao);
    return "{" . $dao->server . ":" . $port .  $suffix . "}";
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
      if ($imap) {
        $date = $this->create_retention_timestamp($folder);
        $emails_delete_ignored = imap_search($imap, 'BEFORE "' . $date . '"');
        if (!empty($emails_delete_ignored)) {
          $this->delete_imap_emails($emails_delete_ignored, $imap, $folder);
        }
      } else {
        error_log("Error Connecting to " . $this->imap_login['hostname'] . $folder);
        $this->errors[$folder] = imap_last_error();
      }

    }
    if (empty($this->errors)) {
        return json_encode($this->results);
    }
    return (json_encode($this->errors) . json_encode($this->results));
  }

  /**
   * Check if retentino is configured. If not, we don't delete anything and return false here
   * @param $settings
   * @return bool
   */
  private function verify_settings($settings)
  {
    return !isset($settings['processed_retention_value']) || $settings['processed_retention_value'] == 0
      || !isset($settings['ignored_retention_value']) || $settings['ignored_retention_value'] == 0;
  }

  /**
   * create the IMAP server port, depending on bounce mailbox config
   * @param $dao
   * @return int
   */
  private function get_server_port($dao)
  {
    if ($dao->port) {
      return $dao->port;
    }
    if ($dao->ssl) {
      $port = 993;
    } else {
      $port = 143;
    }
    return $port;
  }

  /**
   * generates a suffix depending on bounce mailbox config
   * @param $dao
   * @return string
   */
  private function create_imap_suffix($dao)
  {
    if ($dao->ssl) {
      return "/imap/ssl";
    } else {
      return "/imap/novalidate-cert";
    }
  }

  /**
   * generate a retention timestamp
   * @param $folder
   * @return false|string
   */
  private function create_retention_timestamp($folder)
  {
    $time = strtotime("now - {$this->mailStore_retention[$folder]} days");
    return date("j-F-Y", $time);
  }

  /**
   * deletes the emails indexed by the search function on the given imap stream
   * @param $emails_delete_ignored
   * @param $imap
   * @param $folder
   */
  private function delete_imap_emails($emails_delete_ignored, $imap, $folder)
  {
    foreach ($emails_delete_ignored as $email_index) {
      imap_delete($imap, $email_index);
      $this->results[$folder] += 1;
    }
    imap_expunge($imap);
  }


}