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
 * Privacy provider for local_freshdesk.
 *
 * The plugin keeps no personal data inside Moodle's own database. When a
 * user submits a support ticket the following fields are transmitted to
 * the external Freshdesk service: name, email, Moodle username, user ID,
 * profile URL, current course name, page URL, role label, ticket message
 * and an optional screenshot. Retention of that data is governed by
 * Freshdesk's privacy policy.
 *
 * To remain explicit, this provider implements the metadata provider (to
 * declare the external data flow) and the request providers (to confirm
 * that no personal data is held locally and there is therefore nothing to
 * export, list or delete from Moodle).
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_freshdesk\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider declaring data sent to the external Freshdesk service.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describes the personal data this plugin sends to external systems.
     *
     * No data is stored in Moodle's own database; all personal data is
     * transmitted to the Freshdesk support platform when a ticket is submitted.
     *
     * @param collection $collection Metadata collection to populate.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'freshdesk',
            [
                'email'       => 'privacy:metadata:freshdesk:email',
                'name'        => 'privacy:metadata:freshdesk:name',
                'username'    => 'privacy:metadata:freshdesk:username',
                'userid'      => 'privacy:metadata:freshdesk:userid',
                'profileurl'  => 'privacy:metadata:freshdesk:profileurl',
                'coursename'  => 'privacy:metadata:freshdesk:coursename',
                'pageurl'     => 'privacy:metadata:freshdesk:pageurl',
                'userrole'    => 'privacy:metadata:freshdesk:userrole',
                'message'     => 'privacy:metadata:freshdesk:message',
                'assistancetype' => 'privacy:metadata:freshdesk:assistancetype',
                'screenshot'  => 'privacy:metadata:freshdesk:screenshot',
            ],
            'privacy:metadata:freshdesk'
        );

        return $collection;
    }

    /**
     * Returns the list of contexts that contain user data for the given user.
     *
     * The plugin stores no personal data in Moodle, so the contextlist is
     * always empty.
     *
     * @param int $userid The user to search for.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Adds users with data in a given context to the supplied userlist.
     *
     * The plugin stores no personal data in Moodle, so the userlist is left
     * untouched.
     *
     * @param userlist $userlist The userlist to populate.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        // The plugin holds no per-user data in Moodle's database.
    }

    /**
     * Exports all user data for the supplied approved contextlist.
     *
     * The plugin stores no personal data in Moodle, so there is nothing to
     * export.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        // The plugin holds no per-user data in Moodle's database.
    }

    /**
     * Deletes all data for all users in the specified context.
     *
     * The plugin stores no personal data in Moodle, so there is nothing to
     * delete.
     *
     * @param \context $context The context to delete data within.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        // The plugin holds no per-user data in Moodle's database.
    }

    /**
     * Deletes all user data for the supplied approved contextlist.
     *
     * The plugin stores no personal data in Moodle, so there is nothing to
     * delete.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // The plugin holds no per-user data in Moodle's database.
    }

    /**
     * Deletes all user data for the supplied approved userlist.
     *
     * The plugin stores no personal data in Moodle, so there is nothing to
     * delete.
     *
     * @param approved_userlist $userlist The approved context and users to delete information for.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        // The plugin holds no per-user data in Moodle's database.
    }
}
