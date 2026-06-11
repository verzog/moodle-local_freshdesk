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
 * External function to fetch a single Freshdesk knowledge base article server-side.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_freshdesk\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Proxies Freshdesk article retrieval to keep the API key server-side.
 *
 * @package    local_freshdesk
 * @copyright  2026 verzog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_article extends external_api {
    /**
     * Defines the parameters accepted by execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'articleid' => new external_value(PARAM_INT, 'Freshdesk article ID'),
        ]);
    }

    /**
     * Fetches a single Freshdesk knowledge base article server-side.
     *
     * The API key is read from plugin config and never exposed to the browser.
     *
     * @param int $articleid Freshdesk article ID.
     * @return array Article fields: id, title, description (HTML).
     */
    public static function execute(int $articleid): array {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $empty  = ['id' => 0, 'title' => '', 'description' => ''];
        $params = self::validate_parameters(self::execute_parameters(), ['articleid' => $articleid]);

        // The widget is exposed site-wide; validate the system context and require the
        // local/freshdesk:use capability so guest / unauthenticated sessions and any
        // role explicitly denied this capability cannot reach the proxy.
        $context = \core\context\system::instance();
        self::validate_context($context);
        require_capability('local/freshdesk:use', $context);

        $config    = get_config('local_freshdesk');
        $apikey    = (string) ($config->api_key ?? '');
        $portalurl = rtrim((string) ($config->portal_url ?? ''), '/');

        if (empty($config->enabled) || $apikey === '' || $portalurl === '') {
            debugging(
                'local_freshdesk: article fetch skipped — plugin disabled or portal URL / API key not configured.',
                DEBUG_DEVELOPER
            );
            return $empty;
        }

        // Reject non-HTTPS portal URLs to ensure the API key is never sent in clear text.
        if (stripos($portalurl, 'https://') !== 0) {
            debugging('local_freshdesk: article fetch skipped — portal URL must start with https://.', DEBUG_DEVELOPER);
            return $empty;
        }

        $curl = new \curl();
        // Bound the external request so a slow or hung Freshdesk endpoint never stalls a Moodle page.
        $curl->setopt([
            'CURLOPT_CONNECTTIMEOUT' => 5,
            'CURLOPT_TIMEOUT'        => 10,
        ]);
        $curl->setHeader([
            'Authorization: Basic ' . base64_encode($apikey . ':X'),
            'Content-Type: application/json',
        ]);

        $url          = $portalurl . '/api/v2/solutions/articles/' . (int) $params['articleid'];
        $responsebody = $curl->get($url);
        $info         = $curl->get_info();
        $httpcode     = (int) ($info['http_code'] ?? 0);

        if ($httpcode !== 200) {
            $failure = 'local_freshdesk: article fetch failed (HTTP ' . $httpcode . '): ' .
                substr((string) $responsebody, 0, 300);
            debugging($failure, DEBUG_DEVELOPER);
            return $empty;
        }

        $article = json_decode($responsebody, true);
        if (!is_array($article)) {
            return $empty;
        }

        return [
            'id'          => (int) ($article['id'] ?? 0),
            'title'       => (string) ($article['title'] ?? ''),
            'description' => (string) ($article['description'] ?? ''),
        ];
    }

    /**
     * Defines the return value structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id'          => new external_value(PARAM_INT, 'Article ID'),
            'title'       => new external_value(PARAM_TEXT, 'Article title'),
            'description' => new external_value(PARAM_RAW, 'Article HTML content'),
        ]);
    }
}
