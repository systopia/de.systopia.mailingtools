<?php

require_once 'mailingtools.civix.php';
use CRM_Mailingtools_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function mailingtools_civicrm_config(&$config) {
  _mailingtools_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function mailingtools_civicrm_xmlMenu(&$files) {
  _mailingtools_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mailingtools_civicrm_install() {
  _mailingtools_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function mailingtools_civicrm_postInstall() {
  _mailingtools_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function mailingtools_civicrm_uninstall() {
  _mailingtools_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function mailingtools_civicrm_enable() {
  _mailingtools_civix_civicrm_enable();
  CRM_Mailingtools_Config::installScheduledJob();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function mailingtools_civicrm_disable() {
  _mailingtools_civix_civicrm_disable();
  // TODO: remove scheduled job!
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function mailingtools_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mailingtools_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function mailingtools_civicrm_managed(&$entities) {
  _mailingtools_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function mailingtools_civicrm_caseTypes(&$caseTypes) {
  _mailingtools_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function mailingtools_civicrm_angularModules(&$angularModules) {
  _mailingtools_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function mailingtools_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _mailingtools_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementes hook_civicrm_alterMailParams
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterMailParams/
 *
 * @param $params
 * @param $context
 */
function mailingtools_civicrm_alterMailParams(&$params, $context) {
  CRM_Mailingtools_InjectHeader::inject_header($params, $context);
}

/**
 * We will provide our own Mailer (wrapping the original one).
 * so we can manipulate the content of outgoing emails
 */
function mailingtools_civicrm_alterMailer(&$mailer, $driver, $params) {
  $needed = CRM_Mailingtools_Mailer::isNeeded();
  if ($needed) {
    $mailer = new CRM_Mailingtools_Mailer($mailer);
  }
}

/**
 * Set permissions for API calls
 */
function mailingtools_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  if ($entity == 'mailingtools' && $action == 'anonopen') {
    $config = CRM_Mailingtools_Config::singleton();
    $anonopen_permission = $config->getSetting('anonymous_open_permission');
    if ($anonopen_permission) {
      $permissions['mailingtools']['anonopen'] = array($anonopen_permission);
    } else {
      $permissions['mailingtools']['anonopen'] = array('access CiviCRM');
    }

  } elseif ($entity == 'mailingtools' && $action == 'anonurl') {
    $config = CRM_Mailingtools_Config::singleton();
    $anonurl_permission = $config->getSetting('anonymous_link_permission');
    if ($anonurl_permission) {
      $permissions['mailingtools']['anonurl'] = array($anonurl_permission);
    } else {
      $permissions['mailingtools']['anonurl'] = array('access CiviCRM');
    }
  }
}

/**
 * Some token fixes
 *  - make sure that hash is there
 */
function mailingtools_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
  $config = CRM_Mailingtools_Config::singleton();

  $fix_hash_token = $config->getSetting('fix_hash_token');
  if ($fix_hash_token) {
    // make sure 'hash' is there:
    if (!empty($tokens['contact'])) {
      if (in_array('hash', $tokens['contact']) || !empty($tokens['contact']['hash'])) {
        // hash token is requested
        foreach ($values as $contact_id => &$contact_values) {
          if (empty($contact_values['hash'])) {
            CRM_Contact_BAO_Contact_Utils::generateChecksum($contact_id);
            $contact_values['hash'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contact_id, 'hash');
          }
        }
      }
    }
  }
}
