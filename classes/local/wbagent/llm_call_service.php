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
 * Centralized LLM invocation wrapper with mandatory debug logging.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\local\wbagent;

use context_module;
use core\di;
use core_ai\aiactions\explain_text;
use core_ai\aiactions\generate_text;
use core_ai\aiactions\summarise_text;
use core_ai\manager as ai_manager;

/**
 * Provides one entry point for all model calls in the booking agent.
 */
class llm_call_service {
    /** @var conversation_store */
    private conversation_store $store;

    /**
     * Constructor.
     *
     * @param conversation_store $store
     */
    public function __construct(conversation_store $store) {
        $this->store = $store;
    }

    /**
     * Invoke a core_ai action class with guaranteed debug logging.
     *
     * @param int $threadid
     * @param int $cmid
     * @param int $userid
     * @param string $source
     * @param string $prompt
     * @param string $actionclass
     * @return array{success:bool,rawcontent:string,errormessage:string,errorcode:int,errorname:string}
     */
    public function invoke(
        int $threadid,
        int $cmid,
        int $userid,
        string $source,
        string $prompt,
        string $actionclass = generate_text::class
    ): array {
        $rawcontent = '';
        $errormessage = '';
        $errorcode = 0;
        $errorname = '';
        $success = false;

        try {
            $context = context_module::instance($cmid);
            $manager = di::get(ai_manager::class);

            if ($actionclass === summarise_text::class) {
                $action = new summarise_text(
                    contextid: $context->id,
                    userid: $userid,
                    prompttext: $prompt,
                );
            } else if ($actionclass === explain_text::class) {
                $action = new explain_text(
                    contextid: $context->id,
                    userid: $userid,
                    prompttext: $prompt,
                );
            } else {
                $action = new generate_text(
                    contextid: $context->id,
                    userid: $userid,
                    prompttext: $prompt,
                );
            }

            $response = $manager->process_action($action);
            $rawcontent = (string)($response->get_response_data()['generatedcontent'] ?? '');
            $success = (bool)$response->get_success();
            $errormessage = (string)($response->get_errormessage() ?? '');
            $errorcode = (int)$response->get_errorcode();
            $errorname = (string)$response->get_error();
        } catch (\Throwable $e) {
            $success = false;
            $errormessage = $e->getMessage();
            $errorcode = (int)$e->getCode();
            $errorname = '';
        }

        llm_debug_logger::log_exchange_always(
            $this->store,
            $threadid,
            $cmid,
            $userid,
            $source,
            $prompt,
            $rawcontent,
            $success,
            $errormessage
        );

        return [
            'success' => $success,
            'rawcontent' => $rawcontent,
            'errormessage' => $errormessage,
            'errorcode' => $errorcode,
            'errorname' => $errorname,
        ];
    }
}
