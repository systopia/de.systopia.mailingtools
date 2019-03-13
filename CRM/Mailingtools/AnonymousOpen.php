<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
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

    // get a matching event queue ID
    $event_queue_id = self::getEventQueueID($mid, 'anonymous_open_contact_id');

    // ERROR: if this is not set yet, something is wrong.
    if (empty($event_queue_id)) {
      throw new Exception("No found event in queue for mailing [{$mid}]");
    }

    // all good: add entry
    CRM_Core_Error::debug_log_message("Tracked anonymous open event for mailing [{$mid}]");
    CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_mailing_event_opened (event_queue_id, time_stamp)
        VALUES (%1, NOW())", [
            1 => [$event_queue_id, 'Integer']]);

    return $event_queue_id;
  }



  /**
   * Get (or create) an event queue ID for the given
   * @param integer $mid                      mailing ID
   * @param string  $default_contact_setting  setting that yields the default contact ID
   *
   * @return integer|null event queue ID
   * @throws Exception if anything went wrong
   */
  public static function getEventQueueID($mid, $default_contact_setting = NULL) {
    $config = CRM_Mailingtools_Config::singleton();

    // FIRST: try by preferred contact
    $preferred_contact_id = (int) $config->getSetting($default_contact_setting);
    if ($preferred_contact_id) {
      $event_queue_id = CRM_Core_DAO::singleValueQuery("
        SELECT MIN(queue.id)
        FROM civicrm_mailing_event_queue queue
        LEFT JOIN civicrm_mailing_job    job   ON queue.job_id = job.id
        WHERE queue.contact_id = %1
          AND job.mailing_id = %2
          AND job.is_test = 0", [
          1 => [$preferred_contact_id, 'Integer'],
          2 => [$mid,                  'Integer']]);

      if (empty($event_queue_id)) {
        // maybe no real (is_test = 0) job found...?
        $mailing_is_live = self::isMailingLive($mid);
        if (!$mailing_is_live) {
          // ...ah, the mailing is not live yet!
          //  In that case it's ok to use the test job...
          $event_queue_id = CRM_Core_DAO::singleValueQuery("
            SELECT MIN(queue.id)
            FROM civicrm_mailing_event_queue queue
            LEFT JOIN civicrm_mailing_job    job   ON queue.job_id = job.id
            WHERE queue.contact_id = %1
              AND job.mailing_id = %2", [
              1 => [$preferred_contact_id, 'Integer'],
              2 => [$mid, 'Integer']]);
        }
      }

      if (empty($event_queue_id)) {
        // still no queue item? Then we'll create one!
        $event_queue_id = self::injectQueueItem($mid, $preferred_contact_id);
      }
    }

    // PLAN B: take the smallest contact ID
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

    return $event_queue_id;
  }


  /**
   * This function will manipulate open tracker URLs in emails, so they point
   *  to the anonymous handler instead of the native one
   */
  public static function modifyEmailBody(&$body) {
    $config = CRM_Mailingtools_Config::singleton();
    if (!$config->getSetting('anonymous_open_enabled')
        || !$config->getSetting('anonymous_open_url')) {
      // NOT ENABLED
      return;
    }

    // get the base URL
    $core_config = CRM_Core_Config::singleton();
    $system_base = $core_config->userFrameworkBaseURL;

    // find all all relevant links and collect queue IDs
    if (preg_match_all("#{$system_base}sites/all/modules/civicrm/extern/open.php\?q=(?P<queue_id>[0-9]+)[^0-9]#i", $body, $matches)) {
      $queue_ids = $matches['queue_id'];

      if (!empty($queue_ids)) {
        // resolve queue_id => mailing_id
        $queue_id_to_mailing_id = self::getQueueID2MailingID($queue_ids);

        // replace open trackers
        foreach ($queue_id_to_mailing_id as $queue_id => $mailing_id) {
          $new_url = $config->getSetting('anonymous_open_url') . "?mid={$mailing_id}";
          $body = preg_replace("#{$system_base}sites/all/modules/civicrm/extern/open.php\?q={$queue_id}#i", $new_url, $body);
        }
      }
    }
  }

  /**
   * Resolve a list of queue ids to mailing IDs
   *
   * @param $queue_ids array list of queue IDs
   * @return array list of queue_id => mailing id
   *
   * @todo: pre-caching of all queue IDs for the current mailing?
   */
  public static function getQueueID2MailingID($queue_ids) {
    $queue_id_to_mailing_id = [];
    if (empty($queue_ids) || !is_array($queue_ids)) {
      return $queue_id_to_mailing_id;
    }

    // run the query
    $queue_id_list = implode(',', $queue_ids);
    $query = CRM_Core_DAO::executeQuery("
        SELECT queue.id       AS queue_id,
               job.mailing_id AS mailing_id
        FROM civicrm_mailing_event_queue queue
        LEFT JOIN civicrm_mailing_job    job   ON queue.job_id = job.id
        WHERE queue.id IN ({$queue_id_list})
        GROUP BY queue.id");
    while ($query->fetch()) {
      $queue_id_to_mailing_id[$query->queue_id] = $query->mailing_id;
    }

    return $queue_id_to_mailing_id;
  }

  /**
   * Check if the given mailing is LIVE, i.e. has jobs with is_test=0
   *
   * @param $mid integer mailing ID
   *
   * @return bool is the mailing LIVE?
   */
  public static function isMailingLive($mid) {
    $mid = (int) $mid;
    return (bool) CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) 
      FROM civicrm_mailing_job 
      WHERE mailing_id = {$mid}
        AND is_test = 0;");
  }

  /**
   * Create a new (fake) queue item for the given contact
   *
   * @param $mid         int mailing ID
   * @param $contact_id  int contact ID
   *
   * @return int queue item id
   */
  public static function injectQueueItem($mid, $contact_id) {
    // first: select a job (preferrably: not test)
    $job_id = CRM_Core_DAO::singleValueQuery("
          SELECT MIN(job.id)
          FROM civicrm_mailing_job job
          WHERE job.mailing_id = %1
            AND is_test = 0", [
        1 => [$mid, 'Integer']]);
    if (!$job_id) {
      $job_id = CRM_Core_DAO::singleValueQuery("
          SELECT MIN(job.id)
          FROM civicrm_mailing_job job
          WHERE job.mailing_id = %1", [
          1 => [$mid, 'Integer']]);
    }
    if (!$job_id) {
      CRM_Core_Error::debug_log_message("AnonymousOpen: No job found for mailing [{$mid}]");
      return NULL;
    }

    // create item for the given job
    if (function_exists('random_bytes')) {
      $hash = substr(sha1(random_bytes(16)), 0, 16);
    } else {
      $hash = substr(sha1(rand(0, PHP_INT_MAX)), 0, 16);
    }
    CRM_Core_DAO::executeQuery("
      INSERT IGNORE INTO civicrm_mailing_event_queue (job_id, contact_id, hash)
      VALUES (%1, %2, %3)", [
        1 => [$job_id, 'Integer'],
        2 => [$contact_id, 'Integer'],
        3 => [$hash, 'String']]);

    // now the following query should return the new ID
    return CRM_Core_DAO::singleValueQuery("
        SELECT MAX(queue.id)
        FROM civicrm_mailing_event_queue queue
        LEFT JOIN civicrm_mailing_job    job   ON queue.job_id = job.id
        WHERE queue.contact_id = %1
          AND job.mailing_id = %2", [
        1 => [$contact_id, 'Integer'],
        2 => [$mid,        'Integer']]);
  }
}