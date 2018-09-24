<?php
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
  $spec['verify_size']['api.default'] = 1000;
  $spec['verify_size']['api.description'] = "The number of emails verified.";
  $spec['checking_index']['api.default'] = NULL;
  $spec['checking_index']['api.description'] = "Uses an email identifier to start checking from there. Id from civicrm_email table is used.";
  $spec['debug']['api.default'] = FALSE;
  $spec['debug']['api.description'] = "Writes errors of each lookup to CiviCRM log.";
}

/**
 * Mailingtools.Emailsync API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_mailingtools_Emailsync($params) {
  if (version_compare(PHP_VERSION, '7.0.0') == 0) {
    throw new API_Exception('PHP Version is not compatible with this API command. At least PHP7.0 is needed');
  }

  $runner = new CRM_Mailingtools_EmailVerifier($params['verify_size'], $params['checking_index'], $params['debug']);
  $runner->process();
  return civicrm_api3_create_success(array(), $params, 'NewEntity', 'NewAction');


}
