<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language strings for local_freshdesk.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['api_key'] = 'Freshdesk API key';
$string['api_key_desc'] = 'Your Freshdesk API key (Profile Settings > Your API Key). Used server-side only to search knowledge base articles and submit support tickets. Never sent to the browser.';
$string['articleloaderror'] = 'Could not load article.';
$string['attachscreenshot'] = 'Attach screenshot';
$string['back'] = 'Back';
$string['backtoresults'] = 'Back to results';
$string['cachedef_search_results'] = 'Freshdesk knowledge base search results';
$string['cachedef_ticket_fields'] = 'Freshdesk ticket field definitions (type of assistance choices)';
$string['category_field'] = 'Type of assistance field';
$string['category_field_desc'] = 'Optional. The label or API name of a Freshdesk dropdown ticket field, e.g. Types of assistance required. When set, the contact form shows that field\'s choices as a dropdown instead of a free-text Subject box; the chosen option is sent in that field and used as the ticket subject. Choices are read from Freshdesk (refreshed every 15 minutes), so edit them in Freshdesk under Admin > Workflows > Ticket Fields. Nested (dependent) fields show one list per level.';
$string['categorystatus_fetchfailed'] = 'Status: could not read the ticket fields from Freshdesk ({$a}). Check the portal URL, and that the API key belongs to an agent who can view ticket fields.';
$string['categorystatus_nochoices'] = 'Status: found "{$a}" in Freshdesk, but it has no choices. Add choices to it in Freshdesk.';
$string['categorystatus_noconnection'] = 'Status: not checked. Enable the widget and set the HTTPS portal URL and API key first.';
$string['categorystatus_notfound'] = 'Status: no Freshdesk ticket field is called "{$a->identifier}". Dropdown fields available: {$a->available}. The contact form will show the free-text Subject box.';
$string['categorystatus_ok'] = 'Status: found "{$a->label}" ({$a->name}) with {$a->count} choices. The contact form will show it as a dropdown.';
$string['categorystatus_unsupported'] = 'Status: "{$a->label}" is a {$a->type} field. Only dropdown and dependent (nested) dropdown fields can be used, so the contact form will show the free-text Subject box.';
$string['close'] = 'Close support widget';
$string['contactsupport'] = 'Contact Support';
$string['enabled'] = 'Enable widget';
$string['enabled_desc'] = 'Show the Freshdesk support widget on all Moodle pages.';
$string['errorcategory'] = 'Please choose an option from each list.';
$string['errormessage'] = 'Please describe your issue.';
$string['errorsubject'] = 'Please enter a subject.';
$string['errorsubmitting'] = 'Failed to submit your support ticket. Please check the plugin configuration or try again later.';
$string['event_ticket_submitted'] = 'Freshdesk support ticket submitted';
$string['field_mappings'] = 'Extra ticket fields';
$string['field_mappings_desc'] = 'Optional. Fills extra Freshdesk ticket fields from the submitting user\'s Moodle profile, one per line as freshdesk_field = moodle_field. Use this when Freshdesk makes a field mandatory that users should not have to type, e.g. cf_imis_id = idnumber. Freshdesk fields starting with cf_ are custom fields (find the name under Freshdesk Admin > Workflows > Ticket Fields, or in a "Validation failed" error). Moodle fields can be idnumber, username, email, firstname, lastname, institution, department, phone1, phone2, address, city, country, lang, timezone or profile_field_ followed by a custom profile field\'s short name. Whole-number values are sent as numbers. Empty values are left out.';
$string['field_mappings_invalid'] = 'These lines are not in the form freshdesk_field = moodle_field, or name a Moodle field that cannot be used: {$a}';
$string['freshdesk:use'] = 'Use the Freshdesk support widget';
$string['gethelp'] = 'Get Help';
$string['group_id'] = 'Default group ID';
$string['group_id_desc'] = 'Optional. Freshdesk group ID to assign new tickets to. Required if your Freshdesk account makes the Group field mandatory on ticket submission. Find the numeric ID in the URL when editing the group under Freshdesk Admin > Team > Groups.';
$string['hide_for_admins'] = 'Hide for site administrators';
$string['hide_for_admins_desc'] = 'Do not show the widget to Moodle site administrators.';
$string['initialprompt'] = 'Search for help articles above, or contact support below.';
$string['loadingarticle'] = 'Loading...';
$string['loadingoptions'] = 'Loading options...';
$string['loadingsuggestions'] = 'Loading suggestions...';
$string['messagelabel'] = 'How can we help?';
$string['messageplaceholder'] = 'Describe your issue...';
$string['modaltitle'] = 'Support';
$string['noarticles'] = 'No articles found. Try different keywords or contact support below.';
$string['nocontent'] = 'No content available.';
$string['openfullarticle'] = 'Open full article';
$string['openinfreshdesk'] = 'Open in Freshdesk';
$string['openportal'] = 'Open support portal in a new tab';
$string['openwidget'] = 'Open support widget';
$string['pluginname'] = 'Freshdesk Support Widget';
$string['portal_url'] = 'Freshdesk portal URL';
$string['portal_url_desc'] = 'Your Freshdesk account URL, e.g. https://yourcompany.freshdesk.com (must be HTTPS). This must be your *.freshdesk.com domain — the same domain used for the Freshdesk REST API. A custom support-portal domain (CNAME) will not work here, because API requests to it fail. The widget is hidden until this is set.';
$string['privacy:metadata:freshdesk'] = 'When a user submits a support ticket, personal data is transmitted to the Freshdesk support platform to create and manage the ticket. No data is stored within Moodle.';
$string['privacy:metadata:freshdesk:assistancetype'] = 'The type of assistance the user chose from the contact form dropdown.';
$string['privacy:metadata:freshdesk:coursename'] = 'The name of the course the user was viewing when the ticket was submitted.';
$string['privacy:metadata:freshdesk:email'] = 'The user\'s email address, used as the Freshdesk contact identifier.';
$string['privacy:metadata:freshdesk:mappedfields'] = 'Any profile fields the administrator maps to Freshdesk ticket fields under Extra ticket fields (for example the user\'s ID number).';
$string['privacy:metadata:freshdesk:message'] = 'The support message written by the user.';
$string['privacy:metadata:freshdesk:name'] = 'The user\'s full name, included in the Freshdesk ticket.';
$string['privacy:metadata:freshdesk:pageurl'] = 'The URL of the Moodle page the user was viewing when the ticket was submitted.';
$string['privacy:metadata:freshdesk:profileurl'] = 'A direct URL to the user\'s Moodle profile page, included in the ticket description.';
$string['privacy:metadata:freshdesk:screenshot'] = 'An optional screenshot image attached by the user to illustrate the issue.';
$string['privacy:metadata:freshdesk:userid'] = 'The user\'s Moodle numeric ID, included in the ticket description for administrator reference.';
$string['privacy:metadata:freshdesk:username'] = 'The user\'s Moodle username, included in the ticket description for administrator reference.';
$string['privacy:metadata:freshdesk:userrole'] = 'The user\'s role label (Staff or Student) in the current course context.';
$string['privacynotice'] = 'By submitting, your name, email address, and page context will be sent to our support platform (Freshdesk) to process your request.';
$string['relatedheading'] = 'Related articles — did you find what you need?';
$string['removescreenshot'] = 'Remove';
$string['responder_id'] = 'Default agent ID';
$string['responder_id_desc'] = 'Optional. Freshdesk agent ID to assign new tickets to. Required if your Freshdesk account makes the Agent field mandatory on ticket submission. Find the numeric ID in the URL when viewing the agent under Freshdesk Admin > Team > Agents.';
$string['screenshothint'] = 'You can also paste (Ctrl+V / ⌘V) a screenshot.';
$string['screenshotpreview'] = 'Screenshot preview';
$string['searchbutton'] = 'Search';
$string['searching'] = 'Searching...';
$string['searchplaceholder'] = 'Search help articles...';
$string['searchunavailable'] = 'Search unavailable. Please contact support below.';
$string['selectchoice'] = 'Choose...';
$string['send'] = 'Send';
$string['sending'] = 'Sending...';
$string['subjectlabel'] = 'Subject';
$string['submittingas'] = 'Submitting as';
$string['suggestedheading'] = 'Suggested for this page:';
$string['supportrequest'] = 'Support request';
$string['ticket_type'] = 'Default ticket type';
$string['ticket_type_desc'] = 'Optional. Value sent as the ticket "Type" field, e.g. Question. Required if your Freshdesk account makes the Type field mandatory on ticket submission — it must exactly match one of the choices configured under Freshdesk Admin > Workflows > Ticket Fields > Type.';
$string['ticketreply'] = 'We\'ll reply to your registered email address.';
$string['ticketsubmiterror'] = 'Failed to submit ticket. Please try again.';
$string['ticketsubmiterror_admin'] = 'Reason (shown to site administrators only): {$a}';
$string['ticketsubmitted'] = 'Your ticket has been submitted!';
$string['viewprofile'] = 'View profile';
$string['widget_color'] = 'Widget button colour';
$string['widget_color_desc'] = 'Hex colour for the Get Help button, e.g. #006B6B';
$string['widget_icon'] = 'Widget icon';
$string['widget_icon_desc'] = 'Icon displayed in the Get Help button and modal header. Enter a Unicode character (e.g. 🎓 or ❓) or an image URL (e.g. https://example.com/icon.png). Leave blank to use the default graduation cap emoji.';
