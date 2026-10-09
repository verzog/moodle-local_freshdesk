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
 * Tests for the get_ticket_options external function.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_freshdesk\external;

use core_external\external_api;

/**
 * Tests for the get_ticket_options external function.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_freshdesk\external\get_ticket_options
 */
#[\PHPUnit\Framework\Attributes\CoversClass(get_ticket_options::class)]
final class get_ticket_options_test extends \advanced_testcase {
    /**
     * Configures the plugin and logs in a user.
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
     * The configured field's label and choices are returned (served from the cache here).
     *
     * @return void
     */
    public function test_returns_choices(): void {
        set_config('category_field', 'cf_help', 'local_freshdesk');
        \core_cache\cache::make('local_freshdesk', 'ticket_fields')->set(md5('https://example.freshdesk.com'), [[
            'name' => 'cf_help',
            'label' => 'Types of assistance required',
            'type' => 'custom_dropdown',
            'choices' => ['Login problem', 'Certificate'],
        ]]);

        $result = external_api::clean_returnvalue(get_ticket_options::execute_returns(), get_ticket_options::execute());

        $this->assertTrue($result['enabled']);
        $this->assertSame('Types of assistance required', $result['label']);
        $this->assertSame(['Login problem', 'Certificate'], array_column($result['options'], 'value'));
    }

    /**
     * Without the setting the form keeps its free-text subject.
     *
     * @return void
     */
    public function test_disabled_when_not_configured(): void {
        set_config('category_field', '', 'local_freshdesk');

        $result = get_ticket_options::execute();

        $this->assertFalse($result['enabled']);
        $this->assertSame([], $result['options']);
    }

    /**
     * Guests cannot read the choices.
     *
     * @return void
     */
    public function test_guest_is_refused(): void {
        $this->setGuestUser();

        $this->expectException(\required_capability_exception::class);
        get_ticket_options::execute();
    }
}
