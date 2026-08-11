<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2018-2019 SYSTOPIA                       |
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

use CRM_Mailingtools_ExtensionUtil as E;

/**
 * API: Mailingtools.Anonurl
 *
 * Processes an anonymous click event on a trackable URL
 *  based only on the mailing ID and the link ID
 *
 * @param array<string, mixed> $params containing 'mid'
 * @return array<string, mixed> result;
 * @throws CRM_Core_Exception
 */
function civicrm_api3_mailingtools_anonurl($params) {
  try {
    $url = CRM_Mailingtools_AnonymousURL::processAnonymousClickEvent(CRM_Mailingtools_Utils::toInt($params['u']));
    if ($url !== NULL) {
      $link_id = CRM_Mailingtools_Utils::toInt($params['u']);
      return civicrm_api3_create_success([$link_id => $url]);
    }
    else {
      // @phpstan-ignore argument.type
      return civicrm_api3_create_success('Anonymous click tracking disabled.');
    }
  }
  catch (Exception $ex) {
    throw new CRM_Core_Exception($ex->getMessage(), $ex->getCode(), [], $ex);
  }
}

/**
 * API Specs: Mailingtools.Anonopen
 *
 * @param array<string, mixed> $spec
 * @return void
 */
function _civicrm_api3_mailingtools_anonurl_spec(&$spec) {
  $spec['u'] = [
    'name'         => 'u',
    'api.required' => 1,
    'type'         => CRM_Utils_Type::T_INT,
    'title'        => 'URL ID',
    'description'  => 'URL ID for which the click event should be recorded',
  ];
}
