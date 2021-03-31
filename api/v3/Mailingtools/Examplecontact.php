<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2021 SYSTOPIA                            |
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
function _civicrm_api3_mailingtools_examplecontact_spec(&$spec) {
  $spec['sample_size']['api.required'] = 1;
  $spec['offset']['api.description'] = "Offset where to start. Email addresses will be generated mailingtools_example_Offset@systopia.de";
  $spec['debug']['api.description'] = "Logs Created Contacts to Civi Log";

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
function civicrm_api3_mailingtools_examplecontact($params) {

  try {
    $contacts_created = 0;
    $offset = 0;
    if (!empty($params['offset'])) {
      $offset = $params['offset'];
    }

    $count = $params['sample_size'];

    for ($i = 0; $i < $count; $i++) {
      $j = $offset + $i;
      civicrm_api3('contact', 'create', [
        'contact_type' => 'Individual',
        'first_name' => "First_$j",
        'last_name' => "Last_$j",
        'email' => "mailingtools_example_{$j}@systopia.de",
      ]);
      if (!empty($params['debug'])) {
        Civi::log("Created Contact with email mailingtools_example_{$j}@systopia.de");
      }
      $contacts_created++;
    }
    return civicrm_api3_create_success("Number of Contacts Created: {$contacts_created}");
  } catch (API_Exception $e) {
    return civicrm_api3_create_error( "Error creating Example Contacts. {$contacts_created} Contacts have been created. Message: " . $e->getMessage());
  }
}
