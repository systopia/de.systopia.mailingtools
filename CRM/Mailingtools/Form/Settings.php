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
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Mailingtools_Form_Settings extends CRM_Core_Form {
  public function buildQuickForm() {

    $config = CRM_Mailingtools_Config::singleton();
    $current_values = $config->getSettings();

    // add form elements
    $this->add(
      'text',
      'extra_mail_header_key',
      E::ts('Extra Mail Header Key'),
      array("class" => "huge"),
      FALSE
    );

    $this->add(
      'text',
      'extra_mail_header_value',
      E::ts('Extra Mail Header Entry'),
      array("class" => "huge"),
      FALSE
    );

    $this->add(
      'text',
      'processed_retention_value',
      E::ts('CiviMail Processed Retention'),
      array("class" => "huge"),
      FALSE
    );

    $this->add(
      'text',
      'ignored_retention_value',
      E::ts('CiviMail Ignored Retention'),
      array("class" => "huge"),
      FALSE
    );

    // ANONYMOUS open mailing stuff
    $this->add(
        'checkbox',
        'anonymous_open_enabled',
        E::ts('Enabled')
    );

    $this->add(
        'text',
        'anonymous_open_url',
        E::ts('URL Endpoint'),
        ['class' => 'huge'],
        FALSE
    );

    $this->add(
        'select',
        'anonymous_open_permission',
        E::ts('API Permission'),
        CRM_Core_Permission::basicPermissions(TRUE),
        FALSE
    );

    $this->add(
        'text',
        'anonymous_open_contact_id',
        E::ts('Anonymous Contact ID'),
        ["style" => "width: 50px;"],
        FALSE
    );

    $this->renderContact($current_values, 'open');

    // ANONYMOUS link tracking stuff
    $this->add(
        'checkbox',
        'anonymous_link_enabled',
        E::ts('Enabled')
    );

    $this->add(
        'text',
        'anonymous_link_url',
        E::ts('URL Endpoint'),
        ['class' => 'huge'],
        FALSE
    );

    $this->add(
        'select',
        'anonymous_link_permission',
        E::ts('API Permission'),
        CRM_Core_Permission::basicPermissions(TRUE),
        FALSE
    );

    $this->add(
        'text',
        'anonymous_link_contact_id',
        E::ts('Anonymous Contact ID'),
        ["style" => "width: 50px;"],
        FALSE
    );

    // Token Tools
    $this->add(
        'checkbox',
        'fix_hash_token',
        E::ts('Fix {contact.hash} Token')
    );


    // load contacts
    $this->renderContact($current_values, 'link');

    // set default values
    $this->setDefaults($current_values);

    // submit
    $this->addButtons(array(
      array(
          'type'      => 'submit',
          'name'      => E::ts('Save'),
          'isDefault' => TRUE,
      ),
    ));

    // export form elements
    parent::buildQuickForm();
  }

  /**
   * Render the current anonymous_open_contact_id value
   *
   * @param $data array  data
   * @param $key  string key (open|link)
   * @throws CiviCRM_API3_Exception
   */
  protected function renderContact($data, $key) {
    if (!empty($data["anonymous_{$key}_contact_id"])) {
      $contact_id = (int) CRM_Utils_Array::value("anonymous_{$key}_contact_id", $data, 0);
      if ($contact_id) {
        $result = civicrm_api3('Contact', 'get', ['id' => $contact_id, 'return' => 'display_name,contact_type']);
        if (!empty($result['id'])) {
          $contact = reset($result['values']);
          $this->assign("anonymous_{$key}_contact_name", "{$contact['display_name']} ({$contact['contact_type']})");
        } else {
          $this->assign("anonymous_{$key}_contact_name", E::ts("Contact [%1] not found!", [1 => $contact_id]));
        }
      } else {
        $this->assign("anonymous_{$key}_contact_name", E::ts("Bad contact ID: '%1'", [1 => CRM_Utils_Array::value("anonymous_{$key}_contact_id", $data, '')]));
      }
    } else {
      $this->assign("anonymous_{$key}_contact_name", E::ts("disabled"));
    }
  }

  /**
   * get the elements of the form
   * used as a filter for the values array from post Process
   * @return array
   */
  protected function getSettingsInForm() {
    return array(
      'extra_mail_header_key',
      'extra_mail_header_value',
      'processed_retention_value',
      'ignored_retention_value',
      'anonymous_open_enabled',
      'anonymous_open_url',
      'anonymous_open_permission',
      'anonymous_open_contact_id',
      'anonymous_link_enabled',
      'anonymous_link_url',
      'anonymous_link_permission',
      'anonymous_link_contact_id',
      'fix_hash_token',
    );
  }

  /**
   * Post process input values and save them to DB
   */
  public function postProcess() {
    $config = CRM_Mailingtools_Config::singleton();
    $values = $this->exportValues();
    $settings = $config->getSettings();
    $settings_in_form = $this->getSettingsInForm();
    foreach ($settings_in_form as $name) {
      $settings[$name] = CRM_Utils_Array::value($name, $values, NULL);
    }
    $config->setSettings($settings);

    // re-render new value
    $this->renderContact($values, 'open');
    $this->renderContact($values, 'link');

    parent::postProcess();
  }

}
