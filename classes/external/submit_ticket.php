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
 * External function to submit a Freshdesk support ticket.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_freshdesk\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_freshdesk\local\field_mappings;
use local_freshdesk\local\ticket_fields;

/**
 * Proxies ticket creation to the Freshdesk REST API, keeping the API key server-side.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_ticket extends external_api {
    /**
     * Defines the parameters accepted by execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'subject'    => new external_value(PARAM_TEXT, 'Ticket subject'),
            'message'    => new external_value(PARAM_TEXT, 'Ticket message body'),
            'currenturl' => new external_value(PARAM_TEXT, 'Current page URL'),
            'coursename' => new external_value(PARAM_TEXT, 'Current course name', VALUE_DEFAULT, ''),
            'userrole'   => new external_value(PARAM_TEXT, 'User role label', VALUE_DEFAULT, ''),
            'screenshot' => new external_value(
                PARAM_RAW,
                'Base64-encoded JPEG screenshot (optional)',
                VALUE_DEFAULT,
                ''
            ),
            'category'   => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Chosen value at this level'),
                'Chosen type of assistance, top level first (optional)',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Submits a ticket to Freshdesk via server-side HTTP request.
     *
     * User identity (name and email) is sourced from the Moodle session, not
     * from client-supplied values, to prevent spoofing. The API key never
     * leaves the server. When a screenshot is provided it is sent as a
     * multipart attachment; otherwise a plain JSON request is made.
     *
     * @param string $subject    Ticket subject line.
     * @param string $message    Message body written by the user.
     * @param string $currenturl URL of the page the user was on.
     * @param string $coursename Name of the current course, or empty string.
     * @param string $userrole   Role label (Staff or Student), or empty string.
     * @param string $screenshot Base64-encoded JPEG, or empty string.
     * @param string[] $category Chosen type of assistance, top level first, or empty.
     * @return array
     */
    public static function execute(
        string $subject,
        string $message,
        string $currenturl,
        string $coursename,
        string $userrole,
        string $screenshot = '',
        array $category = []
    ): array {
        global $CFG, $USER;

        require_once($CFG->libdir . '/filelib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'subject'    => $subject,
            'message'    => $message,
            'currenturl' => $currenturl,
            'coursename' => $coursename,
            'userrole'   => $userrole,
            'screenshot' => $screenshot,
            'category'   => $category,
        ]);

        // The widget is exposed site-wide; validate the system context and require the
        // local/freshdesk:use capability so guest / unauthenticated sessions and any
        // role explicitly denied this capability cannot submit tickets via the proxy.
        $context = \core\context\system::instance();
        self::validate_context($context);
        require_capability('local/freshdesk:use', $context);

        $config    = get_config('local_freshdesk');
        // Trim to tolerate whitespace accidentally pasted with the key or URL.
        $apikey    = trim((string) ($config->api_key ?? ''));
        $portalurl = rtrim(trim((string) ($config->portal_url ?? '')), '/');

        if (empty($config->enabled) || $apikey === '' || $portalurl === '') {
            return self::failure('Plugin disabled or portal URL / API key not configured.');
        }

        // Reject non-HTTPS portal URLs to ensure the API key is never sent in clear text.
        if (stripos($portalurl, 'https://') !== 0) {
            return self::failure('Portal URL must start with https://.');
        }

        // Build HTML ticket description with page context.
        $descparts   = [];
        $descparts[] = '<p>' . nl2br(htmlspecialchars($params['message'], ENT_QUOTES)) . '</p>';
        $descparts[] = '<hr>';
        $descparts[] = '<p><strong>Page URL:</strong> ' .
            htmlspecialchars($params['currenturl'], ENT_QUOTES) . '</p>';

        if ($params['coursename'] !== '') {
            $descparts[] = '<p><strong>Course:</strong> ' .
                htmlspecialchars($params['coursename'], ENT_QUOTES) . '</p>';
        }

        if ($params['userrole'] !== '') {
            $descparts[] = '<p><strong>Role:</strong> ' .
                htmlspecialchars($params['userrole'], ENT_QUOTES) . '</p>';
        }

        $descparts[] = '<p><strong>Moodle username:</strong> ' .
            htmlspecialchars($USER->username, ENT_QUOTES) . '</p>';
        $profileurl  = (new \moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false);
        $descparts[] = '<p><strong>Profile:</strong> <a href="' .
            htmlspecialchars($profileurl, ENT_QUOTES) . '">' .
            htmlspecialchars($profileurl, ENT_QUOTES) . '</a></p>';
        $descparts[] = '<p><strong>Moodle user ID:</strong> ' . (int) $USER->id . '</p>';

        $description = implode("\n", $descparts);
        $authheader  = 'Authorization: Basic ' . base64_encode($apikey . ':X');

        // Optional ticket routing fields. Some Freshdesk accounts mark Type,
        // Group or Agent as mandatory on submission and reject API requests
        // that omit them, so include each one when configured.
        $extrafields  = [];
        $tickettype   = trim((string) ($config->ticket_type ?? ''));
        $groupid      = (int) ($config->group_id ?? 0);
        $responderid  = (int) ($config->responder_id ?? 0);
        if ($tickettype !== '') {
            $extrafields['type'] = $tickettype;
        }
        if ($groupid > 0) {
            $extrafields['group_id'] = $groupid;
        }
        if ($responderid > 0) {
            $extrafields['responder_id'] = $responderid;
        }

        // The type of assistance chosen from the dropdown fills the configured Freshdesk
        // field. Only values that are current choices of that field are accepted.
        $customfields = [];
        if (!empty($params['category'])) {
            $field = ticket_fields::get_configured_field();
            $selection = $field === null ? null : ticket_fields::map_selection($field, $params['category']);
            if ($selection === null) {
                return self::failure('The chosen type of assistance is not a current choice of the configured Freshdesk field.');
            }
            if ($field['type'] === 'default_ticket_type') {
                $extrafields['type'] = reset($selection);
            } else {
                $customfields = $selection;
            }
        }

        // Extra fields filled from the user's Moodle profile (e.g. cf_imis_id = idnumber), for
        // Freshdesk accounts that make such fields mandatory. They never override the
        // type of assistance or the routing settings above.
        $mapped       = field_mappings::resolve($USER);
        $customfields = $customfields + $mapped['custom'];
        $extrafields  = $extrafields + $mapped['standard'];

        // Decode and validate screenshot if one was supplied.
        $screenshotpath = '';
        if ($params['screenshot'] !== '') {
            $decoded = base64_decode($params['screenshot'], true);
            // Accept only valid data under 5 MB with a JPEG magic-byte header.
            if (
                $decoded !== false
                && strlen($decoded) < 5242880
                && substr($decoded, 0, 3) === "\xFF\xD8\xFF"
            ) {
                $tmpdir         = make_temp_directory('local_freshdesk');
                $screenshotpath = $tmpdir . '/' . uniqid('screenshot_', true) . '.jpg';
                file_put_contents($screenshotpath, $decoded);
            }
        }

        $curl = new \curl();
        // Bound the external request so a slow or hung Freshdesk endpoint never stalls a Moodle page.
        $curl->setopt([
            'CURLOPT_CONNECTTIMEOUT' => 5,
            'CURLOPT_TIMEOUT'        => 20,
        ]);

        if ($screenshotpath !== '') {
            // Multipart request — curl sets Content-Type with boundary automatically.
            $curl->setHeader([$authheader]);
            $postdata = [
                'email'         => $USER->email,
                'name'          => fullname($USER),
                'subject'       => $params['subject'],
                'description'   => $description,
                'source'        => '2',
                'status'        => '2',
                'priority'      => '1',
                'attachments[]' => new \CURLFile($screenshotpath, 'image/jpeg', 'screenshot.jpg'),
            ];
            foreach ($extrafields as $name => $value) {
                $postdata[$name] = (string) $value;
            }
            foreach ($customfields as $name => $value) {
                $postdata['custom_fields[' . $name . ']'] = (string) $value;
            }
            $responsebody = $curl->post($portalurl . '/api/v2/tickets', $postdata);
            @unlink($screenshotpath);
        } else {
            // JSON request (no attachment).
            $curl->setHeader(['Content-Type: application/json', $authheader]);
            if ($customfields) {
                $extrafields['custom_fields'] = $customfields;
            }
            $payload = json_encode(array_merge([
                'email'       => $USER->email,
                'name'        => fullname($USER),
                'subject'     => $params['subject'],
                'description' => $description,
                'source'      => 2,
                'status'      => 2,
                'priority'    => 1,
            ], $extrafields));
            $responsebody = $curl->post($portalurl . '/api/v2/tickets', $payload);
        }

        $info     = $curl->get_info();
        $httpcode = (int) ($info['http_code'] ?? 0);

        if ($httpcode !== 201) {
            return self::failure(self::describe_freshdesk_error($httpcode, (string) $responsebody, (string) $curl->error));
        }

        $event = \local_freshdesk\event\ticket_submitted::create([
            'context' => $context,
            'other'   => ['subject' => $params['subject']],
        ]);
        $event->trigger();

        return ['success' => true];
    }

    /**
     * Reports a failed submission.
     *
     * Site administrators get the reason back so the widget can show it on the form.
     * Everyone else gets the generic error, with the reason only in the exception's
     * debug info (shown when developer debugging is on), because Freshdesk's replies
     * can contain account details users should not see.
     *
     * @param string $detail What went wrong.
     * @return array Result for site administrators.
     * @throws \moodle_exception For everyone else.
     */
    private static function failure(string $detail): array {
        if (is_siteadmin()) {
            return ['success' => false, 'errordetail' => $detail];
        }
        throw new \moodle_exception('errorsubmitting', 'local_freshdesk', '', null, $detail);
    }

    /**
     * Turns a failed Freshdesk response into a short, readable description.
     *
     * Freshdesk validation errors look like {"description": "Validation failed",
     * "errors": [{"field": "cf_x", "message": "...", "code": "missing_field"}]};
     * each listed field is shown with its message and code.
     *
     * @param int $httpcode HTTP status, 0 when the request did not complete.
     * @param string $body Response body.
     * @param string $curlerror Transport error, if any.
     * @return string
     */
    public static function describe_freshdesk_error(int $httpcode, string $body, string $curlerror = ''): string {
        if ($httpcode === 0) {
            return 'Could not reach Freshdesk' . ($curlerror !== '' ? ': ' . $curlerror : '.');
        }

        $data = json_decode($body, true);
        if (is_array($data)) {
            $parts = [];
            foreach (($data['errors'] ?? []) as $error) {
                if (is_array($error)) {
                    $parts[] = trim(
                        ($error['field'] ?? '') . ': ' . ($error['message'] ?? '') .
                        (isset($error['code']) ? ' [' . $error['code'] . ']' : ''),
                        ': '
                    );
                }
            }
            $summary = (string) ($data['description'] ?? $data['message'] ?? '');
            if ($parts) {
                $summary .= ($summary !== '' ? ' — ' : '') . implode('; ', $parts);
            }
            if ($summary !== '') {
                return 'Freshdesk returned HTTP ' . $httpcode . ': ' . \core_text::substr($summary, 0, 600);
            }
        }

        return 'Freshdesk returned HTTP ' . $httpcode . ': ' . \core_text::substr(trim($body), 0, 300);
    }

    /**
     * Defines the return value structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'     => new external_value(PARAM_BOOL, 'Whether the ticket was created successfully'),
            'errordetail' => new external_value(
                PARAM_TEXT,
                'Why the ticket was not created; returned to site administrators only',
                VALUE_OPTIONAL
            ),
        ]);
    }
}
