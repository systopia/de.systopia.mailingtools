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

    $this->add(
      'checkbox',
      'enable_automatic_email_check',
      E::ts('Enabled')
    );

    $this->add(
      'textarea',
      'email_domain_blacklist',
      E::ts('Automatic Email Domain Blacklist'),
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

    // Regex Tokens
    $token_indices = range(0,CRM_Mailingtools_RegexToken::MT_REGEX_TOKEN_COUNT - 1);
    $this->assign('regex_token_indices', $token_indices);
    foreach ($token_indices as $token_index) {
      $this->add(
          'text',
          "regex_token_{$token_index}_def",
          E::ts('Regular Expression'),
          ['class' => 'huge']
      );
      $this->add(
          'select',
          "regex_token_{$token_index}_op",
          E::ts('Operator Type'),
          [
              'api3'    => E::ts("APIv3 Call"),
              'static'  => E::ts("Static Method Call"),
              'replace' => E::ts("Regex Replace"),
          ]
      );
      $this->add(
          'text',
          "regex_token_{$token_index}_val",
          E::ts('Value Function'),
          ['class' => 'huge']
      );
    }
    // set defaults
    $current_tokens = CRM_Mailingtools_RegexToken::getTokenDefinitions();
    foreach ($current_tokens as $token_index => $token_definition) {
      $this->setDefaults([
          "regex_token_{$token_index}_def" => $token_definition['def'],
          "regex_token_{$token_index}_op"  => $token_definition['op'],
          "regex_token_{$token_index}_val" => $token_definition['val'],
      ]);
    }

    // Mosaico Save Message
    $this->add(
      'checkbox',
      'mosaico_save_message',
      E::ts('JS Warning to save Mosaico templates')
    );

    // Mailing Debugging Options, see #17
    $this->add(
      'checkbox',
      'mailing_debugging_short',
      E::ts('Print Short Debugging information to file')
    );
    $this->add(
      'checkbox',
      'mailing_debugging_header',
      E::ts('Print Email Header to File')
    );
    $this->add(
      'checkbox',
      'mailing_debugging_recipients',
      E::ts('Print Email Recipients to Log File')
    );
    $this->add(
      'checkbox',
      'mailing_debugging_body',
      E::ts('Print full Email Body to Log file')
    );
    $this->add(
      'checkbox',
      'mailing_debugging_omit_mailings',
      E::ts('Omit Logging for Mailings')
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
   * Override validation for custom tokens
   * @return bool|void
   */
  public function validate(): bool {
    parent::validate();

    $regex_tokens = $this->extractRegexTokens($this->_submitValues);
    foreach ($regex_tokens as $index => $token_spec) {
      $error = CRM_Mailingtools_RegexToken::verifyTokenDefinition($token_spec);
      if ($error) {
        $this->_errors["regex_token_{$index}_val"] = $error;
      }
    }
    $error = $this->validate_domains($this->_submitValues);
    if ($error) {
      $this->_errors["email_domain_blacklist"] = $error;
    }

    return count($this->_errors) == 0;
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
      $settings[$name] = $values[$name] ?? NULL;
    }
    $config->setSettings($settings);

    // extract regex tokens
    $regex_tokens = $this->extractRegexTokens($values);
    CRM_Mailingtools_RegexToken::setTokenDefinitions($regex_tokens);

    // re-render new value
    $this->renderContact($values, 'open');
    $this->renderContact($values, 'link');

    parent::postProcess();
  }

  /**
   * Extract the (complete) token definitions in the form
   * @param $data array
   * @return array list of token definitions
   */
  protected function extractRegexTokens($data) {
    $token_defs = [];
    $token_indices = range(0,CRM_Mailingtools_RegexToken::MT_REGEX_TOKEN_COUNT - 1);
    foreach ($token_indices as $token_index) {
      if (!empty($data["regex_token_{$token_index}_def"]) && !empty($data["regex_token_{$token_index}_val"])) {
        $token_defs[] = [
            'def' => html_entity_decode($data["regex_token_{$token_index}_def"]),
            'op'  => $data["regex_token_{$token_index}_op"],
            'val' => html_entity_decode($data["regex_token_{$token_index}_val"]),
        ];
      }
    }
    return $token_defs;
  }

  /**
   * @param $data
   * @return string|true
   *
   * Validate input domains via regex pattern, https://regex101.com/r/IY4AVw/1
   */
  protected function validate_domains($data) {
    $pattern = "/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/";
    if (empty($data['email_domain_blacklist'])) {
      // it's ok to not have blacklisted domains, or delete them
      return false;
    }
    $domains = explode(",", $data['email_domain_blacklist']);

    foreach ($domains as $domain) {
      if (!preg_match($pattern, $domain)) {
        return "Invalid Domain {$domain}, please enter valid Domain Names comma searated. e.g. example1.com,example2.com";
      }
    }
    return false;
  }

  /**
   * Render the current anonymous_open_contact_id value
   *
   * @param $data array  data
   * @param $key  string key (open|link)
   * @throws CRM_Core_Exception
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
        'mosaico_save_message',
        'enable_automatic_email_check',
        'email_domain_blacklist',
        'mailing_debugging_short',
        'mailing_debugging_header',
        'mailing_debugging_recipients',
        'mailing_debugging_body',
        'mailing_debugging_omit_mailings',
    );
  }

}
