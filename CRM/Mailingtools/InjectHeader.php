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

/**
 * Class CRM_Mailingtools_InjectHeader
 *
 * Injects a configured header to the mail
 */
class CRM_Mailingtools_InjectHeader {

  /**
   *
   * @param $params
   * @param $context
   */
  static function inject_header(&$params, $context) {
    $config = CRM_Mailingtools_Config::singleton();
    $settings = $config->getSettings();
    if (!empty($settings['extra_mail_header_key']) && !empty($settings['extra_mail_header_value'])) {
      $params['headers'][$settings['extra_mail_header_key']] = $settings['extra_mail_header_value'];
    }
  }

}