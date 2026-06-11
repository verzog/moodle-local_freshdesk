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
 * Admin settings for the Freshdesk support widget plugin.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_freshdesk',
        get_string('pluginname', 'local_freshdesk')
    );

    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_freshdesk/enabled',
        get_string('enabled', 'local_freshdesk'),
        get_string('enabled_desc', 'local_freshdesk'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_freshdesk/portal_url',
        get_string('portal_url', 'local_freshdesk'),
        get_string('portal_url_desc', 'local_freshdesk'),
        'https://thefeaturecreep.freshdesk.com',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_freshdesk/api_key',
        get_string('api_key', 'local_freshdesk'),
        get_string('api_key_desc', 'local_freshdesk'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_freshdesk/ticket_type',
        get_string('ticket_type', 'local_freshdesk'),
        get_string('ticket_type_desc', 'local_freshdesk'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_freshdesk/group_id',
        get_string('group_id', 'local_freshdesk'),
        get_string('group_id_desc', 'local_freshdesk'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_freshdesk/responder_id',
        get_string('responder_id', 'local_freshdesk'),
        get_string('responder_id_desc', 'local_freshdesk'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_freshdesk/widget_color',
        get_string('widget_color', 'local_freshdesk'),
        get_string('widget_color_desc', 'local_freshdesk'),
        '#006B6B',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_freshdesk/hide_for_admins',
        get_string('hide_for_admins', 'local_freshdesk'),
        get_string('hide_for_admins_desc', 'local_freshdesk'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_freshdesk/widget_icon',
        get_string('widget_icon', 'local_freshdesk'),
        get_string('widget_icon_desc', 'local_freshdesk'),
        '🎓',
        PARAM_TEXT
    ));
}
