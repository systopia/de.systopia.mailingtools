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
 * Class CRM_Mailingtools_EmailVerifier
 */
class CRM_Mailingtools_EmailVerifier {

  private $verify_size;
  private $checking_index;
  private $debug;

  public function __construct($verify_size, $checking_index, $debug) {
    $this->verify_size = $verify_size;
    $this->checking_index = $checking_index;
    $this->debug = $debug;
  }

  public function process() {
    // poll email addresses, and verify
    require_once (__DIR__ . '/../../resources/lib/vendor/voku/email-check/src/voku/helper/EmailCheck.php');
    $result = \voku\helper\EmailCheck::isValid("batroff@t-onliene.de", FALSE, FALSE, FALSE, TRUE);
    if ($result) {
      error_log("RIGHT.");
    } else {
      error_log("WRONG.");
    }
  }
}