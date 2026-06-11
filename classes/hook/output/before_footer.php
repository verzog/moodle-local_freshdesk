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
 * Hook callback to inject the Freshdesk widget before the page footer.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_freshdesk\hook\output;

use core\context\course as course_context;
use core\hook\output\before_footer_html_generation;

/**
 * Injects Freshdesk widget configuration and AMD module into every Moodle page.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_footer {
    /**
     * Callback for before_footer_html_generation hook.
     *
     * Passes Freshdesk configuration to JavaScript and initialises the AMD
     * widget module. Skipped when the plugin is disabled or the current user
     * is an admin with the hide-for-admins setting enabled.
     *
     * @param before_footer_html_generation $hook The hook instance.
     * @return void
     */
    public static function callback(before_footer_html_generation $hook): void {
        global $USER, $PAGE, $COURSE;

        $isloggedin = isloggedin() && !isguestuser();
        $isadmin    = is_siteadmin();

        $config = get_config('local_freshdesk');

        if (empty($config->enabled)) {
            return;
        }

        if ($isadmin && !empty($config->hide_for_admins)) {
            return;
        }

        // Two render modes. With local/freshdesk:use (logged-in users by default)
        // the full widget renders: KB search, article viewer, and the contact
        // form. Everyone else (guests, not-logged-in, capability denied) gets a
        // pass-through "Get Help" link to the configured Freshdesk portal — no
        // AJAX endpoints are called in that mode.
        $hascap = has_capability('local/freshdesk:use', \core\context\system::instance());

        // Determine role from course capability (falls back to site context on non-course pages).
        $courseid  = (int) $COURSE->id;
        $context   = $courseid > 1 ? course_context::instance($courseid) : \core\context\system::instance();
        $isstaff   = has_capability('moodle/course:manageactivities', $context);
        $rolelabel = $isstaff ? 'Staff' : 'Student';

        // Resolve user details, empty strings for guests.
        $useremail      = $isloggedin ? $USER->email : '';
        $username       = $isloggedin ? fullname($USER) : '';
        $userusername   = $isloggedin ? $USER->username : '';
        $userprofileurl = $isloggedin
            ? (new \moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false)
            : '';
        $currenturl = $PAGE->url->out(false);
        $coursename = $courseid > 1 ? format_string($COURSE->fullname) : '';

        // Load Freshdesk settings from plugin config.
        // The API key is intentionally NOT passed to JavaScript; all Freshdesk
        // API calls are proxied through server-side external functions.
        $portalurl   = rtrim((string) ($config->portal_url ?? 'https://thefeaturecreep.freshdesk.com'), '/');
        $widgetcolor = (string) ($config->widget_color ?? '#006B6B');
        $widgeticon  = (string) ($config->widget_icon ?? '🎓');

        // Config is passed as a js_call_amd argument rather than via
        // data_for_js(), which is deprecated and scheduled for removal.
        $PAGE->requires->js_call_amd('local_freshdesk/widget', 'init', [[
            'portalUrl'      => $portalurl,
            'userEmail'      => $useremail,
            'userName'       => $username,
            'userUsername'   => $userusername,
            'userProfileUrl' => $userprofileurl,
            'currentUrl'     => $currenturl,
            'courseName'     => $coursename,
            'userRole'       => $rolelabel,
            'isLoggedIn'     => $isloggedin,
            'hasCapability'  => $hascap,
            'widgetColor'    => $widgetcolor,
            'widgetIcon'     => $widgeticon,
        ]]);
    }
}
