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

<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="top"}
</div>

<br/><h3>{ts domain='de.systopia.mailingtools'}Custom Mail Header Fields{/ts}</h3><br/>

<div class="crm-section mailingtools mailingtools">
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

<br/><h3>{ts domain='de.systopia.mailingtools'}Anonymous Open Tracking{/ts}</h3><br/>
  <div class="crm-section">
    <div class="label">{$form.anonymous_open_enabled.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Anonymous Open Tracking{/ts}", {literal}{"id":"id-mailtools-anonymous-open-enable","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.anonymous_open_enabled.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.anonymous_open_url.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Anonymous Open URL{/ts}", {literal}{"id":"id-mailtools-anonymous-open-url","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.anonymous_open_url.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.anonymous_open_permission.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}API Permission{/ts}", {literal}{"id":"id-mailtools-anonymous-open-permission","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.anonymous_open_permission.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.anonymous_open_contact_id.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Anonymous Contact ID{/ts}", {literal}{"id":"id-mailtools-anonymous-open-contact-id","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.anonymous_open_contact_id.html} <span id="anonymous_open_contact_name">{$anonymous_open_contact_name}</span></div>
    <div class="clear"></div>
  </div>


<br/><h3>{ts domain='de.systopia.mailingtools'}Anonymous Link Tracking{/ts}</h3><br/>
  <div class="crm-section">
    <div class="label">{$form.anonymous_link_enabled.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Anonymous Link Tracking{/ts}", {literal}{"id":"id-mailtools-anonymous-link-enable","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.anonymous_link_enabled.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.anonymous_link_url.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Anonymous Link URL{/ts}", {literal}{"id":"id-mailtools-anonymous-link-url","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.anonymous_link_url.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.anonymous_link_permission.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}API Permission{/ts}", {literal}{"id":"id-mailtools-anonymous-link-permission","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.anonymous_link_permission.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.anonymous_link_contact_id.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Anonymous Contact ID{/ts}", {literal}{"id":"id-mailtools-anonymous-link-contact-id","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.anonymous_link_contact_id.html} <span id="anonymous_link_contact_name">{$anonymous_link_contact_name}</span></div>
    <div class="clear"></div>
  </div>


<br/><h3>{ts domain='de.systopia.mailingtools'}Bounce Mailbox Retention{/ts}</h3><br/>
  <p>{ts domain='de.systopia.mailingtools'}Retention Time is interpreted as days. If no value is configured, no mails will be deleted.{/ts}</p>

  <div class="crm-section">
    <div class="label">{$form.processed_retention_value.label}</div>
    <div class="content">{$form.processed_retention_value.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.ignored_retention_value.label}</div>
    <div class="content">{$form.ignored_retention_value.html}</div>
    <div class="clear"></div>
  </div>

<br/><h3>{ts domain='de.systopia.mailingtools'}Email Validation{/ts}</h3><br/>

  <div class="crm-section">
    <div class="label">{$form.enable_automatic_email_check.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Hash Token Fix{/ts}", {literal}{"id":"id-mailtools-enable-automatic-email-check","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.enable_automatic_email_check.html}</div>
    <div class="clear"></div>
  </div>

<br/><h3>{ts domain='de.systopia.mailingtools'}Token Tools{/ts}</h3><br/>

  <div class="crm-section">
    <div class="label">{$form.fix_hash_token.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Hash Token Fix{/ts}", {literal}{"id":"id-mailtools-fix-hash-token","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.fix_hash_token.html}</div>
    <div class="clear"></div>
  </div>

<br/><h3>{ts domain='de.systopia.mailingtools'}Custom (Regex) Tokens{/ts} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Custom (Regex) Tokens{/ts}", {literal}{"id":"id-mailtools-regex-tokens","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></h3><br/>
{foreach from=$regex_token_indices item=token_index}
  {capture assign=token_def}regex_token_{$token_index}_def{/capture}
  {capture assign=token_op}regex_token_{$token_index}_op{/capture}
  {capture assign=token_val}regex_token_{$token_index}_val{/capture}
  <div class="crm-section">
    <div class="label">{ts 1=$token_index domain='de.systopia.mailingtools'}Token&nbsp;%1 {/ts}</div>
    <div class="content">#{$form.$token_def.html}#&nbsp;{$form.$token_op.html}&nbsp;{$form.$token_val.html}</div>
    <div class="clear"></div>
  </div>

{/foreach}


<br/><h3>{ts domain='de.systopia.mailingtools'}Mosaico Save Message{/ts}</h3><br/>

<div class="crm-section">
  <div class="label">{$form.mosaico_save_message.label} <a onclick='CRM.help("{ts domain='de.systopia.mailingtools'}Mosaico Save Message{/ts}", {literal}{"id":"id-mailtools-mosaico-save","file":"CRM\/Mailingtools\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='de.systopia.mailingtools'}Help{/ts}" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.mosaico_save_message.html}</div>
  <div class="clear"></div>
</div>


</div>

<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{*===============================================================================================================*}
{*Bounce Pattern*}

<br/><br/><br/><br/>

<br/><h2>{ts domain='de.systopia.mailingtools'}Data Adjustments (not Settings){/ts}</h2>
<br/><h3>{ts domain='de.systopia.mailingtools'}Additional Bounce Patterns{/ts}</h3>
<p><i>{ts domain='de.systopia.mailingtools'}Add additional Bounce pattern to the Database. Checks first if pattern is already available. Outputs the number of pattern added/ignored from the specified file.{/ts}</i></p>

<div class="crm-section mailingtools mailingtools-custommailheader">
  <a class="button" href="{crmURL p="civicrm/admin/setting/ImportBouncePattern" q="name=smtp_code_pattern_simple"}">
    {ts domain='de.systopia.mailingtools'}Add Simple SMTP Patterns{/ts}
  </a>
  <a class="button" href="{crmURL p="civicrm/admin/setting/ImportBouncePattern" q="name=smtp_code_pattern_enhanced"}">
    {ts domain='de.systopia.mailingtools'}Add Enhanced SMTP Patterns{/ts}
  </a>
  <a class="button" href="{crmURL p="civicrm/admin/setting/ImportBouncePattern" q="name=update_away_bounce_pattern_german"}">
    {ts domain='de.systopia.mailingtools'}Add German Away Patterns{/ts}
  </a>
</div>
</br>

<div class="crm-section mailingtools mailingtools-custommailheader">
</div>
