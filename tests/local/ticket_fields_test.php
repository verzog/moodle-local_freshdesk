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
     * Nested choices given as a list of one-key objects keep their names, not list positions.
     *
     * @return void
     */
    public function test_list_of_keyed_nested_choices(): void {
        $field = ticket_fields::normalise([
            'name' => 'cf_country',
            'label' => 'Country',
            'type' => 'nested_field',
            'choices' => [['usa' => [['texas' => ['austin', 'houston']], ['ohio' => []]]], ['canada' => []]],
            'nested_ticket_fields' => [
                ['name' => 'cf_state', 'label' => 'State', 'level' => 2],
                ['name' => 'cf_city', 'label' => 'City', 'level' => 3],
            ],
        ]);

        $this->assertSame(['usa', 'texas', 'austin', 'houston', 'ohio', 'canada'], array_column($field['options'], 'value'));
        $this->assertSame([1, 2, 3, 3, 2, 1], array_column($field['options'], 'level'));
        $this->assertSame(
            ['cf_country' => 'usa', 'cf_state' => 'texas', 'cf_city' => 'houston'],
            ticket_fields::map_selection($field, ['usa', 'texas', 'houston'])
        );
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
     * The status line reports a usable field with its number of top-level choices.
     *
     * @return void
     */
    public function test_describe_status_found(): void {
        $status = ticket_fields::describe_status(self::sample_fields(), 'Types of assistance required');

        $this->assertTrue($status['ok']);
        $this->assertStringContainsString('cf_types_of_assistance_required', $status['message']);
        $this->assertStringContainsString('with 3 choices', $status['message']);
    }

    /**
     * A field of an unsupported type (e.g. multi-select) is named, with its type.
     *
     * @return void
     */
    public function test_describe_status_unsupported_type(): void {
        $fields = array_merge(self::sample_fields(), [[
            'name' => 'cf_services',
            'label' => 'Services',
            'type' => 'custom_multi_select_dropdown',
            'choices' => ['A', 'B'],
        ]]);

        $status = ticket_fields::describe_status($fields, 'services');

        $this->assertFalse($status['ok']);
        $this->assertStringContainsString('custom_multi_select_dropdown', $status['message']);
    }

    /**
     * An unknown name lists the dropdown fields that do exist.
     *
     * @return void
     */
    public function test_describe_status_not_found_lists_dropdowns(): void {
        $status = ticket_fields::describe_status(self::sample_fields(), 'Type of help');

        $this->assertFalse($status['ok']);
        $this->assertStringContainsString('"Types of assistance required", "Area"', $status['message']);
        $this->assertStringNotContainsString('Subject"', $status['message']);
    }

    /**
     * A dropdown with no choices is reported as unusable.
     *
     * @return void
     */
    public function test_describe_status_no_choices(): void {
        $fields = [['name' => 'cf_empty', 'label' => 'Empty list', 'type' => 'custom_dropdown', 'choices' => []]];

        $status = ticket_fields::describe_status($fields, 'Empty list');

        $this->assertFalse($status['ok']);
        $this->assertStringContainsString('no choices', $status['message']);
    }

    /**
     * Without an API key nothing is requested and the status says what to set up first.
     *
     * @return void
     */
    public function test_diagnose_not_configured(): void {
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_freshdesk');
        set_config('api_key', '', 'local_freshdesk');

        $status = ticket_fields::diagnose('Types of assistance required');

        $this->assertFalse($status['ok']);
        $this->assertSame(get_string('categorystatus_noconnection', 'local_freshdesk'), $status['message']);
        $this->assertNull(ticket_fields::diagnose('  '));
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
