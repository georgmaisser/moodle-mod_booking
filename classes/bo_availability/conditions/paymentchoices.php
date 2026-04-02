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
 * Pre-page condition allowing users to choose a payment method.
 *
 * @package mod_booking
 * @copyright 2026 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\bo_availability\conditions;

use cache;
use mod_booking\bo_availability\bo_condition;
use mod_booking\booking_option_settings;
use mod_booking\singleton_service;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/booking/lib.php');

/**
 * Payment choices condition.
 */
class paymentchoices implements bo_condition {
    /** @var string Credits payment method key. */
    public const METHOD_CREDITS = 'credits';

    /** @var string Subscription payment method key. */
    public const METHOD_SUBSCRIPTION = 'subscription';

    /** @var string Shopping cart payment method key. */
    public const METHOD_SHOPPINGCART = 'shoppingcart';

    /** @var int $id Standard conditions have hardcoded ids. */
    public $id = MOD_BOOKING_BO_COND_PAYMENTCHOICES;

    /** @var bool $overwrittenbybillboard Indicates if the condition can be overwritten by the billboard. */
    public $overwrittenbybillboard = false;

    /**
     * Get the condition id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Needed to see if class can take JSON.
     * @return bool
     */
    public function is_json_compatible(): bool {
        return false;
    }

    /**
     * Needed to see if it shows up in mform.
     * @return bool
     */
    public function is_shown_in_mform(): bool {
        return false;
    }

    /**
     * Returns the name of the condition.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('bocondpaymentchoices', 'mod_booking');
    }

    /**
     * Returns whether the condition is skippable or not.
     *
     * @return bool
     */
    public function is_skippable(): bool {
        return false;
    }

    /**
     * Determines whether this condition currently blocks the flow.
     *
     * @param booking_option_settings $settings
     * @param int $userid
     * @param bool $not
     * @return bool
     */
    public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool {
        $isavailable = true;

        if (empty(get_config('booking', 'paymentchoiceenabled'))) {
            return true;
        }

        $methods = self::get_applicable_methods($settings, $userid);
        if (count($methods) > 1) {
            $isavailable = false;
        }

        if ($not) {
            $isavailable = !$isavailable;
        }

        return $isavailable;
    }

    /**
     * Each function can return additional sql.
     *
     * @param int $userid
     * @param array $params
     * @return array
     */
    public function return_sql(int $userid = 0, &$params = []): array {
        return ['', '', '', [], ''];
    }

    /**
     * Hard block is not needed for this pre-page helper condition.
     *
     * @param booking_option_settings $settings
     * @param int $userid
     * @return bool
     */
    public function hard_block(booking_option_settings $settings, $userid): bool {
        return false;
    }

    /**
     * Returns condition description payload.
     *
     * @param booking_option_settings $settings
     * @param int|null $userid
     * @param bool $full
     * @param bool $not
     * @return array
     */
    public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array {
        $isavailable = $this->is_available($settings, (int)$userid, $not);
        $description = !$isavailable ? get_string('choosepaymentmethod', 'mod_booking') : '';

        return [$isavailable, $description, MOD_BOOKING_BO_PREPAGE_PREBOOK, MOD_BOOKING_BO_BUTTON_INDIFFERENT];
    }

