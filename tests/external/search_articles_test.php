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
 * Tests for the search_articles external function.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_freshdesk\external;

use core_external\external_api;

/**
 * Tests for the search_articles external function.
 *
 * Outbound Freshdesk calls are never made: each test stops at a guard
 * (capability, configuration) or is served from the plugin cache.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_freshdesk\external\search_articles
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_freshdesk\external\search_articles::class)]
final class search_articles_test extends \advanced_testcase {
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
     * Cached results are returned without calling Freshdesk.
     *
     * @return void
     */
    public function test_returns_cached_results(): void {
        $articles = [['id' => 42, 'title' => 'Resetting your password', 'description_text' => 'How to reset it.']];
        $cache = \core_cache\cache::make('local_freshdesk', 'search_results');
        $cache->set(md5('password'), $articles);

        $result = search_articles::execute(' Password ');
        $result = external_api::clean_returnvalue(search_articles::execute_returns(), $result);

        $this->assertSame($articles, $result);
    }

    /**
     * A disabled plugin returns no results.
     *
     * @return void
     */
    public function test_disabled_returns_empty(): void {
        set_config('enabled', 0, 'local_freshdesk');

        $this->assertSame([], search_articles::execute('password'));
        $this->assertDebuggingCalled();
    }

    /**
     * A non-HTTPS portal URL is refused so the API key is never sent in clear text.
     *
     * @return void
     */
    public function test_insecure_portal_url_returns_empty(): void {
        set_config('portal_url', 'http://example.freshdesk.com', 'local_freshdesk');

        $this->assertSame([], search_articles::execute('password'));
        $this->assertDebuggingCalled();
    }

    /**
     * Users without local/freshdesk:use cannot search.
     *
     * @return void
     */
    public function test_requires_capability(): void {
        global $DB, $USER;

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability('local/freshdesk:use', CAP_PROHIBIT, $roleid, \core\context\system::instance()->id, true);
        $this->assertFalse(has_capability('local/freshdesk:use', \core\context\system::instance(), $USER));

        $this->expectException(\required_capability_exception::class);
        search_articles::execute('password');
    }
}
