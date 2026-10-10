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
 * Admin setting for the type-of-assistance field, with a live status line.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_freshdesk\admin;

use local_freshdesk\local\ticket_fields;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Text setting that shows, beneath it, whether Freshdesk has a usable field with that name.
 *
 * The check only runs when the setting is displayed (the plugin's settings page
 * or an admin search that matches it), so other admin pages are not slowed down.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_category_field extends \admin_setting_configtext {
    /**
     * Returns the setting's HTML with the Freshdesk status added to its description.
     *
     * @param mixed $data Current value of the setting.
     * @param string $query Admin search query, if any.
     * @return string
     */
    public function output_html($data, $query = '') {
        $status = ticket_fields::diagnose((string) $data);
        if ($status !== null) {
            $this->description .= \html_writer::div(
                s($status['message']),
                'alert ' . ($status['ok'] ? 'alert-success' : 'alert-warning') . ' mt-2 mb-0'
            );
        }
        return parent::output_html($data, $query);
    }
}
