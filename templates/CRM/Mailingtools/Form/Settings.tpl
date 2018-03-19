{*-------------------------------------------------------+
| SYSTOPIA MailingTools Extension                        |
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
+-------------------------------------------------------*}

<h3>{ts domain='de.systopia.mailingtools'}Custom Mail Header Fields{/ts}</h3>

<div class="crm-section mailingtools mailingtools-custommailheader">
  <div class="crm-section">
    <div class="label">{$form.extra_mail_header_key.label}</div>
    <div class="content">{$form.extra_mail_header_key.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.extra_mail_header_value.label}</div>
    <div class="content">{$form.extra_mail_header_value.html}</div>
    <div class="clear"></div>
  </div>

  <h3>{ts domain='de.systopia.mailingtools'}Bounce Mailbox Retention{/ts}</h3>
  <div class="crm-section">
    <div class="label">CiviMail Ignored Retention</div>
    <div class="content">NULL</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">CiviMail processed retention</div>
    <div class="content">NULL</div>
    <div class="clear"></div>
  </div>


</div>

<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{*===============================================================================================================*}
{*Bounce Pattern*}

<p> </p>

<h3>{ts domain='de.systopia.mailingtools'}Additional Bounce Patterns{/ts}</h3>
<p> </p>

<div class="crm-section mailingtools mailingtools-custommailheader">
  <a class="button" href="{crmURL p="civicrm/admin/setting/ImportBouncePattern" q="name=smtp_code_pattern_simple"}">
    SMTP Pattern Simple
  </a>
</div>
</br>
<p> </p>

<div class="crm-section mailingtools mailingtools-custommailheader">
  <a class="button" href="{crmURL p="civicrm/admin/setting/ImportBouncePattern" q="name=smtp_code_pattern_enhanced"}">
      SMTP Pattern Enhanced
  </a>
</div>
</br>
<p> </p>

<div class="crm-section mailingtools mailingtools-custommailheader">
  <a class="button" href="{crmURL p="civicrm/admin/setting/ImportBouncePattern" q="name=update_away_bounce_pattern_german"}">
    German Away Pattern
  </a>
</div>
