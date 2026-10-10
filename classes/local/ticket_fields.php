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
 * Reads the configured "type of assistance" dropdown field from Freshdesk.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_freshdesk\local;

/**
 * Fetches, caches and validates the Freshdesk ticket field shown as the widget's dropdown.
 *
 * The administrator names a Freshdesk dropdown field (for example "Types of
 * assistance required") in the plugin settings. Its choices are read from the
 * Freshdesk ticket fields API, so editing them in Freshdesk updates the widget.
 * Nested (dependent) fields are supported: each level becomes its own list.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ticket_fields {
    /** Freshdesk field types that can be shown as a dropdown. */
    const SUPPORTED_TYPES = ['custom_dropdown', 'nested_field', 'default_ticket_type'];

    /**
     * Returns the configured dropdown field, or null when none is set up or it cannot be read.
     *
     * The array has the keys name, type, label, levels (each with name and label,
     * top level first) and options (each with id, parentid, level and value; a
     * parentid of 0 marks a top-level choice).
     *
     * @return array|null
     */
    public static function get_configured_field(): ?array {
        $identifier = trim((string) get_config('local_freshdesk', 'category_field'));
        if ($identifier === '') {
            return null;
        }

        $fields = self::fetch_fields();
        if ($fields === null) {
            return null;
        }

        $field = self::find_field($fields, $identifier);
        if ($field === null) {
            debugging(
                'local_freshdesk: no Freshdesk dropdown field matches the "Type of assistance field" setting "' .
                    s($identifier) . '".',
                DEBUG_DEVELOPER
            );
        }
        return $field;
    }

    /**
     * Fetches all ticket fields from Freshdesk, using the plugin cache when possible.
     *
     * @return array|null Decoded ticket fields, or null on any failure.
     */
    public static function fetch_fields(): ?array {
        $result = self::request_fields(true);
        if ($result['fields'] === null && $result['error'] !== '') {
            debugging('local_freshdesk: ticket fields fetch failed: ' . $result['error'], DEBUG_DEVELOPER);
        }
        return $result['fields'];
    }

    /**
     * Requests the ticket fields from Freshdesk and reports why when that fails.
     *
     * @param bool $usecache Whether a cached copy may be returned; a fresh copy is always cached.
     * @return array fields: decoded ticket fields or null; error: '' on success, 'notconfigured'
     *               when the plugin is not set up, otherwise a short description of the failure.
     */
    public static function request_fields(bool $usecache): array {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $config    = get_config('local_freshdesk');
        $apikey    = trim((string) ($config->api_key ?? ''));
        $portalurl = rtrim(trim((string) ($config->portal_url ?? '')), '/');

        if (empty($config->enabled) || $apikey === '' || stripos($portalurl, 'https://') !== 0) {
            return ['fields' => null, 'error' => 'notconfigured'];
        }

        $cache    = \core_cache\cache::make('local_freshdesk', 'ticket_fields');
        $cachekey = md5($portalurl);
        if ($usecache) {
            $cached = $cache->get($cachekey);
            if (is_array($cached)) {
                return ['fields' => $cached, 'error' => ''];
            }
        }

        $curl = new \curl();
        // Bound the external request so a slow or hung Freshdesk endpoint never stalls the widget.
        $curl->setopt([
            'CURLOPT_CONNECTTIMEOUT' => 5,
            'CURLOPT_TIMEOUT'        => 10,
        ]);
        $curl->setHeader([
            'Authorization: Basic ' . base64_encode($apikey . ':X'),
            'Content-Type: application/json',
        ]);

        $responsebody = $curl->get($portalurl . '/api/v2/ticket_fields');
        $httpcode     = (int) ($curl->get_info()['http_code'] ?? 0);

        if ($httpcode !== 200) {
            $detail = $httpcode === 0 ? (string) $curl->error : substr(trim((string) $responsebody), 0, 200);
            return ['fields' => null, 'error' => 'HTTP ' . $httpcode . ($detail !== '' ? ': ' . $detail : '')];
        }

        $fields = json_decode((string) $responsebody, true);
        if (!is_array($fields)) {
            return ['fields' => null, 'error' => 'HTTP 200, but the response was not a list of ticket fields'];
        }

        $cache->set($cachekey, $fields);
        return ['fields' => $fields, 'error' => ''];
    }

    /**
     * Checks the "Type of assistance field" setting against Freshdesk, bypassing the cache.
     *
     * Used by the settings page to tell the administrator whether the field was found.
     *
     * @param string $identifier The setting's value.
     * @return array|null ok: whether the dropdown will be shown; message: what was found or why not.
     *                    Null when the setting is empty.
     */
    public static function diagnose(string $identifier): ?array {
        if (trim($identifier) === '') {
            return null;
        }

        $result = self::request_fields(false);
        if ($result['error'] === 'notconfigured') {
            return ['ok' => false, 'message' => get_string('categorystatus_noconnection', 'local_freshdesk')];
        }
        if ($result['fields'] === null) {
            return ['ok' => false, 'message' => get_string('categorystatus_fetchfailed', 'local_freshdesk', $result['error'])];
        }
        return self::describe_status($result['fields'], $identifier);
    }

    /**
     * Describes whether the named field exists among the given ticket fields and can be used.
     *
     * @param array $fields Ticket fields as returned by the Freshdesk API.
     * @param string $identifier Field name or label from the setting.
     * @return array ok: whether the dropdown will be shown; message: what was found or why not.
     */
    public static function describe_status(array $fields, string $identifier): array {
        $field = self::find_field($fields, $identifier);
        if ($field !== null) {
            $count = count(array_filter($field['options'], fn(array $option): bool => $option['parentid'] === 0));
            if ($count === 0) {
                return ['ok' => false, 'message' => get_string('categorystatus_nochoices', 'local_freshdesk', $field['label'])];
            }
            return ['ok' => true, 'message' => get_string('categorystatus_ok', 'local_freshdesk', (object) [
                'label' => $field['label'],
                'name'  => $field['name'],
                'count' => $count,
            ])];
        }

        // A field with that name exists but is not a type the widget can show.
        foreach ($fields as $candidate) {
            if (is_array($candidate) && self::matches($candidate, $identifier)) {
                return ['ok' => false, 'message' => get_string('categorystatus_unsupported', 'local_freshdesk', (object) [
                    'label' => (string) ($candidate['label'] ?? $candidate['name'] ?? ''),
                    'type'  => (string) ($candidate['type'] ?? ''),
                ])];
            }
        }

        $available = [];
        foreach ($fields as $candidate) {
            if (is_array($candidate) && in_array($candidate['type'] ?? '', self::SUPPORTED_TYPES, true)) {
                $available[] = '"' . (string) ($candidate['label'] ?? $candidate['name'] ?? '') . '"';
            }
        }
        return ['ok' => false, 'message' => get_string('categorystatus_notfound', 'local_freshdesk', (object) [
            'identifier' => trim($identifier),
            'available'  => $available ? implode(', ', $available) : get_string('none'),
        ])];
    }

    /**
     * Finds a supported dropdown field by its API name or label (case-insensitive).
     *
     * @param array $fields Ticket fields as returned by the Freshdesk API.
     * @param string $identifier Field name (e.g. cf_types_of_assistance) or label.
     * @return array|null The normalised field, or null when no supported field matches.
     */
    public static function find_field(array $fields, string $identifier): ?array {
        foreach ($fields as $field) {
            if (
                is_array($field)
                && in_array($field['type'] ?? '', self::SUPPORTED_TYPES, true)
                && self::matches($field, $identifier)
            ) {
                return self::normalise($field);
            }
        }
        return null;
    }

    /**
     * Whether a ticket field's API name or label matches the identifier (case-insensitive).
     *
     * @param array $field A single ticket field from the Freshdesk API.
     * @param string $identifier Field name or label.
     * @return bool
     */
    private static function matches(array $field, string $identifier): bool {
        $wanted = \core_text::strtolower(trim($identifier));
        foreach (['name', 'label', 'label_for_customers'] as $key) {
            if (isset($field[$key]) && \core_text::strtolower(trim((string) $field[$key])) === $wanted) {
                return true;
            }
        }
        return false;
    }

    /**
     * Converts a Freshdesk dropdown field into the flat structure the widget uses.
     *
     * @param array $field A single ticket field from the Freshdesk API.
     * @return array
     */
    public static function normalise(array $field): array {
        $levels = [[
            'name'  => (string) ($field['name'] ?? ''),
            'label' => self::customer_label($field),
        ]];

        $nested = array_filter($field['nested_ticket_fields'] ?? [], 'is_array');
        usort($nested, fn(array $a, array $b): int => ((int) ($a['level'] ?? 0)) <=> ((int) ($b['level'] ?? 0)));
        foreach ($nested as $level) {
            $levels[] = [
                'name'  => (string) ($level['name'] ?? ''),
                'label' => self::customer_label($level),
            ];
        }

        $options = [];
        self::flatten($field['choices'] ?? [], 0, 1, count($levels), $options);

        return [
            'name'    => $levels[0]['name'],
            'type'    => (string) ($field['type'] ?? ''),
            'label'   => $levels[0]['label'],
            'levels'  => $levels,
            'options' => $options,
        ];
    }

    /**
     * Maps a chosen path (top level first) to the Freshdesk fields to send with the ticket.
     *
     * Every level must be answered down to a choice with no further sub-choices,
     * and every value must be one of the field's current choices.
     *
     * @param array $field A field returned by normalise().
     * @param string[] $path Chosen values, top level first.
     * @return array|null Field name => value, or null when the path is incomplete or invalid.
     */
    public static function map_selection(array $field, array $path): ?array {
        if (empty($path) || count($path) > count($field['levels'])) {
            return null;
        }

        $values   = [];
        $parentid = 0;
        foreach (array_values($path) as $depth => $value) {
            $match = null;
            foreach ($field['options'] as $option) {
                if ($option['parentid'] === $parentid && $option['value'] === (string) $value) {
                    $match = $option;
                    break;
                }
            }
            if ($match === null) {
                return null;
            }
            $values[$field['levels'][$depth]['name']] = $match['value'];
            $parentid = $match['id'];
        }

        // The last answer must not have sub-choices still waiting to be picked.
        foreach ($field['options'] as $option) {
            if ($option['parentid'] === $parentid) {
                return null;
            }
        }
        return $values;
    }

    /**
     * Returns the label shown to customers for a field or nested level.
     *
     * @param array $field Field or nested level definition.
     * @return string
     */
    private static function customer_label(array $field): string {
        foreach (['label_for_customers', 'label_in_portal', 'label', 'name'] as $key) {
            if (!empty($field[$key])) {
                return (string) $field[$key];
            }
        }
        return '';
    }

    /**
     * Recursively flattens Freshdesk choices into a list of options with parent links.
     *
     * Freshdesk returns choices as a list of strings (simple dropdowns), as an
     * object keyed by value whose members hold the next level (nested fields),
     * as a list of such objects (e.g. [{"usa": [{"texas": [...]}]}]), or as a
     * list of objects with value and choices keys. All four are handled.
     *
     * @param mixed $choices Choices at this level.
     * @param int $parentid Id of the parent option, 0 for the top level.
     * @param int $level Current level, starting at 1.
     * @param int $maxlevel Number of levels the field has.
     * @param array $options Output list, appended to in place.
     * @return void
     */
    private static function flatten($choices, int $parentid, int $level, int $maxlevel, array &$options): void {
        if (!is_array($choices) || $level > $maxlevel) {
            return;
        }

        foreach ($choices as $key => $child) {
            if (is_int($key) && is_array($child) && !array_key_exists('value', $child) && !array_is_list($child)) {
                // A list entry such as {"usa": [...]}: its keys are choices at this same level.
                self::flatten($child, $parentid, $level, $maxlevel, $options);
                continue;
            }
            if (is_array($child) && array_key_exists('value', $child)) {
                $value    = $child['value'];
                $children = $child['choices'] ?? $child['nested_options'] ?? [];
            } else if (is_array($child)) {
                $value    = $key;
                $children = $child;
            } else {
                $value    = is_int($key) ? $child : $key;
                $children = [];
            }

            if (!is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            $id = count($options) + 1;
            $options[] = [
                'id'       => $id,
                'parentid' => $parentid,
                'level'    => $level,
                'value'    => (string) $value,
            ];
            self::flatten($children, $id, $level + 1, $maxlevel, $options);
        }
    }
}
