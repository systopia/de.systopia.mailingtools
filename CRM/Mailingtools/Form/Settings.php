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

    // submit
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    parent::buildQuickForm();
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
    );
  }

  /**
   * set the default (=current) values in the form
   */
  public function setDefaultValues() {
    $config = CRM_Mailingtools_Config::singleton();
    return $config->getSettings();
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
      if (isset($values[$name])) {
        $settings[$name] = $values[$name];
      }
    }
    $config->setSettings($settings);



    parent::postProcess();
  }

}
