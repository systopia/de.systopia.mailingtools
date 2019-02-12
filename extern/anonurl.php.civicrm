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

require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
require_once 'CRM/Core/Error.php';
require_once 'CRM/Utils/Array.php';

$link_id = (int) CRM_Utils_Array::value('u', $_GET);
if (!$link_id) {
  echo "Missing input parameters\n";
  exit();
}

// register open event and retrieve URL
$config = CRM_Core_Config::singleton();
require_once 'CRM/Mailingtools/AnonymousURL.php';
$url = CRM_Mailingtools_AnonymousURL::processAnonymousClickEvent($link_id);

// CRM-18320 - Fix encoded ampersands (see CRM_Utils_System::redirect)
$url = str_replace('&amp;', '&', $url);

// CRM-17953 - The CMS is not bootstrapped so cannot use CRM_Utils_System::redirect
header('Location: ' . $url);
CRM_Utils_System::civiExit();
