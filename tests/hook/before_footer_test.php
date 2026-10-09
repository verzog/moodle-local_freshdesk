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
 * Tests for the before_footer hook callback.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_freshdesk\hook\output;

use core\hook\output\before_footer_html_generation;

/**
 * Tests for the before_footer hook callback.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_freshdesk\hook\output\before_footer
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_freshdesk\hook\output\before_footer::class)]
final class before_footer_test extends \advanced_testcase {
    /**
     * Runs the callback against a fresh page and returns the page's footer JavaScript.
     *
     * @return string
     */
    protected function run_callback(): string {
        global $PAGE;

        $PAGE = new \moodle_page();
        $PAGE->set_context(\core\context\system::instance());
        $PAGE->set_url(new \moodle_url('/'));
        before_footer::callback(new before_footer_html_generation($PAGE->get_renderer('core')));
        return $PAGE->requires->get_end_code();
    }

    /**
     * When enabled, the widget module is loaded with the configured colour.
     *
     * @return void
     */
    public function test_enabled_loads_widget(): void {
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_freshdesk');
        set_config('portal_url', 'https://example.freshdesk.com', 'local_freshdesk');
        set_config('widget_color', '#123456', 'local_freshdesk');
        $this->setUser($this->getDataGenerator()->create_user());

        $js = $this->run_callback();

        $this->assertStringContainsString('local_freshdesk/widget', $js);
        $this->assertStringContainsString('#123456', $js);
    }

    /**
     * When disabled, nothing is added to the page.
     *
     * @return void
     */
    public function test_disabled_adds_nothing(): void {
        $this->resetAfterTest();
        set_config('enabled', 0, 'local_freshdesk');

        $this->assertStringNotContainsString('local_freshdesk/widget', $this->run_callback());
    }

    /**
     * Without a portal URL the widget stays hidden.
     *
     * @return void
     */
    public function test_blank_portal_url_adds_nothing(): void {
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_freshdesk');
        set_config('portal_url', '', 'local_freshdesk');
        $this->setUser($this->getDataGenerator()->create_user());

        $this->assertStringNotContainsString('local_freshdesk/widget', $this->run_callback());
    }

    /**
     * A colour setting carrying anything other than a colour value falls back to the default.
     *
     * @return void
     */
    public function test_unsafe_colour_falls_back_to_default(): void {
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_freshdesk');
        set_config('portal_url', 'https://example.freshdesk.com', 'local_freshdesk');
        set_config('widget_color', 'red; background: url(x)', 'local_freshdesk');
        $this->setUser($this->getDataGenerator()->create_user());

        $js = $this->run_callback();

        $this->assertStringNotContainsString('url(x)', $js);
        $this->assertStringContainsString('#006B6B', $js);
    }
}
