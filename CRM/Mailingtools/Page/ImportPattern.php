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

declare(strict_types = 1);

use CRM_Mailingtools_ExtensionUtil as E;

class CRM_Mailingtools_Page_ImportPattern extends CRM_Core_Page {

  /**
   * @return void
   * @throws CRM_Extension_Exception
   */
  public function run() {
    $param = CRM_Mailingtools_Utils::toString(CRM_Utils_Request::retrieve('name', 'String'));
    if ($param === '' || $param === '0') {
      throw new CRM_Extension_Exception('Please Provide a filename in the name parameter of the URL');
    }
    if (preg_match('/^[A-Za-z0-9_-]+$/', $param) !== 1) {
      throw new CRM_Extension_Exception('Invalid filename in the name parameter of the URL');
    }
    $files = glob(__DIR__ . "/../../../resources/*{$param}*.json");

    if ($files === FALSE || $files === []) {
      throw new CRM_Extension_Exception("Couldn't find file {$param}. Files must be placed in the resource directory.");
    }

    $counter = [];
    foreach ($files as $f) {
      $decoded = json_decode((string) file_get_contents($f), TRUE);
      $patterns = is_array($decoded) ? $decoded : [];
      $filename_parts = preg_split('/.+\//', $f);
      $filename = $filename_parts !== FALSE ? ($filename_parts[1] ?? '') : '';
      $counter[$filename] = [
        'ignored'   => 0,
        'inserted'  => 0,
      ];
      $this->parsePattern($patterns, $counter[$filename]);
    }

    $this->assign('name', $param);
    $this->assign('result_counter', $counter);

    parent::run();
  }

  /**
   * @param array<int|string, mixed> $patterns
   * @param array<string, int> $counter
   * @return void
   */
  private function parsePattern($patterns, &$counter) {
    foreach ($patterns as $bounce_value => $pattern) {
      if (!is_array($pattern)) {
        continue;
      }
      $bounce_type_id = CRM_Mailingtools_Utils::toInt($pattern[0] ?? 0);
      $bounce_pattern = CRM_Mailingtools_Utils::toString($pattern[1] ?? '');
      if ($this->isInDB($bounce_pattern)) {
        $counter['ignored'] += 1;
        continue;
      }

      CRM_Core_DAO::executeQuery(
        'INSERT INTO `civicrm_mailing_bounce_pattern` (`bounce_type_id`, `pattern`) VALUES(%1, %2);',
        [
          1 => [$bounce_type_id, 'Integer'],
          2 => [$bounce_pattern, 'String'],
        ]
      );
      $counter['inserted'] += 1;
    }
  }

  /**
   * @param string $pattern
   *
   * @return bool
   */
  private function isInDB($pattern): bool {
    return (int) CRM_Core_DAO::singleValueQuery(
      'SELECT COUNT(*) FROM `civicrm_mailing_bounce_pattern` WHERE `pattern`=%1;',
      [1 => [$pattern, 'String']]
    ) > 0;
  }

}
