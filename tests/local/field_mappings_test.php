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
 * Tests for mapping Freshdesk ticket fields to Moodle profile fields.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_freshdesk\local;

/**
 * Tests for mapping Freshdesk ticket fields to Moodle profile fields.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_freshdesk\local\field_mappings
 */
#[\PHPUnit\Framework\Attributes\CoversClass(field_mappings::class)]
final class field_mappings_test extends \advanced_testcase {
    /**
     * Valid lines are read, blank lines ignored, and unusable lines reported.
     *
     * @return void
     */
    public function test_parse(): void {
        $result = field_mappings::parse(
            "cf_imis_id = idnumber\n\n  phone=phone1  \ncf_secret = password\nnot a mapping\nSubject = department\n"
        );

        $this->assertSame(['cf_imis_id' => 'idnumber', 'phone' => 'phone1'], $result['mappings']);
        $this->assertSame(['cf_secret = password', 'not a mapping', 'Subject = department'], $result['invalid']);
    }

    /**
     * Values come from the user's profile; empty values are skipped and standard fields stay text.
     *
     * @return void
     */
    public function test_resolve_core_and_profile_fields(): void {
        $this->resetAfterTest();
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'membertype',
            'name' => 'Member type',
        ]);
        $user = $this->getDataGenerator()->create_user([
            'idnumber' => '204517',
            'department' => '',
            'profile_field_membertype' => 'Fellow',
        ]);
        set_config('field_mappings', "cf_imis_id = idnumber\ncf_member_type = profile_field_membertype\n" .
            "cf_department = department\nphone = idnumber", 'local_freshdesk');

        $result = field_mappings::resolve($user);

        $this->assertSame(['cf_imis_id' => 204517, 'cf_member_type' => 'Fellow'], $result['custom']);
        // A phone number stays text; the iMIS ID becomes a number (Freshdesk types unknown here).
        $this->assertSame(['phone' => '204517'], $result['standard']);
        // The empty department is reported, so the admin reason can name it.
        $this->assertSame(['cf_department' => 'department'], $result['empty']);
    }

    /**
     * A mapping to a profile field that does not exist is reported as missing, not empty.
     *
     * @return void
     */
    public function test_resolve_reports_missing_profile_field(): void {
        $this->resetAfterTest();
        set_config('field_mappings', 'cf_level = profile_field_nosuchfield', 'local_freshdesk');

        $result = field_mappings::resolve($this->getDataGenerator()->create_user());

        $this->assertSame(['cf_level' => 'profile_field_nosuchfield'], $result['missing']);
        $this->assertSame([], $result['empty']);
    }

    /**
     * Identifiers with leading zeros stay as text so no digits are lost.
     *
     * @return void
     */
    public function test_resolve_keeps_leading_zeros(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['idnumber' => '00123']);
        set_config('field_mappings', 'cf_imis_id = idnumber', 'local_freshdesk');

        $this->assertSame(['cf_imis_id' => '00123'], field_mappings::resolve($user)['custom']);
    }

    /**
     * When Freshdesk's field types are known, only number fields get numbers.
     *
     * @return void
     */
    public function test_typed_value_follows_freshdesk_field_type(): void {
        $types = ['cf_imis_id' => 'custom_number', 'cf_fee' => 'custom_decimal', 'cf_code' => 'custom_text'];

        $this->assertSame(204517, field_mappings::typed_value('204517', $types, 'cf_imis_id'));
        $this->assertSame(12.5, field_mappings::typed_value('12.5', $types, 'cf_fee'));
        $this->assertSame('204517', field_mappings::typed_value('204517', $types, 'cf_code'));
        $this->assertSame('204517', field_mappings::typed_value('204517', $types, 'cf_unknown'));
        $this->assertSame('00123', field_mappings::typed_value('00123', $types, 'cf_imis_id'));
    }

    /**
     * Field types are read from the cached Freshdesk ticket fields when resolving.
     *
     * @return void
     */
    public function test_resolve_uses_cached_field_types(): void {
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_freshdesk');
        set_config('portal_url', 'https://example.freshdesk.com', 'local_freshdesk');
        set_config('api_key', 'testkey', 'local_freshdesk');
        set_config('field_mappings', "cf_imis_id = idnumber\ncf_code = idnumber", 'local_freshdesk');
        \core_cache\cache::make('local_freshdesk', 'ticket_fields')->set(md5('https://example.freshdesk.com'), [
            ['name' => 'cf_imis_id', 'label' => 'iMIS ID', 'type' => 'custom_number'],
            ['name' => 'cf_code', 'label' => 'Code', 'type' => 'custom_text'],
        ]);
        $user = $this->getDataGenerator()->create_user(['idnumber' => '204517']);

        $this->assertSame(['cf_imis_id' => 204517, 'cf_code' => '204517'], field_mappings::resolve($user)['custom']);
    }

    /**
     * With no mappings nothing extra is sent.
     *
     * @return void
     */
    public function test_resolve_without_mappings(): void {
        $this->resetAfterTest();
        set_config('field_mappings', '', 'local_freshdesk');

        $this->assertSame(
            ['custom' => [], 'standard' => [], 'empty' => [], 'missing' => []],
            field_mappings::resolve($this->getDataGenerator()->create_user())
        );
    }
}
