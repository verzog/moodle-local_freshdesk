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
$string['close'] = 'Close support widget';
$string['contactsupport'] = 'Contact Support';
$string['enabled'] = 'Enable widget';
$string['enabled_desc'] = 'Show the Freshdesk support widget on all Moodle pages.';
$string['errormessage'] = 'Please describe your issue.';
$string['errorsubject'] = 'Please enter a subject.';
$string['errorsubmitting'] = 'Failed to submit your support ticket. Please check the plugin configuration or try again later.';
$string['event_ticket_submitted'] = 'Freshdesk support ticket submitted';
$string['freshdesk:use'] = 'Use the Freshdesk support widget';
$string['gethelp'] = 'Get Help';
$string['group_id'] = 'Default group ID';
$string['group_id_desc'] = 'Optional. Freshdesk group ID to assign new tickets to. Required if your Freshdesk account makes the Group field mandatory on ticket submission. Find the numeric ID in the URL when editing the group under Freshdesk Admin > Team > Groups.';
$string['hide_for_admins'] = 'Hide for site administrators';
$string['hide_for_admins_desc'] = 'Do not show the widget to Moodle site administrators.';
$string['initialprompt'] = 'Search for help articles above, or contact support below.';
$string['loadingarticle'] = 'Loading...';
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
$string['portal_url_desc'] = 'Your Freshdesk account URL, e.g. https://yourcompany.freshdesk.com (must be HTTPS). This must be your *.freshdesk.com domain — the same domain used for the Freshdesk REST API. A custom support-portal domain (CNAME) will not work here, because API requests to it fail.';
$string['privacy:metadata:freshdesk'] = 'When a user submits a support ticket, personal data is transmitted to the Freshdesk support platform to create and manage the ticket. No data is stored within Moodle.';
$string['privacy:metadata:freshdesk:coursename'] = 'The name of the course the user was viewing when the ticket was submitted.';
$string['privacy:metadata:freshdesk:email'] = 'The user\'s email address, used as the Freshdesk contact identifier.';
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
$string['screenshothint'] = 'You can also paste (Ctrl+V / ⌘V) a screenshot.';
$string['responder_id'] = 'Default agent ID';
$string['responder_id_desc'] = 'Optional. Freshdesk agent ID to assign new tickets to. Required if your Freshdesk account makes the Agent field mandatory on ticket submission. Find the numeric ID in the URL when viewing the agent under Freshdesk Admin > Team > Agents.';
$string['searchbutton'] = 'Search';
$string['searching'] = 'Searching...';
$string['searchplaceholder'] = 'Search help articles...';
$string['searchunavailable'] = 'Search unavailable. Please contact support below.';
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
$string['ticketsubmitted'] = 'Your ticket has been submitted!';
$string['viewprofile'] = 'View profile';
$string['widget_color'] = 'Widget button colour';
$string['widget_color_desc'] = 'Hex colour for the Get Help button, e.g. #006B6B';
$string['widget_icon'] = 'Widget icon';
$string['widget_icon_desc'] = 'Icon displayed in the Get Help button and modal header. Enter a Unicode character (e.g. 🎓 or ❓) or an image URL (e.g. https://example.com/icon.png). Leave blank to use the default graduation cap emoji.';
