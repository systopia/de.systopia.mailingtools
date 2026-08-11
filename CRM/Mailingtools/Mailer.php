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

declare(strict_types = 1);

/**
 * Wrapper for CiviCRM Mailer
 */
class CRM_Mailingtools_Mailer {

  /**
   * this is the original, wrapped mailer
   *
   * @var object|null
   */
  protected $mailer = NULL;

  /**
   * @var Mail Driver, see #26111
   */
  protected $driver = NULL;

  /**
   * @var array<string, mixed> Mail Params, currently not used
   */
  protected $params = [];

  /**
   * Check if the deployment of this mailer wrapper is needed
   */
  public static function isNeeded(): bool {
    $config = CRM_Mailingtools_Config::singleton();
    return ((bool) $config->getSetting('anonymous_open_enabled') && (bool) $config->getSetting('anonymous_open_url'))
         || ((bool) $config->getSetting('anonymous_link_enabled') && (bool) $config->getSetting('anonymous_link_url'))
         || CRM_Mailingtools_RegexToken::isEnabled()
         || (bool) $config->getSetting('mailing_debugging_short')
         || (bool) $config->getSetting('mailing_debugging_header')
         || (bool) $config->getSetting('mailing_debugging_recipients')
         || (bool) $config->getSetting('mailing_debugging_body');
  }

  /**
   * construct this mailer wrapping another one
   *
   * @param object $mailer
   * @param mixed $driver
   * @param array<string, mixed> $params
   * @return void
   */
  public function __construct($mailer, $driver, $params) {
    $this->mailer = $mailer;
    $this->driver = $driver;
    $this->params = $params;
  }

  /**
   * Send an email via the wrapped mailer,
   *  mending the URLs contained
   *
   * @param array<int|string, mixed>|string $recipients
   * @param array<string, mixed> $headers
   * @param string $body
   * @return void
   */
  public function send($recipients, $headers, $body) {
    CRM_Mailingtools_AnonymousOpen::modifyEmailBody($body);
    CRM_Mailingtools_AnonymousURL::modifyEmailBody($body);

    // apply regex tokens to body _and_ headers
    if (CRM_Mailingtools_RegexToken::isEnabled()) {
      $context = [
        'recipients' => $recipients,
        'headers'    => $headers,
      ];
      $body = CRM_Mailingtools_RegexToken::tokenReplace($body, $context);
      foreach ($headers as $name => $value) {
        $headers[$name] = CRM_Mailingtools_RegexToken::tokenReplace($value, $context);
      }
    }
    // Debug output
    $mail_logger = new CRM_Mailingtools_MailLogger();
    $mail_logger->logMailInfo($recipients, $headers, $body);

    $this->mailer->send($recipients, $headers, $body);
  }

  /**
   * @return Mail|null
   */
  public function getDriver() {
    return $this->driver;
  }

}
