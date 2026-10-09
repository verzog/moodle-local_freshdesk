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
 * Tests for the get_article external function.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_freshdesk\external;

/**
 * Tests for the get_article external function.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_freshdesk\external\get_article
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_freshdesk\external\get_article::class)]
final class get_article_test extends \advanced_testcase {
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
     * Without an API key nothing is fetched and an empty article is returned.
     *
     * @return void
     */
    public function test_unconfigured_returns_empty(): void {
        set_config('api_key', '  ', 'local_freshdesk');

        $this->assertSame(['id' => 0, 'title' => '', 'description' => ''], get_article::execute(42));
        $this->assertDebuggingCalled();
    }

    /**
     * A non-HTTPS portal URL is refused.
     *
     * @return void
     */
    public function test_insecure_portal_url_returns_empty(): void {
        set_config('portal_url', 'http://example.freshdesk.com', 'local_freshdesk');

        $this->assertSame(['id' => 0, 'title' => '', 'description' => ''], get_article::execute(42));
        $this->assertDebuggingCalled();
    }

    /**
     * Guests cannot fetch articles.
     *
     * @return void
     */
    public function test_guest_is_refused(): void {
        $this->setGuestUser();

        $this->expectException(\required_capability_exception::class);
        get_article::execute(42);
    }
}
