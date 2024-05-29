# Mailing Tools

![Screenshot](img/screenshot.png)

Provides Tools to support Massmailings in CiviCRM. Options are:

* Add Additional Header to all Mails sent by CiviCRM
* Introduce a retention Policy (in days) for the Bounce Mailing Mailbox. Can be specified for ignored and processed Folders.
* Add additional Patterns for Bounce handling. Currently only adds static, pre-configured files (SMTP Error Codes, some German away patterns)
* Periodically verify Emails for a valid MX record via API command (and subsequently Cron job if needed/wanted)

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.5+ (better 5.6+)
    * PHP 7.0 for Email MX-Verification
    * composer voku/email-check

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl de.systopia.mailingtools@https://github.com/FIXME/de.systopia.mailingtools/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/FIXME/de.systopia.mailingtools.git
cv en mailingtools
cd resources/lib 
composer install
```

## Usage

After installation a settings page exists in civicrm/admin/setting/mailingtools. civicrm/menu/rebuild should be manually triggered after install, then it 
is also available in the administrate console (civicrm/admin).

A Cron Job is added (Check Bounce Mailstore) with 'always' frequency, but disabled. If a retention value 
on the settings page is set, this will trigger the API command and delete all Mails in CiviMail/(ignored|processed) older than 
the configured value in days. 
The API Command is Mailingtools.mailretention - no parameters.

## Known Issues

* This is an alpha Version, testing is needed!
* Careful with the pattern, especially in large email environment. Also Emails will be DELETED if 
a retention is added. 
* Currently the login for the Bounce Mailbox is taken from settings, but might fail sometimes. I am considering 
to add an additional setting dialogue to manually specify login parameters.
* Important: For the API Command Mailingtools.emailsync PHP7.0 is needed, and I would advise to patch voku/emailChecker 
(isdnserror) to only check for valid MX record, see issue [here](https://github.com/voku/email-check/issues/8). 
