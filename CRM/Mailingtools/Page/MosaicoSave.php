<?php
/*-------------------------------------------------------+
| SYSTOPIA REMOTE EVENT REGISTRATION                     |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres  (endres@systopia.de)                |
| Author: P. Batroff (batroff@systopia.de)               |
| Source: http://www.systopia.de/                        |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


class CRM_Mailingtools_Page_MosaicoSave {

  public static function buildPagehook(&$page) {

    // check if save message is enabled
    $config = CRM_Mailingtools_Config::singleton();
    $enabled = $config->getSetting('mosaico_save_message');
    if (!$enabled) {
      return;
    }

    $script = file_get_contents(__DIR__ . '/../../../js/save_mosaico_document.js');

    CRM_Core_Region::instance('page-footer')->add(array(
      'script' => $script,
    ));
  }
}