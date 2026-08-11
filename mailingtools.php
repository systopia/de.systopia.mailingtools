<?php
// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- civix hook file: require_once + hook functions is the standard, unavoidable pattern here.
declare(strict_types = 1);

require_once 'mailingtools.civix.php';
use CRM_Mailingtools_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @phpstan-param mixed $config
 * @phpstan-return void
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function mailingtools_civicrm_config(&$config) {
  _mailingtools_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @phpstan-return void
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mailingtools_civicrm_install() {
  _mailingtools_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @phpstan-return void
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function mailingtools_civicrm_enable() {
  _mailingtools_civix_civicrm_enable();
  CRM_Mailingtools_Config::installScheduledJob();
}

/**
 * Implements hook_civicrm_alterMailParams().
 *
 * @phpstan-param array<string, mixed> $params
 * @phpstan-param mixed $context
 * @phpstan-return void
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterMailParams/
 */
function mailingtools_civicrm_alterMailParams(&$params, $context) {
  CRM_Mailingtools_InjectHeader::inject_header($params, $context);
}

/**
 * We will provide our own Mailer (wrapping the original one).
 * so we can manipulate the content of outgoing emails
 *
 * @param mixed $mailer
 * @param mixed $driver
 * @param array<string, mixed> $params
 * @return void
 */
function mailingtools_civicrm_alterMailer(&$mailer, $driver, $params) {
  $needed = CRM_Mailingtools_Mailer::isNeeded();
  if ($needed) {
    $mailer = new CRM_Mailingtools_Mailer($mailer, $driver, $params);
  }
}

/**
 * Set permissions for API calls
 *
 * @param string $entity
 * @param string $action
 * @param array<string, mixed> $params
 * @param array<string, mixed> $permissions
 * @return void
 */
function mailingtools_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  if ($entity === 'mailingtools' && $action === 'anonopen') {
    $config = CRM_Mailingtools_Config::singleton();
    $anonopen_permission = $config->getSetting('anonymous_open_permission');
    if ((bool) $anonopen_permission) {
      $permissions['mailingtools']['anonopen'] = [$anonopen_permission];
    }
    else {
      $permissions['mailingtools']['anonopen'] = ['access CiviCRM'];
    }

  }
  elseif ($entity === 'mailingtools' && $action === 'anonurl') {
    $config = CRM_Mailingtools_Config::singleton();
    $anonurl_permission = $config->getSetting('anonymous_link_permission');
    if ((bool) $anonurl_permission) {
      $permissions['mailingtools']['anonurl'] = [$anonurl_permission];
    }
    else {
      $permissions['mailingtools']['anonurl'] = ['access CiviCRM'];
    }
  }
}

/**
 * Some token fixes
 *  - make sure that hash is there
 *
 * @param array<int|string, mixed> $values
 * @param array<int, mixed> $cids
 * @param mixed $job
 * @param array<string, mixed> $tokens
 * @param mixed $context
 * @return void
 */
function mailingtools_civicrm_tokenValues(&$values, $cids, $job = NULL, $tokens = [], $context = NULL) {
  $config = CRM_Mailingtools_Config::singleton();

  $fix_hash_token = $config->getSetting('fix_hash_token');
  if ((bool) $fix_hash_token) {
    // make sure 'hash' is there:
    if (($tokens['contact'] ?? []) !== []) {
      // @phpstan-ignore empty.notAllowed
      if (in_array('hash', $tokens['contact'], TRUE) || !empty($tokens['contact']['hash'])) {
        // hash token is requested
        foreach ($values as $contact_id => &$contact_values) {
          if (($contact_values['hash'] ?? '') === '' || ($contact_values['hash'] ?? '') === '0') {
            CRM_Contact_BAO_Contact_Utils::generateChecksum($contact_id);
            $contact_values['hash'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contact_id, 'hash');
          }
        }
      }
    }
  }
}

/**
 * Implements hook_civicrm_pre().
 *
 * @phpstan-param string $op
 * @phpstan-param string $objectName
 * @phpstan-param int|string $id
 * @phpstan-param array<string, mixed> $params
 * @phpstan-return void
 * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_pre/
 */
function mailingtools_civicrm_pre($op, $objectName, $id, &$params) {
  if ($op === 'delete' && (bool) $id) {
    if ($objectName === 'Individual' || $objectName === 'Household' || $objectName === 'Organization') {
      // make sure the contact used for the anonymous open/click tracking is not deleted
      $config = CRM_Mailingtools_Config::singleton();
      $open_contact_id  = (int) $config->getSetting('anonymous_open_contact_id');
      $click_contact_id = (int) $config->getSetting('anonymous_link_contact_id');
      if ((int) $id === $open_contact_id || (int) $id === $click_contact_id) {
        throw new \RuntimeException(E::ts(
          'You cannot delete the contact currently used for anonymous open/click tracking. Remove Contact [%1] '
          . 'from the settings of the MailingTools extension. Caution: you will lose the anonymous mailing '
          . 'statistics if you delete this contact.',
          [1 => $id]
        ));
      }
    }
  }
}

/**
 * Implements hook_civicrm_pageRun().
 *
 * @phpstan-param mixed $page
 * @phpstan-return void
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
 *
 * @return void
 */
function mailingtools_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  //  Trigger when EMail is edited or updated
  if ($objectName === 'Email' && in_array($op, ['update', 'edit', 'create'], TRUE)) {
    // TODO
    // verify Email address; if invalid then set on hold
    CRM_Mailingtools_Utils::verify_email($op, $objectName, $objectId, $objectRef);
    CRM_Mailingtools_Utils::check_email_dns_blacklist($objectRef->email, $objectRef->id);
  }
}
