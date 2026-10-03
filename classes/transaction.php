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


namespace paygw_flouci;

/**
 * Constants and small pure helpers for Flouci payment sessions.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class transaction {

    /** @var string Name of the table holding the sessions. */
    public const TABLE = 'paygw_flouci';

    /** Session created, waiting for the customer / for Flouci. */
    public const STATUS_PENDING = 'pending';
    /** Flouci confirmed the money; the payment is recorded but the order is not delivered yet. */
    public const STATUS_PAID = 'paid';
    /** Paid and delivered. Final. */
    public const STATUS_COMPLETE = 'complete';
    /** The payment failed. Final. */
    public const STATUS_FAILED = 'failed';
    /** The session expired without payment. Final. */
    public const STATUS_EXPIRED = 'expired';
    /** Flouci reported SUCCESS but amount/reference differ from what we asked. Needs a human. Final. */
    public const STATUS_MISMATCH = 'mismatch';

    /** @var int Lifetime of a Flouci checkout session, in seconds. */
    public const SESSION_TIMEOUT = 1200;

    /** @var int Extra time after the session lifetime before we consider an unknown session gone. */
    public const EXPIRY_GRACE = 600;

    /**
     * All statuses, in display order.
     *
     * @return string[]
     */
    public static function all_statuses(): array {
        return [
            self::STATUS_PENDING, self::STATUS_PAID, self::STATUS_COMPLETE,
            self::STATUS_FAILED, self::STATUS_EXPIRED, self::STATUS_MISMATCH,
        ];
    }

    /**
     * Converts a TND amount to millimes (Flouci's unit). Rounds, never truncates:
     * with intval() 1.005 TND becomes 1004 millimes, because 1.005 * 1000 is 1004.9999999999999
     * in floating point.
     *
     * @param float $amount Amount in dinars.
     * @return int
     */
    public static function to_millimes(float $amount): int {
        return (int) round($amount * 1000);
    }

    /**
     * Converts millimes to dinars.
     *
     * @param int $millimes
     * @return float
     */
    public static function from_millimes(int $millimes): float {
        return $millimes / 1000;
    }

    /**
     * Generates an unguessable 40-character reference for a session.
     *
     * @return string
     */
    public static function generate_ref(): string {
        return bin2hex(random_bytes(20));
    }

    /**
     * URL the customer comes back to (used for both Flouci success_link and fail_link).
     * The page never trusts the URL: it always asks Flouci for the real status.
     *
     * @param string $ref
     * @return \moodle_url
     */
    public static function return_url(string $ref): \moodle_url {
        return new \moodle_url('/payment/gateway/flouci/return.php', ['ref' => $ref]);
    }

    /**
     * URL Flouci calls server-to-server.
     *
     * @return \moodle_url
     */
    public static function webhook_url(): \moodle_url {
        return new \moodle_url('/payment/gateway/flouci/webhook.php');
    }

    /**
     * Truncates a message so that it fits the lasterror column.
     *
     * @param string $message
     * @return string
     */
    public static function shorten(string $message): string {
        return \core_text::substr($message, 0, 250);
    }
}
