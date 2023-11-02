<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2019 SYSTOPIA                            |
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
 * Processor for anonymous url tracking
 */
class CRM_Mailingtools_AnonymousURL {

  /**
   * Process an anonymous url tracking event
   *
   * @param $trackable_url_id int URL id
   * @return string|null the URL this ID belongs to
   * @throws Exception if something failed.
   */
  public static function processAnonymousClickEvent($trackable_url_id) {
    $config = CRM_Mailingtools_Config::singleton();

    // check if we're enabled
    $enabled = $config->getSetting('anonymous_link_enabled');
    if (!$enabled) {
      return NULL;
    }

    // check the link ID...
    $trackable_url_id = (int) $trackable_url_id;
    if (!$trackable_url_id) {
      throw new Exception("Bad link ID");
    }
    // load the link
    $link = CRM_Core_DAO::executeQuery("
        SELECT mailing_id, url 
        FROM civicrm_mailing_trackable_url 
        WHERE id = %1", [1 => [$trackable_url_id, 'Integer']]);
    if (!$link->fetch()) {
      throw new Exception("Invalid link ID");
    }

    // NOW: find a matching event queue ID
    $event_queue_id = CRM_Mailingtools_AnonymousOpen::getEventQueueID($link->mailing_id, 'anonymous_link_contact_id');
    if (empty($event_queue_id)) {
      throw new Exception("No found event in queue for mailing [{$link->mailing_id}]");
    }

    // all good: add entry
    Civi::log()->debug("Tracked anonymous click event for link {$trackable_url_id} in mailing [{$link->mailing_id}]");
    CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_mailing_event_trackable_url_open (event_queue_id, trackable_url_id, time_stamp)
        VALUES (%1, %2, NOW())", [
            1 => [$event_queue_id,   'Integer'],
            2 => [$trackable_url_id, 'Integer']]);

    return $link->url;
  }



  /**
   * This function will manipulate open tracker URLs in emails, so they point
   *  to the anonymous handler instead of the native one
   */
  public static function modifyEmailBody(&$body) {
    $config = CRM_Mailingtools_Config::singleton();
    if (!$config->getSetting('anonymous_link_enabled')
        || !$config->getSetting('anonymous_link_url')) {
      // NOT ENABLED
      return;
    }

    // get the base URL
    $core_config = CRM_Core_Config::singleton();
    $system_base = $core_config->userFrameworkBaseURL;

    // find all all relevant links and collect queue IDs
    if (preg_match_all("#{$system_base}sites/all/modules/civicrm/extern/url.php\?u=(?P<link_id>[0-9]+)[^'\"\\n]+#i", $body, $matches)) {
      foreach ($matches[0] as $i => $string) {
        $new_url = $config->getSetting('anonymous_link_url') . "?u={$matches['link_id'][$i]}";
        $body = str_replace($string, $new_url, $body);
      }
    }
  }
}