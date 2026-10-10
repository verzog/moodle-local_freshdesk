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
 * Tests for the submit_ticket external function.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_freshdesk\external;

/**
 * Tests for the submit_ticket external function.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_freshdesk\external\submit_ticket
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_freshdesk\external\submit_ticket::class)]
final class submit_ticket_test extends \advanced_testcase {
    /**
     * Configures the plugin with a valid-looking portal and logs in a user.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_freshdesk');
        set_config('portal_url', 'https://example.freshdesk.com', 'local_freshdesk');
        set_config('api_key', 'testkey', 'local_freshdesk');
        $this->setUser($this->getDataGenerator()->create_user());
    }

    /**
     * Submitting while the plugin is disabled fails with the plugin's error string.
     *
     * @return void
     */
    public function test_disabled_throws(): void {
        set_config('enabled', 0, 'local_freshdesk');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('errorsubmitting', 'local_freshdesk'));
        submit_ticket::execute('Subject', 'Message', 'https://example.com/', '', '');
    }

    /**
     * A non-HTTPS portal URL is refused so the API key is never sent in clear text.
     *
     * @return void
     */
    public function test_insecure_portal_url_throws(): void {
        set_config('portal_url', 'http://example.freshdesk.com', 'local_freshdesk');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('errorsubmitting', 'local_freshdesk'));
        submit_ticket::execute('Subject', 'Message', 'https://example.com/', '', '');
    }

    /**
     * A type of assistance that is not a current Freshdesk choice is rejected before anything is sent.
     *
     * @return void
     */
    public function test_invalid_category_throws(): void {
        set_config('category_field', 'cf_help', 'local_freshdesk');
        \core_cache\cache::make('local_freshdesk', 'ticket_fields')->set(md5('https://example.freshdesk.com'), [[
            'name' => 'cf_help',
            'label' => 'Types of assistance required',
            'type' => 'custom_dropdown',
            'choices' => ['Login problem', 'Certificate'],
        ]]);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('errorsubmitting', 'local_freshdesk'));
        submit_ticket::execute('Subject', 'Message', 'https://example.com/', '', '', '', ['Made-up choice']);
    }

    /**
     * Site administrators get the reason back instead of only the generic error.
     *
     * @return void
     */
    public function test_admin_receives_reason(): void {
        $this->setAdminUser();
        set_config('enabled', 0, 'local_freshdesk');

        $result = submit_ticket::execute('Subject', 'Message', 'https://example.com/', '', '');
        $result = \core_external\external_api::clean_returnvalue(submit_ticket::execute_returns(), $result);

        $this->assertFalse($result['success']);
        $this->assertSame('Plugin disabled or portal URL / API key not configured.', $result['errordetail']);
    }

    /**
     * Freshdesk validation errors are summarised field by field.
     *
     * @return void
     */
    public function test_describe_freshdesk_error(): void {
        $body = '{"description":"Validation failed","errors":[{"field":"custom_fields.cf_imis_id",' .
            '"message":"It should be a/an Integer","code":"missing_field"}]}';

        $this->assertSame(
            'Freshdesk returned HTTP 400: Validation failed — ' .
                'custom_fields.cf_imis_id: It should be a/an Integer [missing_field]',
            submit_ticket::describe_freshdesk_error(400, $body)
        );
        $this->assertSame(
            'Freshdesk returned HTTP 401: Unauthorised',
            submit_ticket::describe_freshdesk_error(401, 'Unauthorised')
        );
        $this->assertSame('Could not reach Freshdesk: timed out', submit_ticket::describe_freshdesk_error(0, '', 'timed out'));
    }

    /**
     * Guests cannot submit tickets.
     *
     * @return void
     */
    public function test_guest_is_refused(): void {
        $this->setGuestUser();

        $this->expectException(\required_capability_exception::class);
        submit_ticket::execute('Subject', 'Message', 'https://example.com/', '', '');
    }
}
