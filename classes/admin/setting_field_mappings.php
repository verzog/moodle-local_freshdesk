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
 * Admin setting for the extra ticket field mappings, with validation.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_freshdesk\admin;

use local_freshdesk\local\field_mappings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Textarea setting that refuses lines which are not "freshdesk_field = moodle_field".
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_field_mappings extends \admin_setting_configtextarea {
    /**
     * Validates every line, naming the ones that cannot be used.
     *
     * @param string $data The submitted text.
     * @return true|string True when valid, otherwise an error message.
     */
    public function validate($data) {
        $invalid = field_mappings::parse((string) $data)['invalid'];
        if ($invalid) {
            return get_string('field_mappings_invalid', 'local_freshdesk', implode(', ', $invalid));
        }
        return true;
    }
}
