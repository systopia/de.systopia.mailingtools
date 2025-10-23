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
 * Mailingtools.Emailsync API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mailingtools_Emailsync_spec(&$spec) {
  $spec['verify_size']['api.default']        = 1000;
  $spec['verify_size']['api.description']    = "The number of emails verified.";
  $spec['checking_index']['api.default']     = 0;
  $spec['checking_index']['api.description'] = "Uses an email identifier to start checking from there. Id from civicrm_email table is used.";
  $spec['debug']['api.default']              = FALSE;
  $spec['debug']['api.description']          = "Writes errors of each lookup to CiviCRM log.";
}

/**
 * Mailingtools.Emailsync API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws CRM_Core_Exception
 */
function civicrm_api3_mailingtools_Emailsync($params) {
  if (version_compare(PHP_VERSION, '7.0.0') == 0) {
    throw new CRM_Core_Exception('PHP Version is not compatible with this API command. At least PHP7.0 is needed');
  }

  $runner = new CRM_Mailingtools_EmailVerifier($params['verify_size'], $params['checking_index'], $params['debug']);
  $results = $runner->process();
  return civicrm_api3_create_success("Number of Emails Processed: {$results['processed']}, Number of Emails deactivated: {$results['on_hold']}");


}
