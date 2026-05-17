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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_booking\local\wbagent;

/**
 * Stores thread-scoped confirmation allowlists in user preferences.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class confirmation_session_allow_service {
    /** Preference key that stores session allowlist entries. */
    private const USER_PREFERENCE_KEY = 'mod_booking_ai_confirmation_session_allowlist';

    /** Default lifetime for a thread allowlist entry in seconds. */
    private const DEFAULT_TTL = 3600;

    /**
     * Allow confirmations for a specific thread for the current session window.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @param int|null $expiresat
     * @return void
     */
    public function allow_thread(int $userid, int $cmid, int $threadid, ?int $expiresat = null): void {
        $allowlist = $this->get_allowlist($userid);
        $key = $this->make_key($cmid, $threadid);
        $allowlist[$key] = [
            'cmid' => $cmid,
            'threadid' => $threadid,
            'expiresat' => $expiresat ?? (time() + self::DEFAULT_TTL),
        ];
        $this->save_allowlist($userid, $allowlist);
    }

    /**
     * Check whether confirmations may be auto-approved for a thread.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @return bool
     */
    public function is_thread_allowed(int $userid, int $cmid, int $threadid): bool {
        $allowlist = $this->get_allowlist($userid);
        $key = $this->make_key($cmid, $threadid);
        return !empty($allowlist[$key]);
    }

    /**
     * Remove a thread from the allowlist.
     *
     * @param int $userid
     * @param int $cmid
     * @param int $threadid
     * @return void
     */
    public function clear_thread(int $userid, int $cmid, int $threadid): void {
        $allowlist = $this->get_allowlist($userid);
        $key = $this->make_key($cmid, $threadid);
        unset($allowlist[$key]);
        $this->save_allowlist($userid, $allowlist);
    }

    /**
     * Build a stable preference key for a thread.
     *
     * @param int $cmid
     * @param int $threadid
     * @return string
     */
    private function make_key(int $cmid, int $threadid): string {
        return $cmid . ':' . $threadid;
    }

    /**
     * Load and prune the allowlist from user preferences.
     *
     * @param int $userid
     * @return array<string,array<string,int>>
     */
    private function get_allowlist(int $userid): array {
        $raw = (string)get_user_preferences(self::USER_PREFERENCE_KEY, '', $userid);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $now = time();
        $allowlist = [];
        foreach ($decoded as $key => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $cmid = (int)($entry['cmid'] ?? 0);
            $threadid = (int)($entry['threadid'] ?? 0);
            $expiresat = (int)($entry['expiresat'] ?? 0);
            if ($cmid <= 0 || $threadid <= 0 || $expiresat <= $now) {
                continue;
            }

            $allowlist[(string)$key] = [
                'cmid' => $cmid,
                'threadid' => $threadid,
                'expiresat' => $expiresat,
            ];
        }

        if ($allowlist !== $decoded) {
            $this->save_allowlist($userid, $allowlist);
        }

        return $allowlist;
    }

    /**
     * Persist the allowlist in user preferences.
     *
     * @param int $userid
     * @param array<string,array<string,int>> $allowlist
     * @return void
     */
    private function save_allowlist(int $userid, array $allowlist): void {
        set_user_preference(self::USER_PREFERENCE_KEY, json_encode($allowlist), $userid);
    }
}
