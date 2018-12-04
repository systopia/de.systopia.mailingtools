<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)               |
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
 * Processor for anonymous open events
 */
class CRM_Mailingtools_AnonymousOpen {

  /**
   * Process an anonymous open event
   *
   * @param $mid int mailing ID
   * @return int|null OpenEvent ID or NULL disabled
   * @throws Exception if something failed.
   */
  public static function processAnonymousOpenEvent($mid) {
    $config = CRM_Mailingtools_Config::singleton();

    // check if we're enabled
    $enabled = $config->getSetting('anonymous_open_enabled');
    if (!$enabled) {
      return NULL;
    }

    // mid needs to be set
    $mid = (int) $mid;
    if (!$mid) {
      throw new Exception("Invalid mailing ID");
    }

    // NOW: find the event queue ID
    $event_queue_id = NULL;

    // FIRST: try by preferred contact
    $preferred_contact_id = (int) $config->getSetting('anonymous_open_contact_id');
    if ($preferred_contact_id) {
      $event_queue_id = CRM_Core_DAO::singleValueQuery("
        SELECT queue.id
        FROM civicrm_mailing_event_queue queue
        LEFT JOIN civicrm_mailing_job    job   ON queue.job_id = job.id
        WHERE queue.contact_id = %1
          AND job.mailing_id = %2", [
              1 => [$preferred_contact_id, 'Integer'],
              2 => [$mid,                  'Integer']]);
    }

    // SECOND: take the smallest contact ID
    if (empty($event_queue_id)) {
      $contact_id = CRM_Core_DAO::singleValueQuery("
        SELECT MIN(contact_id)
        FROM civicrm_mailing_event_queue queue
        LEFT JOIN civicrm_mailing_job    job   ON queue.job_id = job.id
        WHERE job.mailing_id = %1", [
          1 => [$mid, 'Integer']]);

      if ($contact_id) {
        $event_queue_id = CRM_Core_DAO::singleValueQuery("
        SELECT queue.id
        FROM civicrm_mailing_event_queue queue
        LEFT JOIN civicrm_mailing_job    job   ON queue.job_id = job.id
        WHERE queue.contact_id = %1
          AND job.mailing_id = %2", [
            1 => [$contact_id, 'Integer'],
            2 => [$mid, 'Integer']]);
      } else {
        throw new Exception("No contacts in queue for mailing [{$mid}]");
      }
    }

    // ERROR: if this is not set yet, something is wrong.
    if (empty($event_queue_id)) {
      throw new Exception("No found event in queue for mailing [{$mid}]");
    }

    // all good: add entry
    CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_mailing_event_opened (event_queue_id, time_stamp)
        VALUES (%1, NOW())", [
            1 => [$event_queue_id, 'Integer']]);
  }
}