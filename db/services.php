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
 * External functions registered for local_freshdesk.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_freshdesk_submit_ticket' => [
        'classname'     => \local_freshdesk\external\submit_ticket::class,
        'description'   => 'Submit a Freshdesk support ticket on behalf of the current user.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/freshdesk:use',
    ],
    'local_freshdesk_search_articles' => [
        'classname'     => \local_freshdesk\external\search_articles::class,
        'description'   => 'Search Freshdesk knowledge base articles (server-side proxy, API key stays on server).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/freshdesk:use',
    ],
    'local_freshdesk_get_article' => [
        'classname'     => \local_freshdesk\external\get_article::class,
        'description'   => 'Fetch a single Freshdesk knowledge base article (server-side proxy, API key stays on server).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/freshdesk:use',
    ],
];
