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

declare(strict_types = 1);

use CRM_Mailingtools_ExtensionUtil as E;

/**
 * Configurations
 */
class CRM_Mailingtools_Config {

  /**
   * @var CRM_Mailingtools_Config|null */
  private static $singleton = NULL;

  /**
   * @var array<string, mixed>|null */
  private static $settings = NULL;

  /**
   * @var array<int|string, mixed>|null */
  protected $jobs = NULL;

  /**
   * get the config instance
   *
   * @return CRM_Mailingtools_Config
   */
  public static function singleton() {
    if (self::$singleton === NULL) {
      self::$singleton = new CRM_Mailingtools_Config();
    }
    return self::$singleton;
  }

  /**
   * Get a single setting
   *
   * @param string $name          setting name
   * @param mixed $default_value
   * @return mixed setting
   */
  public function getSetting($name, $default_value = NULL) {
    $settings = self::getSettings();
    return $settings[$name] ?? $default_value;
  }

  /**
   * get Mailingtools settings
   *
   * @return array<string, mixed>
   */
  public function getSettings() {
    if (self::$settings === NULL) {
      $stored_settings = Civi::settings()->get('Mailingtools_settings');
      self::$settings = is_array($stored_settings) ? $stored_settings : [];
    }

    return self::$settings;
  }

  /**
   * set Mailingtools settings
   *
   * @param array<string, mixed> $settings
   * @return void
   */
  public function setSettings($settings) {
    self::$settings = $settings;
    Civi::settings()->set('Mailingtools_settings', $settings);
  }

  /**
   * Install a scheduled job if there isn't one already
   *
   * @return void
   */
  public static function installScheduledJob() {
    $config = self::singleton();
    $jobs = $config->getScheduledJobs();
    if ($jobs === []) {
      // none found? create a new one
      civicrm_api3('Job', 'create', [
        'api_entity'    => 'Mailingtools',
        'api_action'    => 'mailretention',
        'run_frequency' => 'Always',
        'name'          => E::ts('Check Bounce Mailstore'),
        'description'   => E::ts(
          'Checks the configured Bounce Mailbox, and if a retention is configured deletes older mail'
        ),
        'is_active'     => '0',
      ]);
    }
  }

  /**
   * get all scheduled jobs that trigger the dispatcher
   *
   * @return array<int|string, mixed>
   */
  public function getScheduledJobs() {
    if ($this->jobs === NULL) {
      // find all scheduled jobs calling Sqltask.execute
      $query = civicrm_api3('Job', 'get', [
        'api_entity'   => 'Mailingtools',
        'api_action'   => 'mailretention',
        'option.limit' => 0,
      ]);
      $this->jobs = $query['values'];
    }
    return $this->jobs;
  }

}
