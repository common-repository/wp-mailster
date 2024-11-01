=== WP Mailster ===
Contributors: brandtoss, svelon
Tags: mailing list, listserv, discussion list, mailman alternative, group email
Tested up to: 6.7
Stable tag: 1.8.16.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP Mailster allows your users to be part of a group and communicate by email without having to log into a website.

== Description ==

<strong>True two-way group communication over email</strong>

[WP Mailster](https://wpmailster.com/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=WPMST&utm_content=plugin+repo+description) allows your users to be part of a group and communicate by email without having to log into a website.
Similar to programs like Mailman or Listserv this plugin allows you to run a discussion mailing list.

That means that every email sent to a central email address is forwarded to the rest of the members of the list.

When members reply to such list emails WP Mailster again forwards them to the other list recipients.
Unlike newsletter plugins this allows true two-way communication via email.


### Features
* group communication through email
* usable with any POP3/IMAP email account
* recipients can be managed in the WordPress admin area
* users can subscribe/unsubscribe through widgets on the website
* all WP users can be chosen as recipients, additional recipients can be stored (without having to create them as WP users)
* users can be organized in groups
* single users or whole groups can be added as recipients of a mailing list
* replies to mailing list messages can be forwarded to all recipients (or only the sender)
* email archive for browsing the mails
* full support of HTML emails and attachments
* custom headers and footers
* subject prefixes
* many [more features](https://wpmailster.com/feature-comparison/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=WPMST&utm_content=plugin+repo+description)

> <strong>Note</strong>: This version of WP Mailster is FREE and limited in terms of number of lists and subscribers per list. Additional features such as captcha protection, double opt-in, email filtering, configurable event notifications, and many others are available in the <strong>[premium editions](https://wpmailster.com/pricing/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=WPMST&utm_content=plugin+repo+description)</strong>.
>

### Help & Support
Please check the [documentation on our site](https://wpmailster.com/documentation/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=WPMST&utm_content=plugin+repo+description) as well as look through the [FAQs](https://wpmailster.com/faq/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=WPMST&utm_content=plugin+repo+description) before [contacting us](https://wpmailster.com/contact/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=WPMST&utm_content=plugin+repo+description). Thank you!

### Special Thanks
This plugin has been tested on various browsers thanks to [BrowserStack](https://www.browserstack.com/)!

== Frequently Asked Questions ==

= How do I send an email? =
When you want to use WP Mailster you don't need to browse to a website, login and do something to send the message there - you just use your favorite mail client.
Simply write an email to the mailing list's address - and nothing else. So use Gmail, Outlook, Thunderbird, a Webmailer, any way you like - just send it to the mailing list address you have setup in WP Mailster.

= Why take the emails so long to be delivered? How can I speed up sending? =
WP Mailster is a part of WordPress which is a PHP based web application. That means: it can not act/run without being triggered and it can not run forever when triggered. This is a technical limitation coming from PHP, not from WP Mailster or WordPress.
Triggering means that somebody accesses the site. During the page load WP Mailster is handling the jobs to do (mail retrieving/sending). Thus mails can only be send/retrieved when somebody is browsing your site, otherwise the delivery is delayed or never done. As your site might not be browsed every few minutes 24×7 we recommend you to use a cronjob that opens the site periodically. We have a guide on our website on how to set that up.

= What are send errors? =
The send errors are messages your email server is giving back to WP Mailster basically saying "I will not forward this message". Then WP Mailster sending for some time but eventually stops which is what you see happening.
The cause can be a lot of things, e.g. hitting send limits (per hour/day) or sending email with content that the server does not like.
You need to find out what your email servers are telling WP Mailster. Please follow our troubleshooting guide on our site.

= Why do I get a "Certificate failure" error message upon checking the inbox connection? =
You might get an error message like this when checking the connection settings: “Certificate failure for [server] Server name does not match certificate” or “Certificate failure for [server] unable to get local issuer certificate”.
This is often the case when you use your own mailing server (with a self-signed certificate). To get a connection established, you need to deactivate the automated certificate check. Do this by adding the following line in the special parameter box: <strong>/novalidate-cert</strong>

= What does the message "Problem identified: PHP IMAP extension not installed" mean? =
Your server needs the PHP IMAP extension installed, otherwise WP Mailster can not work at all. There is nothing that we can do about that, you need to contact your webhoster and see whether they can enable/install the PHP IMAP extension.
If that is not possible you need to use a different webhoster providing a more suitable environment.
Please note that it does not matter whether you want to use IMAP or POP3 for accessing the mailing list inbox. You need the extension in both cases, it is not specific to the IMAP protocol.

= Why do I get "Connection refused" or "Network is unreachable" messages, although I use the correct mailbox settings? =
In most situations this connection errors show up when your webhoster (where WordPress/WP Mailster is installed) is blocking outgoing connections such as connection attempts to 3rd party email servers. This is done to avoid spam sending.
Please contact your webhoster, so they configure their firewall to allow you to send emails.

= Why does my website show this error "Fatal error: Out of memory (allocated [...]) (tried to allocate [...] bytes) in [...]/wp-content/plugins/wp-mailster/[...]" =
Your issue is very likely coming from a large email causing an PHP out of memory error. This is resulting from the fact that every PHP environment has a memory limit. This limit is hit when the email is tried to be retrieved from the mail server.
Please remove this email from the mailing list in question. Either through a mail webfrontend or with the “Delete first email in inbox” tool the “Tools” section in the mailing list’s settings.
In order to prevent have this happen in the future:

* Raise the PHP memory limit. Your webhoster can help you with that.
* Introduce a maximum email size for WP Mailster. You can find this setting in the “List behaviour” tab of the mailing lists settings. If larger emails are in the inbox, WP Mailster will not try to process them but simply delete it and tell the sender what happened.

= What is the difference between a Mailing List and a Group? =
A <strong>mailing list</strong> is a set of recipients that should all get the messages send to the mailing list’s email address.

A <strong>group</strong> is a way of organizing users. Think of it like a bucket of users. It comes in handy if you want certain users be recipients of multiple mailing lists. Then you would not add the users directly to the recipients of a mailing list, but rather add a group.

An example would be that you have three groups: Management, Project Coordinators and Developers.

If you want to run a company-wide mailing list (e.g. all@example.com) and a project mailing list (e.g. project.import@example.com) then you would add

* all three groups (Management, Project Coordinators and Developers) as recipients of the company-wide mailing list
* only the Project Coordinators and Developers groups as recipients of the project mailing list


Why all that?
In case someone joins the project team you would only add the user to the Developers group. Then the user would be automatically be a recipient of both the project and the company-wide mailing list.
The same thing applies if someone leaves: the user only needs to be removed form the groups and thus is automatically removed from the mailing lists that have this groups as the recipients.

So to sum up: if you run many lists and have many users that are largely managed by the admin, then it might be handy to organize users in groups and add the groups as the recipients.

= How can I report security bugs? =
You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/wp-mailster)


== Screenshots ==

1. The dashboard provides an overview of the overall status (mailing list, #emails etc.)
2. All emails (that are forwarded to the subscribers) are also stored in an email archive in the admin section
3. Mailing list settings, general settings
4. Mailing list settings, mailbox (incoming email)
5. Mailing list settings, mailbox: choose from some pre-configured servers or use your own (what most users do)
6. Mailing list settings, sender settings (outgoing email)
7. Mailing list settings, mail content
8. Mailing list settings, list behaviour
9. Manage your recipients (per list and/or via custom groups)
10. Add members to your list (both WP users and users outside WP are possible)

== Changelog ==

= 1.8.16 =
*Release Date - October 28th, 2024*
*   [Improvement] WordPress 6.7 compatibility (marker)
*   [Bug Fix] Security fix to close Cross Site Scripting (XSS) vulnerability

= 1.8.15 =
*Release Date - October 16th, 2024*
*   [Improvement] Improve error handling and logging
*   [Improvement] Add selected logging about queue status and actions
*   [Improvement] WordPress 6.6 compatibility (marker)
*   [Bug Fix] Remove no longer relevant code

= 1.8.14 =
*Release Date - May 24th, 2024*
*   [Improvement] Automatically shorten (too) long subjects, current max length: 191 characters
*   [Bug Fix] Fix email character display/modification issues with Baltic encoding
*   [Bug Fix] Fix backend email archive view (and other admin list views) for Safari
*   [Bug Fix] Fix some rare cases where email was not saved to database
*   [Bug Fix] Fix multiple PHP warnings

= 1.8.12 =
*Release Date - February 1st, 2024*
*   [Bug Fix] Fix reCaptcha (v2 is still used)
*   [Bug Fix] Fix multiple PHP warnings
*   [Bug Fix] Fix multiple PHP deprecation messages (Deprecated: Creation of dynamic property)

= 1.8.11 =
*Release Date - December 21st, 2023*
*   [Improvement] CSV import also supports file with only emails (no name column)
*   [Improvement] Do not create Microsoft and Google default connections because of missing OAUTH2 support
*   [Bug Fix] Avoid error "Prohibited input U+00000081" and update idna-convert library
*   [Bug Fix] Fix issue where special characters in subject break user interface (list of archived emails, list of queued emails)
*   [Bug Fix] Bounce emails containing email addresses in angle brackets are showing the addresses in the email archive email details view

= 1.8.10 =
*Release Date - September 4th, 2023*

*   [Bug Fix] Fix for: Non-static method PEAR::isError() cannot be called statically
*   [Bug Fix] When moderation is active, the list administrators (serving as moderators) should not need to approve their own messages to the list

= 1.8.9 =
*Release Date - August 18th, 2023*

*   [Bug Fix] submitTxt parameter works again for subscribe/unsubscribe shortcodes
*   [Bug Fix] headerTxt parameter works again for subscribe/unsubscribe shortcodes
*   [Bug Fix] Fix error 1406 Data too long for column references_to
*   [Bug Fix] Fix WordPress database error: [Unknown column ‘published’ in ‘where clause’]
*   [Bug Fix] When emails get deleted, no empty folders remain (where the attachments were stored in before they were deleted)
*   [Bug Fix] Fix two bugs related to email digests that could prevent digests from being sent (Society & Enterprise edition)
*   [Bug Fix] Avoid warnings with PHP 8.2

= 1.8.8 =
*Release Date - June 6th, 2023*

*   [Bug Fix] When formatted HTML with very long lines are encountered, the automatic line splitting may run into issues. e.g. when formatted Text from Google Docs is copied into email body. This is now fixed.
*   [Bug Fix] Fix subscriber module problem with PHP 8.1/8.2
*   [Bug Fix] Anticipate problems with apostrophes and quotes characters being part of users' names and notes
*   [Bug Fix] Do not log to PHP error log during installation
*   [Bug Fix] Remove unneeded code, no need to require parse_ini_file function

= 1.8.7 =
*Release Date - March 29th, 2023*

*   [Feature] Add removeFromGroup option for unsubscribe forms to automatically remove a user from a user group when the user unsubscribes
*   [Improvement] Warn about system incompatibility issues (like missing PHP IMAP extensions)
*   [Improvement] Default order lists, users and names by name
*   [Bug Fix] Fix timezone issue for email received and forwarded timestamps
*   [Bug Fix] Fix for iPhone embedded images/photos
*   [Bug Fix] Fix: date parts (like month names) are translated
*   [Bug Fix] User interface for adding mailing list(s) is now tabbed again
*   [Bug Fix] User interface (select list of group users) works correctly after new group was created
*   [Bug Fix] Avoid PHP warnings

= 1.8.6 =
*Release Date - February 8th, 2023*

*   [Bug Fix] Fix PHP include problem related to library Html2Text in free edition as well
*   [Bug Fix] Sender (SMTP) connection check feedback working again
*   [Bug Fix] Avoid some PHP warnings in PHP 8.x

= 1.8.5 =
*Release Date - February 2nd, 2023*

*   [Bug Fix] Email without TO recipient will not stop email(s) from being retrieved from mailing list inbox
*   [Bug Fix] Fix PHP include problem related to library Html2Text (and others)
*   [Bug Fix] The add2Group parameter works in the subscribe-shortcode
*   [Bug Fix] Ability to deal with charset collation differences (between WP users and WP Mailster database tables)
*   [Bug Fix] Avoid PHP warnings in PHP 8.1



== Upgrade Notice ==
= 1.8.15 =
Improve error handling and logging, code cleanup, WP 6.6 compatibility

= 1.8.14 =
Shorten long subjects, fix Baltic encoding issues, fix admin user interface for Safari, fix email saving issues

= 1.8.12 =
Fix reCaptcha (v2), avoid multiple PHP warnings and deprecation messages

= 1.8.11 =
CSV import supports email-only files, No more Microsoft/Google default connections, avoid "Prohibited input U+00000081" error, fix several user interface issues

= 1.8.10 =
Fix for "Non-static method PEAR::isError() cannot be called statically" issue

