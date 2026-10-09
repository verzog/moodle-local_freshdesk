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
 * External function returning the "type of assistance" choices for the contact form.
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
use local_freshdesk\local\ticket_fields;

/**
 * Returns the choices of the configured Freshdesk dropdown field, read server-side.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_ticket_options extends external_api {
    /**
     * Defines the parameters accepted by execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns the dropdown field's label, level labels and choices.
     *
     * When no field is configured, or Freshdesk cannot be read, enabled is false
     * and the contact form falls back to a free-text subject.
     *
     * @return array
     */
    public static function execute(): array {
        self::validate_parameters(self::execute_parameters(), []);

        $context = \core\context\system::instance();
        self::validate_context($context);
        require_capability('local/freshdesk:use', $context);

        $field = ticket_fields::get_configured_field();
        if ($field === null || empty($field['options'])) {
            return ['enabled' => false, 'label' => '', 'levels' => [], 'options' => []];
        }

        return [
            'enabled' => true,
            'label'   => $field['label'],
            'levels'  => array_map(fn(array $level): array => ['label' => $level['label']], $field['levels']),
            'options' => array_map(fn(array $option): array => [
                'id'       => $option['id'],
                'parentid' => $option['parentid'],
                'value'    => $option['value'],
            ], $field['options']),
        ];
    }

    /**
     * Defines the return value structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'enabled' => new external_value(PARAM_BOOL, 'Whether a dropdown field is configured and available'),
            'label'   => new external_value(PARAM_TEXT, 'Label of the field, as shown to customers in Freshdesk'),
            'levels'  => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Label of this level'),
                ]),
                'Levels of the field, top level first'
            ),
            'options' => new external_multiple_structure(
                new external_single_structure([
                    'id'       => new external_value(PARAM_INT, 'Option id, unique within this response'),
                    'parentid' => new external_value(PARAM_INT, 'Id of the parent option, 0 for a top-level choice'),
                    'value'    => new external_value(PARAM_TEXT, 'Choice value, as configured in Freshdesk'),
                ]),
                'All choices at every level'
            ),
        ]);
    }
}