    /**
     * Only customizable functions need to return their necessary form elements.
     *
     * @param MoodleQuickForm $mform
     * @param int $optionid
     * @return void
     */
    public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0) {
        // Do nothing.
    }

    /**
     * Render pre-page content.
     *
     * @param int $optionid
     * @param int $userid
     * @return array
     */
    public function render_page(int $optionid, int $userid = 0) {
        $dataarray['data'] = [
            'optionid' => $optionid,
            'userid' => $userid,
        ];

        return [
            'data' => [$dataarray],
            'template' => 'mod_booking/condition/paymentchoices',
            'buttontype' => 1,
        ];
    }

    /**
     * This condition does not provide booking buttons.
     *
     * @param booking_option_settings $settings
     * @param int $userid
     * @param bool $full
     * @param bool $not
     * @param bool $fullwidth
     * @return array
     */
    public function render_button(
        booking_option_settings $settings,
        int $userid = 0,
        bool $full = false,
        bool $not = false,
        bool $fullwidth = true
    ): array {
        return ['', []];
    }

    /**
     * Returns configured payment methods metadata.
     *
     * @return array
     */
    public static function get_payment_method_definitions(): array {
        return [
            self::METHOD_CREDITS => [
                'setting' => 'paymentchoicecredits',
                'label' => 'paymentmethodcredits',
            ],
            self::METHOD_SUBSCRIPTION => [
                'setting' => 'paymentchoicesubscription',
                'label' => 'paymentmethodsubscription',
            ],
            self::METHOD_SHOPPINGCART => [
                'setting' => 'paymentchoiceshoppingcart',
                'label' => 'paymentmethodshoppingcart',
            ],
        ];
    }

    /**
     * Return all payment methods that apply for this user and option.
     *
     * @param booking_option_settings $settings
     * @param int $userid
     * @return array
     */
    public static function get_applicable_methods(booking_option_settings $settings, int $userid): array {
        $methods = [];

        foreach (self::get_payment_method_definitions() as $method => $definition) {
            if (empty(get_config('booking', $definition['setting']))) {
                continue;
            }

            if (!self::is_method_applicable($method, $settings, $userid)) {
                continue;
            }

            $methods[$method] = get_string($definition['label'], 'mod_booking');
        }

        return $methods;
    }

    /**
     * Returns selected payment method from cache.
     *
     * @param int $userid
     * @param int $optionid
     * @return string|null
     */
    public static function get_active_payment_choice(int $userid, int $optionid): ?string {
        if (empty($userid) || empty($optionid)) {
            return null;
        }

        $cache = cache::make('mod_booking', 'conditionforms');
        $cachekey = self::get_cache_key($userid, $optionid);
        $data = $cache->get($cachekey);

        if (empty($data->payment_choice) || !is_string($data->payment_choice)) {
            return null;
        }

        $method = trim($data->payment_choice);
        $definitions = self::get_payment_method_definitions();

        return array_key_exists($method, $definitions) ? $method : null;
    }

    /**
     * Persist selected payment method in cache.
     *
     * @param int $userid
     * @param int $optionid
     * @param string $method
     * @return void
     */
    public static function set_active_payment_choice(int $userid, int $optionid, string $method): void {
        if (empty($userid) || empty($optionid)) {
            return;
        }

        $definitions = self::get_payment_method_definitions();
        if (!array_key_exists($method, $definitions)) {
            return;
        }

        $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
        if (!self::is_method_applicable($method, $settings, $userid)) {
            return;
        }

        $cache = cache::make('mod_booking', 'conditionforms');
        $cache->set(self::get_cache_key($userid, $optionid), (object)[
            'id' => $optionid,
            'userid' => $userid,
            'payment_choice' => $method,
        ]);
    }

    /**
     * Remove selected payment method from cache.
     *
     * @param int $userid
     * @param int $optionid
     * @return void
     */
    public static function clear_active_payment_choice(int $userid, int $optionid): void {
        if (empty($userid) || empty($optionid)) {
            return;
        }

        $cache = cache::make('mod_booking', 'conditionforms');
        $cache->delete(self::get_cache_key($userid, $optionid));
    }

    /**
     * Returns cache key.
     *
     * @param int $userid
     * @param int $optionid
     * @return string
     */
    private static function get_cache_key(int $userid, int $optionid): string {
        return $userid . '_' . $optionid . '_paymentchoice';
    }

    /**
     * Checks if a specific method applies.
     *
     * @param string $method
     * @param booking_option_settings $settings
     * @param int $userid
     * @return bool
     */
    private static function is_method_applicable(string $method, booking_option_settings $settings, int $userid): bool {
        switch ($method) {
            case self::METHOD_CREDITS:
                return self::is_credits_applicable($settings, $userid);
            case self::METHOD_SUBSCRIPTION:
                return self::is_subscription_applicable($settings, $userid);
            case self::METHOD_SHOPPINGCART:
                return self::is_shoppingcart_applicable($settings);
            default:
                return false;
        }
    }

    /**
     * Checks if credits payment applies.
     *
     * @param booking_option_settings $settings
     * @param int $userid
     * @return bool
     */
    private static function is_credits_applicable(booking_option_settings $settings, int $userid): bool {
        global $USER;

        if (empty(get_config('booking', 'bookwithcreditsactive'))) {
            return false;
        }

        $profilefield = get_config('booking', 'bookwithcreditsprofilefield');
        if (empty($profilefield) || empty($settings->credits)) {
            return false;
        }

        if (empty($settings->jsonobject->useprice)) {
            return true;
        }

        if (!empty($userid) && $userid !== $USER->id) {
            $user = singleton_service::get_instance_of_user($userid);
            profile_load_custom_fields($user);
        } else {
            $user = $USER;
        }

        $key = 'profile_field_' . $profilefield;
        $usercredit = $user->{$key} ?? $user->profile[$profilefield] ?? 0;

        return $settings->credits <= $usercredit;
    }

    /**
     * Checks if subscription payment applies.
     *
     * @param booking_option_settings $settings
     * @param int $userid
     * @return bool
     */
    private static function is_subscription_applicable(booking_option_settings $settings, int $userid): bool {
        if (empty($settings->jsonobject->useprice)) {
            return false;
        }

        return self::has_active_subscription($userid);
    }

    /**
     * Checks if shopping cart payment applies.
     *
     * @param booking_option_settings $settings
     * @return bool
     */
    private static function is_shoppingcart_applicable(booking_option_settings $settings): bool {
        if (!class_exists('local_shopping_cart\\shopping_cart')) {
            return false;
        }

        return !empty($settings->jsonobject->useprice);
    }

    /**
     * Returns whether the user currently has an active subscription.
     *
     * @param int $userid
     * @return bool
     */
    public static function has_active_subscription(int $userid): bool {
        return self::get_subscription_end_timestamp($userid) > time();
    }

    /**
     * Returns the configured subscription end timestamp for the user.
     *
     * @param int $userid
     * @return int
     */
    public static function get_subscription_end_timestamp(int $userid): int {
        global $USER;

        $profilefield = get_config('booking', 'bookwithsubscriptionprofilefield');
        if (empty($profilefield) || empty($userid)) {
            return 0;
        }

        if (!empty($userid) && $userid !== $USER->id) {
            $user = singleton_service::get_instance_of_user($userid);
            profile_load_custom_fields($user);
        } else {
            $user = $USER;
            profile_load_custom_fields($user);
        }

        $key = 'profile_field_' . $profilefield;
        $rawvalue = $user->{$key} ?? $user->profile[$profilefield] ?? 0;

        if (empty($rawvalue)) {
            return 0;
        }

        if (is_numeric($rawvalue)) {
            return (int)$rawvalue;
        }

        $timestamp = strtotime((string)$rawvalue);
        return $timestamp === false ? 0 : $timestamp;
    }
}
