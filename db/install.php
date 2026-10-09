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
 * Runs once when the plugin is first installed.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

/**
 * Sets sensible defaults in the plugin config table on first install.
 *
 * When migrating from the previous component name (local_freshdeskwidget),
 * existing settings are copied across automatically so the API key and
 * portal URL do not need to be re-entered.
 *
 * @return bool
 */
function xmldb_local_freshdesk_install(): bool {
    // Migrate settings from the old component name if they exist.
    $oldconfig = get_config('local_freshdeskwidget');
    if (isset($oldconfig->portal_url)) {
        foreach ((array) $oldconfig as $name => $value) {
            // The old plugin's version number must not overwrite this plugin's version.
            if ($name === 'version') {
                continue;
            }
            set_config($name, $value, 'local_freshdesk');
        }
        return true;
    }

    // Fresh install: set sensible defaults.
    set_config('enabled', 1, 'local_freshdesk');
    set_config('portal_url', 'https://thefeaturecreep.freshdesk.com', 'local_freshdesk');
    set_config('api_key', '', 'local_freshdesk');
    set_config('widget_color', '#006B6B', 'local_freshdesk');
    set_config('hide_for_admins', 0, 'local_freshdesk');

    return true;
}
