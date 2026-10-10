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
 * Maps Freshdesk ticket fields to values from the submitting user's Moodle profile.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_freshdesk\local;

/**
 * Parses the "Extra ticket fields" setting and resolves it for a user.
 *
 * The setting holds one mapping per line, "freshdesk_field = moodle_field", for
 * example "cf_imis_id = idnumber" or "cf_member_type = profile_field_membertype".
 * Freshdesk fields starting with cf_ are sent as custom fields; others are sent
 * as standard ticket fields.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_mappings {
    /** Ticket fields the plugin sets itself; a mapping must never replace them. */
    const RESERVED_FIELDS = [
        'subject', 'description', 'email', 'name', 'source', 'status', 'priority',
        'attachments', 'custom_fields', 'requester_id',
    ];

    /** Freshdesk custom field types whose values must be sent as numbers. */
    const NUMBER_TYPES = ['custom_number' => 'int', 'custom_decimal' => 'float'];

    /** Core user fields that may be sent to Freshdesk (never passwords or other secrets). */
    const USER_FIELDS = [
        'id', 'username', 'idnumber', 'email', 'firstname', 'lastname', 'institution',
        'department', 'phone1', 'phone2', 'address', 'city', 'country', 'lang', 'timezone',
    ];

    /**
     * Parses the setting into valid mappings and the lines that could not be understood.
     *
     * @param string $setting The setting's text.
     * @return array mappings: Freshdesk field => Moodle field; invalid: the rejected lines.
     */
    public static function parse(string $setting): array {
        $mappings = [];
        $invalid  = [];

        foreach (preg_split('/\R/', $setting) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('=', $line, 2));
            if (count($parts) === 2 && self::valid_freshdesk_field($parts[0]) && self::valid_moodle_field($parts[1])) {
                $mappings[$parts[0]] = $parts[1];
            } else {
                $invalid[] = $line;
            }
        }
        return ['mappings' => $mappings, 'invalid' => $invalid];
    }

    /**
     * Returns the Freshdesk fields to send for a user, from the plugin setting.
     *
     * Empty values are left out. Values for Freshdesk number fields are returned as
     * numbers, because Freshdesk rejects quoted numbers there; everything else stays
     * text (so a phone number such as 5551234 is not turned into a number).
     *
     * @param \stdClass $user The submitting user.
     * @return array custom: cf_ fields for custom_fields; standard: other ticket fields.
     */
    public static function resolve(\stdClass $user): array {
        global $CFG;

        require_once($CFG->dirroot . '/user/profile/lib.php');

        $result   = ['custom' => [], 'standard' => []];
        $mappings = self::parse((string) get_config('local_freshdesk', 'field_mappings'))['mappings'];
        if (!$mappings) {
            return $result;
        }

        $profile = null;
        $types   = null;
        foreach ($mappings as $freshdeskfield => $moodlefield) {
            if (str_starts_with($moodlefield, 'profile_field_')) {
                $profile ??= profile_user_record((int) $user->id, false);
                $value = $profile->{substr($moodlefield, strlen('profile_field_'))} ?? '';
            } else {
                $value = $user->{$moodlefield} ?? '';
            }

            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $iscustom = str_starts_with($freshdeskfield, 'cf_');
            if ($iscustom && is_numeric($value)) {
                $types ??= self::field_types();
                $value = self::typed_value($value, $types, $freshdeskfield);
            }
            $result[$iscustom ? 'custom' : 'standard'][$freshdeskfield] = $value;
        }
        return $result;
    }

    /**
     * Converts a numeric-looking value to a number when Freshdesk defines the field as one.
     *
     * When the field types could not be read from Freshdesk, a whole number without
     * leading zeros is sent as an integer, which suits Freshdesk number fields such as
     * an ID; the type check replaces this guess whenever Freshdesk can be reached.
     *
     * @param string $value The profile value.
     * @param array|null $types Freshdesk field name => field type, or null when unknown.
     * @param string $field The Freshdesk field name.
     * @return int|float|string
     */
    public static function typed_value(string $value, ?array $types, string $field) {
        $iswhole = (bool) preg_match('/^(0|-?[1-9][0-9]{0,17})$/', $value);
        if ($types === null) {
            return $iswhole ? (int) $value : $value;
        }
        $type = self::NUMBER_TYPES[$types[$field] ?? ''] ?? null;
        if ($type === 'int' && $iswhole) {
            return (int) $value;
        }
        if ($type === 'float' && is_numeric($value)) {
            return (float) $value;
        }
        return $value;
    }

    /**
     * Returns Freshdesk field name => type from the (cached) ticket fields, or null when unavailable.
     *
     * @return array|null
     */
    private static function field_types(): ?array {
        $fields = ticket_fields::fetch_fields();
        if ($fields === null) {
            return null;
        }
        $types = [];
        foreach ($fields as $field) {
            if (is_array($field) && isset($field['name'], $field['type'])) {
                $types[(string) $field['name']] = (string) $field['type'];
            }
        }
        return $types;
    }

    /**
     * Whether a name looks like a Freshdesk ticket field (e.g. cf_imis_id or phone)
     * and is not one the plugin sets itself, such as subject or description.
     *
     * @param string $name Field name.
     * @return bool
     */
    private static function valid_freshdesk_field(string $name): bool {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/i', $name)
            && !in_array(\core_text::strtolower($name), self::RESERVED_FIELDS, true);
    }

    /**
     * Whether a name is an allowed core user field or a custom profile field.
     *
     * @param string $name Field name, e.g. idnumber or profile_field_membertype.
     * @return bool
     */
    private static function valid_moodle_field(string $name): bool {
        return in_array($name, self::USER_FIELDS, true) || (bool) preg_match('/^profile_field_[a-z0-9_]+$/i', $name);
    }
}
