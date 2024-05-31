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
  public static function isNeeded(): bool {
    $config = CRM_Mailingtools_Config::singleton();
    return  ($config->getSetting('anonymous_open_enabled') && $config->getSetting('anonymous_open_url'))
         || ($config->getSetting('anonymous_link_enabled') && $config->getSetting('anonymous_link_url'))
         || CRM_Mailingtools_RegexToken::isEnabled()
         || $config->getSetting('mailing_debugging_short')
         || $config->getSetting('mailing_debugging_header')
         || $config->getSetting('mailing_debugging_recipients')
         || $config->getSetting('mailing_debugging_body')
      ;
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

    // apply regex tokens to body _and_ headers
    if (CRM_Mailingtools_RegexToken::isEnabled()) {
      $context = [
          'recipients' => $recipients,
          'headers'    => $headers,
      ];
      $body = CRM_Mailingtools_RegexToken::tokenReplace($body, $context);
      $headers = CRM_Mailingtools_RegexToken::tokenReplace($headers, $context);
    }
    // Debug output
    $mail_logger = new CRM_Mailingtools_MailLogger();
    $mail_logger->logMailInfo($recipients, $headers, $body);

    $this->mailer->send($recipients, $headers, $body);
  }
}
