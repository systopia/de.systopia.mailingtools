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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mailingtools_civicrm_install() {
  _mailingtools_civix_civicrm_install();
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

/**
 * Implements hook_civicrm_pre
 * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_pre/
 */
function mailingtools_civicrm_pre($op, $objectName, $id, &$params) {
  if ($op == 'delete' && $id) {
    if ($objectName == 'Individual' || $objectName == 'Household' || $objectName == 'Organization') {
      // make sure the contact used for the anonymous open/click tracking is not deleted
      $config = CRM_Mailingtools_Config::singleton();
      $open_contact_id  = (int) $config->getSetting('anonymous_open_contact_id');
      $click_contact_id = (int) $config->getSetting('anonymous_link_contact_id');
      if ($id == $open_contact_id || $id == $click_contact_id) {
        throw new Exception(E::ts("You cannot delete the contact currently used for anonymous open/click tracking. Remove Contact [%1] from the settings of the MailingTools extension. Caution: you will lose the anonymous mailing statistics if you delete this contact.", [1 => $id]));
      }
    }
  }
}

/**
 * implements hook_civicrm_pageRun( &$page )
 * @param $page
 */
function mailingtools_civicrm_pageRun(&$page) {
  $name = $page->getVar('_name');
  switch ($name) {
    case 'Civi\\Angular\\Page\\Main':
      CRM_Mailingtools_Page_MosaicoSave::buildPagehook($page);
      break;
    default:
      return;
  }
}

/**
 * This hook is called after a db write on entities.
 *
 * @param string $op
 *   The type of operation being performed.
 * @param string $objectName
 *   The name of the object.
 * @param int $objectId
 *   The unique identifier for the object.
 * @param object $objectRef
 *   The reference to the object.
 *
 * https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post/
 */
function mailingtools_civicrm_post($op, $objectName, $objectId, &$objectRef) {
//  Trigger when EMail is edited or updated
  if($objectName == "Email" && in_array($op, ['update', 'edit', 'create'])) {
    // TODO
    // verify Email address; if invalid then set on hold
    CRM_Mailingtools_Utils::verify_email($op, $objectName, $objectId,$objectRef);
  }
}
