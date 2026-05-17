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
 * Session-scoped blocker/mailbox state for continuation wait requests.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\local\wbagent;

use cache;

/**
 * Store and retrieve continuation wait state in session cache.
 */
class continuation_wait_state {
    /**
     * Build a cache key for one user/thread scope.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @return string
     */
    private static function make_key(int $userid, int $cmid, int $threadid): string {
        // Session caches aiwaitstate/aiwaitmailbox are configured with simplekeys=true.
        return 'u' . $userid . '_c' . $cmid . '_t' . $threadid;
    }

    /**
     * Set an active planner blocker marker.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @param int $runid
     * @param int $ttlseconds
     * @return void
     */
    public function set_blocker(int $userid, int $cmid, int $threadid, int $runid, int $ttlseconds = 60): void {
        $ttlseconds = max(1, min(60, $ttlseconds));
        $now = time();
        $payload = [
            'userid' => $userid,
            'cmid' => $cmid,
            'threadid' => $threadid,
            'runid' => $runid,
            'startedat' => $now,
            'expiresat' => $now + $ttlseconds,
        ];

        $cache = cache::make('mod_booking', 'aiwaitstate');
        $cache->set(self::make_key($userid, $cmid, $threadid), $payload);
    }

    /**
     * Clear active planner blocker marker.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @return void
     */
    public function clear_blocker(int $userid, int $cmid, int $threadid): void {
        $cache = cache::make('mod_booking', 'aiwaitstate');
        $cache->delete(self::make_key($userid, $cmid, $threadid));
    }

    /**
     * Get current blocker payload when active.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @return array<string,mixed>|null
     */
    public function get_blocker(int $userid, int $cmid, int $threadid): ?array {
        $cache = cache::make('mod_booking', 'aiwaitstate');
        $key = self::make_key($userid, $cmid, $threadid);
        $payload = $cache->get($key);
        if (!is_array($payload)) {
            return null;
        }

        $expiresat = (int)($payload['expiresat'] ?? 0);
        if ($expiresat > 0 && $expiresat < time()) {
            $cache->delete($key);
            return null;
        }

        return $payload;
    }

    /**
     * Set mailbox payload for the waiting request.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @param array<string,mixed> $payload
     * @param int $ttlseconds
     * @return void
     */
    public function set_mailbox(int $userid, int $cmid, int $threadid, array $payload, int $ttlseconds = 60): void {
        $ttlseconds = max(1, min(60, $ttlseconds));
        $now = time();
        $payload['createdat'] = $now;
        $payload['expiresat'] = $now + $ttlseconds;

        $cache = cache::make('mod_booking', 'aiwaitmailbox');
        $cache->set(self::make_key($userid, $cmid, $threadid), $payload);
    }

    /**
     * Get mailbox payload if present and not expired.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @return array<string,mixed>|null
     */
    public function get_mailbox(int $userid, int $cmid, int $threadid): ?array {
        $cache = cache::make('mod_booking', 'aiwaitmailbox');
        $key = self::make_key($userid, $cmid, $threadid);
        $payload = $cache->get($key);
        if (!is_array($payload)) {
            return null;
        }

        $expiresat = (int)($payload['expiresat'] ?? 0);
        if ($expiresat > 0 && $expiresat < time()) {
            $cache->delete($key);
            return null;
        }

        return $payload;
    }

    /**
     * Clear mailbox payload.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @return void
     */
    public function clear_mailbox(int $userid, int $cmid, int $threadid): void {
        $cache = cache::make('mod_booking', 'aiwaitmailbox');
        $cache->delete(self::make_key($userid, $cmid, $threadid));
    }
}
