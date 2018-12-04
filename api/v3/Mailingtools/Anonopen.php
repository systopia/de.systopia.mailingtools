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

use CRM_Mailingtools_ExtensionUtil as E;


/**
 * API: Mailingtools.Anonopen
 *
 * Processes an anonymous open event,
 *  based only on the mailing ID
 *
 * @param array $params containing 'mid'
 * @return array result
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_mailingtools_anonopen($params) {
  try {
    $result = CRM_Mailingtools_AnonymousOpen::processAnonymousOpenEvent($params['mid']);
    if ($result) {
      return civicrm_api3_create_success("Anonymous open event recorded.");
    } else {
      return civicrm_api3_create_success("Anonymous open tracking disabled.");
    }
  } catch (Exception $ex) {
    throw new CiviCRM_API3_Exception($ex->getMessage());
  }
}

/**
 * API Specs: Mailingtools.Anonopen
 */
function _civicrm_api3_mailingtools_anonopen_spec(&$spec) {
  $spec['mid'] = array(
      'name'         => 'mid',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_INT,
      'title'        => 'Mailing ID',
      'description'  => 'Mailing ID for which an open event should be recorded',
  );
}
