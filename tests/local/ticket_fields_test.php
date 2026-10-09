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
 * Tests for reading the Freshdesk type-of-assistance field.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_freshdesk\local;

/**
 * Tests for reading the Freshdesk type-of-assistance field.
 *
 * The field definitions below mirror the shapes returned by the Freshdesk
 * GET /api/v2/ticket_fields endpoint.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_freshdesk\local\ticket_fields
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ticket_fields::class)]
final class ticket_fields_test extends \advanced_testcase {
    /**
     * Ticket fields as Freshdesk returns them: a text field, a simple dropdown and a nested field.
     *
     * @return array
     */
    private static function sample_fields(): array {
        return [
            ['name' => 'subject', 'label' => 'Subject', 'type' => 'default_subject'],
            [
                'name' => 'cf_types_of_assistance_required',
                'label' => 'Types of assistance required',
                'label_for_customers' => 'What do you need help with?',
                'type' => 'custom_dropdown',
                'choices' => ['Login problem', 'Course content', 'Certificate'],
            ],
            [
                'name' => 'cf_area',
                'label' => 'Area',
                'label_for_customers' => 'Area',
                'type' => 'nested_field',
                'choices' => [
                    'Technical' => ['Browser' => ['Firefox', 'Chrome'], 'Mobile app' => []],
                    'Billing' => ['Refund' => [], 'Invoice' => []],
                    'Other' => [],
                ],
                'nested_ticket_fields' => [
                    ['name' => 'cf_detail', 'label' => 'Detail', 'label_in_portal' => 'Detail', 'level' => 3],
                    ['name' => 'cf_topic', 'label' => 'Topic', 'label_in_portal' => 'Topic', 'level' => 2],
                ],
            ],
        ];
    }

    /**
     * A field is found by its label, case-insensitively, and shows the customer-facing label.
     *
     * @return void
     */
    public function test_find_simple_dropdown_by_label(): void {
        $field = ticket_fields::find_field(self::sample_fields(), '  types of ASSISTANCE required ');

        $this->assertSame('cf_types_of_assistance_required', $field['name']);
        $this->assertSame('What do you need help with?', $field['label']);
        $this->assertSame(['Login problem', 'Course content', 'Certificate'], array_column($field['options'], 'value'));
        $this->assertSame([0, 0, 0], array_column($field['options'], 'parentid'));
    }

    /**
     * Fields that are not dropdowns are never matched.
     *
     * @return void
     */
    public function test_non_dropdown_field_is_ignored(): void {
        $this->assertNull(ticket_fields::find_field(self::sample_fields(), 'Subject'));
        $this->assertNull(ticket_fields::find_field(self::sample_fields(), 'No such field'));
    }

    /**
     * Nested fields become one level per Freshdesk level, in level order, with parent links.
     *
     * @return void
     */
    public function test_nested_field_levels_and_options(): void {
        $field = ticket_fields::find_field(self::sample_fields(), 'cf_area');

        $this->assertSame(['cf_area', 'cf_topic', 'cf_detail'], array_column($field['levels'], 'name'));
        $technical = $field['options'][0];
        $this->assertSame(['Technical', 0, 1], [$technical['value'], $technical['parentid'], $technical['level']]);
        $browser = $field['options'][1];
        $this->assertSame(['Browser', $technical['id'], 2], [$browser['value'], $browser['parentid'], $browser['level']]);
        $this->assertCount(9, $field['options']);
    }

    /**
     * Choices given as objects with value and choices keys are also understood.
     *
     * @return void
     */
    public function test_object_shaped_choices(): void {
        $field = ticket_fields::normalise([
            'name' => 'cf_kind',
            'label' => 'Kind',
            'type' => 'custom_dropdown',
            'choices' => [['id' => 7, 'value' => 'Question'], ['id' => 8, 'value' => 'Problem']],
        ]);

        $this->assertSame(['Question', 'Problem'], array_column($field['options'], 'value'));
    }

    /**
     * A complete, valid choice maps to the Freshdesk field names to send.
     *
     * @return void
     */
    public function test_map_selection_valid(): void {
        $simple = ticket_fields::find_field(self::sample_fields(), 'cf_types_of_assistance_required');
        $nested = ticket_fields::find_field(self::sample_fields(), 'cf_area');

        $this->assertSame(
            ['cf_types_of_assistance_required' => 'Certificate'],
            ticket_fields::map_selection($simple, ['Certificate'])
        );
        $this->assertSame(
            ['cf_area' => 'Technical', 'cf_topic' => 'Browser', 'cf_detail' => 'Chrome'],
            ticket_fields::map_selection($nested, ['Technical', 'Browser', 'Chrome'])
        );
        // A choice without sub-choices is complete on its own.
        $this->assertSame(['cf_area' => 'Other'], ticket_fields::map_selection($nested, ['Other']));
    }

    /**
     * Unknown values, incomplete paths and over-long paths are rejected.
     *
     * @return void
     */
    public function test_map_selection_invalid(): void {
        $simple = ticket_fields::find_field(self::sample_fields(), 'cf_types_of_assistance_required');
        $nested = ticket_fields::find_field(self::sample_fields(), 'cf_area');

        $this->assertNull(ticket_fields::map_selection($simple, ['Something made up']));
        $this->assertNull(ticket_fields::map_selection($simple, []));
        $this->assertNull(ticket_fields::map_selection($nested, ['Technical']));
        $this->assertNull(ticket_fields::map_selection($nested, ['Billing', 'Browser']));
        $this->assertNull(ticket_fields::map_selection($simple, ['Certificate', 'Extra']));
    }

    /**
     * The configured field is read from the cache, so Freshdesk is not called on every request.
     *
     * @return void
     */
    public function test_get_configured_field_uses_cache(): void {
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_freshdesk');
        set_config('portal_url', 'https://example.freshdesk.com', 'local_freshdesk');
        set_config('api_key', 'testkey', 'local_freshdesk');
        set_config('category_field', 'Types of assistance required', 'local_freshdesk');
        \core_cache\cache::make('local_freshdesk', 'ticket_fields')
            ->set(md5('https://example.freshdesk.com'), self::sample_fields());

        $field = ticket_fields::get_configured_field();

        $this->assertSame('cf_types_of_assistance_required', $field['name']);
    }

    /**
     * With the setting empty there is no dropdown field.
     *
     * @return void
     */
    public function test_get_configured_field_not_set(): void {
        $this->resetAfterTest();
        set_config('category_field', '', 'local_freshdesk');

        $this->assertNull(ticket_fields::get_configured_field());
    }
}
