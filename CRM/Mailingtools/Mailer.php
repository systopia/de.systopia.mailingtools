<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

/**
 * Wrapper for CiviCRM Mailer
 */
class CRM_Mailingtools_Mailer {

  /**
   * this is the original, wrapped mailer
   */
  protected $mailer = NULL;

  /**
   * Check if the deployment of this mailer wrapper is needed
   */
  public static function isNeeded() {
    $config = CRM_Mailingtools_Config::singleton();
    return  ($config->getSetting('anonymous_open_enabled') && $config->getSetting('anonymous_open_url'))
         || ($config->getSetting('anonymous_link_enabled') && $config->getSetting('anonymous_link_url'))
         || CRM_Mailingtools_RegexToken::isEnabled()
         || !empty($config->getSetting('bcc_default_address'))
         || $config->getSetting('bcc_logged_in_user_mail');
  }

  /**
   * construct this mailer wrapping another one
   */
  public function __construct($mailer) {
    $this->mailer = $mailer;
  }

  /**
   * Send an email via the wrapped mailer,
   *  mending the URLs contained
   */
  function send($recipients, $headers, $body) {
    CRM_Mailingtools_AnonymousOpen::modifyEmailBody($body);
    CRM_Mailingtools_AnonymousURL::modifyEmailBody($body);

    $this->add_bcc($recipients, $headers, $body);

    // apply regex tokens to body _and_ headers
    if (CRM_Mailingtools_RegexToken::isEnabled()) {
      $context = [
          'recipients' => $recipients,
          'headers'    => $headers,
      ];
      $body = CRM_Mailingtools_RegexToken::tokenReplace($body, $context);
      $headers = CRM_Mailingtools_RegexToken::tokenReplace($headers, $context);
    }
    $this->mailer->send($recipients, $headers, $body);
  }


  public function add_bcc(&$recipients, &$headers, $body) {
    if (array_key_exists('X-CiviMail-Bounce', $headers)) {
      // nothing to do here, this is a mass-mailing. We use hook_civicrm_alterMailingRecipients instead
      return;
    }
    // bcc address nesds to be added to recipients
    $config = CRM_Mailingtools_Config::singleton();
    $bcc_address = $config->getSetting('bcc_default_address');
    $recipients[] = $bcc_address;
    if ($config->getSetting('bcc_logged_in_user_mail')) {
      // get logged in user id, get Email Address, and add it to recipients as well
      $session = CRM_Core_Session::singleton();
      $contact_id = $session->get('userID');
      $result = civicrm_api3('Email', 'get', [
        'contact_id' => $contact_id,
        'is_primary' => 1,
      ]);
      if ($result['count'] == 1) {
        foreach ($result['values'] as $values) {
          $recipients[] = $values['email'];
        }
      }
    }
  }
}
