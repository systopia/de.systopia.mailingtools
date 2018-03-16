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

class CRM_Mailingtools_Page_ImportPattern extends CRM_Core_Page {

  /**
   * @return string|void
   * @throws CRM_Extension_Exception
   */
  public function run() {
    $param = CRM_Utils_Request::retrieve("name");
    if (empty($param)) {
      throw new CRM_Extension_Exception("Please Provide a filename in the name parameter of the URL");
    }

    $files = glob(__DIR__ . "/resources/*{$param}*.json");

    if (empty($files)) {
      throw new CRM_Extension_Exception("Couldn't find file {$param}. Files must be placed in the resource directory.");
    }

    foreach ($files as $f) {
      $counter = array();
      $pattern = json_decode($f);
      $counter[$f] = array();
      $this->parsePattern($pattern, $counter[$f]);
    }



    parent::run();
  }

  /**
   * @param $pattern
   *
   */
  private function parsePattern($patterns, &$counter) {
    foreach ($patterns as $bounce_value => $pattern) {
      if ($this->isInDB($pattern)) {
        $counter['ignored'] += 1;
      }
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_mailing_bounce_pattern (`bounce_type_id`, `pattern`) VALUES(%1, '%2');",
        array(1 => array($bounce_value, "Integer"),
              2 => array($pattern, "String"),
          )
        );
      $counter['inserted'] += 1;
    }
  }

  /**
   * @param $pattern
   *
   * @return bool
   */
  private function isInDB($pattern) {

    return CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*) FROM `civicrm_mailing_bounce_pattern` WHERE `pattern`='%1'",
      array(1 => array($pattern, "String"))
    );
  }

}
