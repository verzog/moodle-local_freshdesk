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
 * Tests for the privacy provider.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_freshdesk\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\types\external_location;

/**
 * Tests for the privacy provider.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_freshdesk\privacy\provider
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_freshdesk\privacy\provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * The data sent to Freshdesk is declared as an external location.
     *
     * @return void
     */
    public function test_get_metadata(): void {
        $collection = provider::get_metadata(new collection('local_freshdesk'));
        $items = $collection->get_collection();

        $this->assertCount(1, $items);
        $this->assertInstanceOf(external_location::class, $items[0]);
        $this->assertSame('freshdesk', $items[0]->get_name());
        $this->assertArrayHasKey('email', $items[0]->get_privacy_fields());
    }

    /**
     * No personal data is held in Moodle, so no contexts are reported.
     *
     * @return void
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $this->assertCount(0, provider::get_contexts_for_userid($user->id));
    }
}
