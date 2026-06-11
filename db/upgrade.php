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
 * Upgrade steps between plugin versions.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs upgrade steps between plugin versions.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_local_freshdesk_upgrade($oldversion): bool {

    if ($oldversion < 2026041002) {
        // Ticket form URL now pre-fills subject and description with course/role context.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041002, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041003) {
        // Contact form replaced with native Moodle form submitting via PHP proxy to Freshdesk API.
        // API key is now kept server-side. New external function: local_freshdesk_submit_ticket.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041003, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041004) {
        // Fix: removed declare(strict_types=1) from db/upgrade.php and db/install.php.
        // Moodle passes oldversion as a string; strict types caused a silent TypeError.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041004, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041005) {
        // Coding standards pass: added MOODLE_INTERNAL guard to lib.php, install.php,
        // and upgrade.php; added @package/@copyright/@license to class docblocks;
        // fixed @return type shape in external function; cleaned inline comment style.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041005, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041006) {
        // Add debugging output and console logging to help diagnose ticket submission errors.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041006, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041007) {
        // Fix: require_once filelib.php before using \curl in external function.
        // The AJAX service context does not auto-load lib/filelib.php.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041007, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041008) {
        // Improve console error logging to print full JSON string for easier diagnosis.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041008, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041009) {
        // Include Freshdesk HTTP code and response body in exception debuginfo so it
        // appears in the browser console without needing developer debug mode.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041009, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041010) {
        // Fix: add required status (2=Open) and priority (1=Low) fields to Freshdesk
        // ticket payload. Without these the API returns HTTP 400 validation failed.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041010, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041011) {
        // Enhancement: contact form now shows submitting user name and auto-suggests
        // related knowledge base articles based on current course or page URL.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041011, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041012) {
        // Enhancement: auto-suggested articles now also appear on the modal home screen
        // when the widget is opened, using the course name or page URL as the search term.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041012, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041013) {
        // Enhancement: Freshdesk ticket description now includes the submitting user's
        // Moodle username and a direct profile URL, resolved server-side.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041013, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041014) {
        // Enhancement: article suggestions now search both the course name and the
        // current activity type (e.g. quiz, assign) in parallel and merge results,
        // increasing the likelihood of relevant articles appearing.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041014, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041015) {
        // Feature: screenshot attachment in the contact form (file upload or clipboard
        // paste). Screenshots are compressed to JPEG client-side and sent as a
        // multipart attachment via the server-side Freshdesk API proxy.
        // Coding standards: added declare(strict_types=1) to db/install.php;
        // added JSDoc module block and init docblock to amd/src/widget.js.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041015, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041016) {
        // Rename: plugin component renamed from local_freshdeskwidget to local_freshdesk
        // to match Moodle frankenstyle naming convention and repository name.
        // Plugin must now be installed in local/freshdesk/ (was local/freshdeskwidget/).
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041016, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041017) {
        // Privacy: API key removed from JavaScript config; Freshdesk search and article
        // fetch calls now proxied through new server-side external functions
        // (local_freshdesk_search_articles, local_freshdesk_get_article).
        // Privacy API provider added (privacy/provider.php).
        // Audit event added (ticket_submitted) — fires on every successful ticket submission.
        // Consent notice added to contact form informing users of data sent to Freshdesk.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026041017, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041018) {
        // Code-review fixes: Privacy provider relocated to classes/privacy/provider.php so
        // it is autoloaded by Moodle. Outbound Freshdesk calls now enforce HTTPS and bound
        // connect/read timeouts. lib.php and db/caches.php hardened with strict_types and
        // MOODLE_INTERNAL guard. All widget UI text moved into lang strings (loaded via
        // core/str). Modal and Help button now rendered from Mustache templates
        // (templates/modal.mustache, templates/help_button.mustache). AMD build artifact
        // resynced with source. No database changes required.
        upgrade_plugin_savepoint(true, 2026041018, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041019) {
        // Security: external functions (submit_ticket, search_articles, get_article) now
        // call self::validate_context(\context_system::instance()) so the AJAX endpoints
        // are gated behind a properly validated Moodle context, per the Moodle external
        // services guidance. No database changes required.
        upgrade_plugin_savepoint(true, 2026041019, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041020) {
        // Privacy: the provider class now also implements
        // \core_privacy\local\request\plugin\provider and
        // \core_privacy\local\request\core_userlist_provider with explicit no-op
        // methods, making it clear that the plugin holds no personal data in Moodle's
        // database. The metadata provider continues to declare the data sent to
        // Freshdesk. No database changes required.
        upgrade_plugin_savepoint(true, 2026041020, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041021) {
        // Capability: introduced local/freshdesk:use, granted by default to user,
        // student, teacher, editingteacher, and manager archetypes. The three
        // external functions (submit_ticket, search_articles, get_article) now call
        // require_capability('local/freshdesk:use', \context_system::instance())
        // after validate_context(), and the before_footer hook hides the widget
        // from users who lack the capability. The capability is created
        // automatically by Moodle when the plugin upgrade runs.
        upgrade_plugin_savepoint(true, 2026041021, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041022) {
        // Tiered access: users without local/freshdesk:use (guests, not-logged-in,
        // capability denied) now see a styled "Get Help" link that opens the
        // Freshdesk portal in a new tab — no AJAX endpoints are exercised in that
        // mode. Users with the capability continue to get the full in-page widget
        // (KB search, article viewer, contact form). No database changes required.
        upgrade_plugin_savepoint(true, 2026041022, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041023) {
        // Coding standards: alphabetised the implements list on the privacy
        // provider, removed the no-op blank line after its opening brace,
        // dropped the unnecessary MOODLE_INTERNAL guard from lib.php (no side
        // effects in that file), and reordered the language strings in strict
        // alphabetical order with no inline section comments. No database
        // changes required.
        upgrade_plugin_savepoint(true, 2026041023, 'local', 'freshdesk');
    }

    if ($oldversion < 2026041024) {
        // Coding standards: rewrote the multi-line "two render modes" comment
        // in classes/hook/output/before_footer.php as flowing prose so each
        // line begins with exactly one space after the // marker, satisfying
        // moodle.Commenting.InlineComment.SpacingBefore. No functional or
        // database changes.
        upgrade_plugin_savepoint(true, 2026041024, 'local', 'freshdesk');
    }

    if ($oldversion < 2026061100) {
        // Moodle 5.x compatibility pass: widget config now passed via js_call_amd
        // (data_for_js is deprecated); cache and context classes use their
        // core_cache / core\context namespaces; Freshdesk API failures now emit
        // debugging() diagnostics so admins can see why search/tickets fail;
        // clipboard paste is only captured while the contact form is open.
        // No database changes required.
        upgrade_plugin_savepoint(true, 2026061100, 'local', 'freshdesk');
    }

    return true;
}
