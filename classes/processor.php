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

use core_payment\helper as payment_helper;
use paygw_flouci\event\payment_completed;
use paygw_flouci\event\payment_mismatch;

/**
 * Business logic: starting a Flouci payment and turning Flouci's verdict into a delivered order.
 *
 * Security model (the reasons this class exists as it does):
 *  - The amount and currency are always computed on the server from the component's payable.
 *    Nothing the browser sends is trusted.
 *  - A session is bound to the user who started it, to one component/area/item, and to one
 *    Flouci payment_id. A payment_id cannot be replayed against another item or by another user.
 *  - A payment is only accepted after asking the Flouci API (never from a redirect or webhook
 *    body) and only if Flouci reports status SUCCESS for exactly the amount we asked for.
 *  - Processing is serialised per session with a lock and is idempotent: browser return,
 *    webhook and cron may all race, the order is delivered once.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processor {

    /**
     * Creates (or reuses) a Flouci checkout session for the given user and item.
     *
     * @param string $component Component that sells the item (e.g. enrol_fee).
     * @param string $paymentarea Payment area.
     * @param int $itemid Item id.
     * @param int $userid The paying user.
     * @param string $returnurl Local URL to send the user back to if the payment does not succeed.
     * @return \stdClass The session record (checkouturl is set).
     * @throws \moodle_exception
     */
    public static function start(string $component, string $paymentarea, int $itemid, int $userid,
                                 string $returnurl = ''): \stdClass {
        global $DB;

        $payable = payment_helper::get_payable($component, $paymentarea, $itemid);
        $currency = $payable->get_currency();
        if ($currency !== 'TND') {
            throw new \moodle_exception('unsupportedcurrency', 'paygw_flouci', '', $currency);
        }

        $surcharge = payment_helper::get_gateway_surcharge('flouci');
        $amount = payment_helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);
        $millimes = transaction::to_millimes($amount);
        if ($millimes <= 0) {
            throw new \moodle_exception('invalidamount', 'paygw_flouci');
        }

        // Throws 'gatewaynotfound' when the account is disabled or Flouci is not enabled for it.
        $config = payment_helper::get_gateway_configuration($component, $paymentarea, $itemid, 'flouci');
        $client = new api_client((string) ($config['publickey'] ?? ''), (string) ($config['secretkey'] ?? ''));

        // Double click / reload: reuse the live session instead of creating another one at Flouci.
        $select = 'userid = :userid AND component = :component AND paymentarea = :paymentarea AND itemid = :itemid
                   AND status = :status AND amountmillimes = :amount AND flouciid <> :empty AND timecreated > :since';
        $existing = $DB->get_records_select(transaction::TABLE, $select, [
            'userid' => $userid,
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'status' => transaction::STATUS_PENDING,
            'amount' => $millimes,
            'empty' => '',
            'since' => time() - (transaction::SESSION_TIMEOUT - 120),
        ], 'timecreated DESC', '*', 0, 1);
        foreach ($existing as $record) {
            if (!empty($record->checkouturl)) {
                return $record;
            }
        }

        $now = time();
        $record = (object) [
            'ref' => transaction::generate_ref(),
            'flouciid' => '',
            'userid' => $userid,
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'accountid' => $payable->get_account_id(),
            'amountmillimes' => $millimes,
            'currency' => $currency,
            'status' => transaction::STATUS_PENDING,
            'paymentid' => 0,
            'checkouturl' => '',
            'returnurl' => self::clean_returnurl($returnurl),
            'lasterror' => '',
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record(transaction::TABLE, $record);

        try {
            $backurl = transaction::return_url($record->ref)->out(false);
            $session = $client->generate_payment(
                $millimes,
                $backurl,
                $backurl,
                transaction::webhook_url()->out(false),
                $record->ref,
                transaction::SESSION_TIMEOUT
            );
        } catch (api_exception $e) {
            self::update($record, [
                'status' => transaction::STATUS_FAILED,
                'lasterror' => transaction::shorten((string) $e->debuginfo),
            ]);
            throw new \moodle_exception('cannotstart', 'paygw_flouci');
        }

        return self::update($record, [
            'flouciid' => $session['payment_id'],
            'checkouturl' => $session['link'],
        ]);
    }

    /**
     * Brings a session up to date: asks Flouci for the verdict and, if the customer really paid,
     * records the payment and delivers the order. Safe to call repeatedly and concurrently.
     *
     * API/network problems do not throw: they are stored in lasterror and the session stays pending
     * so that the next attempt (webhook, return page, cron) can try again.
     *
     * @param \stdClass $record A row of paygw_flouci (only the id is trusted; it is reloaded under lock).
     * @return \stdClass The reloaded, possibly updated row.
     * @throws \moodle_exception When the lock cannot be obtained.
     */
    public static function process(\stdClass $record): \stdClass {
        global $DB;

        $lock = \core\lock\lock_config::get_lock_factory('paygw_flouci')->get_lock('tx_' . $record->id, 30);
        if (!$lock) {
            throw new \moodle_exception('lockfailed', 'paygw_flouci');
        }

        try {
            $record = $DB->get_record(transaction::TABLE, ['id' => $record->id], '*', MUST_EXIST);

            switch ($record->status) {
                case transaction::STATUS_PENDING:
                    return self::check_with_flouci($record);
                case transaction::STATUS_PAID:
                    return self::deliver($record);
                default:
                    return $record;
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Asks Flouci about a pending session and acts on the answer.
     *
     * @param \stdClass $record
     * @return \stdClass
     */
    private static function check_with_flouci(\stdClass $record): \stdClass {
        if ($record->flouciid === '') {
            return $record; // The session was never created at Flouci.
        }

        try {
            $account = new \core_payment\account((int) $record->accountid);
            $gateway = $account->get_gateways(false)['flouci'] ?? null;
            if (!$gateway) {
                throw new \moodle_exception('notconfigured', 'paygw_flouci');
            }
            $config = $gateway->get_configuration();
            $client = new api_client((string) ($config['publickey'] ?? ''), (string) ($config['secretkey'] ?? ''));
            $result = $client->verify_payment($record->flouciid);
        } catch (api_exception $e) {
            $age = time() - (int) $record->timecreated;
            if ($e->httpcode === 404 && $age > transaction::SESSION_TIMEOUT + transaction::EXPIRY_GRACE) {
                return self::update($record, [
                    'status' => transaction::STATUS_EXPIRED,
                    'lasterror' => transaction::shorten((string) $e->debuginfo),
                ]);
            }
            return self::update($record, ['lasterror' => transaction::shorten((string) $e->debuginfo)]);
        } catch (\Throwable $e) {
            return self::update($record, ['lasterror' => transaction::shorten($e->getMessage())]);
        }

        switch ($result['status']) {
            case 'SUCCESS':
                return self::confirm($record, $result);
            case 'EXPIRED':
                return self::update($record, ['status' => transaction::STATUS_EXPIRED, 'lasterror' => '']);
            case 'FAILURE':
            case 'SYSTEM_FAILURE':
                return self::update($record, ['status' => transaction::STATUS_FAILED, 'lasterror' => '']);
            default:
                // PENDING (or a status we do not use): leave the session open.
                return self::update($record, ['lasterror' => '']);
        }
    }

    /**
     * Flouci says SUCCESS: check amount and reference, record the payment, deliver.
     *
     * @param \stdClass $record
     * @param array $result Result of api_client::verify_payment().
     * @return \stdClass
     */
    private static function confirm(\stdClass $record, array $result): \stdClass {
        global $DB;

        $expected = (int) $record->amountmillimes;
        $tracking = $result['trackingid'];
        $trackingok = ($tracking === null || $tracking === '' || hash_equals((string) $record->ref, $tracking));

        if ($result['amount'] !== $expected || !$trackingok) {
            $record = self::update($record, [
                'status' => transaction::STATUS_MISMATCH,
                'lasterror' => transaction::shorten(
                    "Flouci reported {$result['amount']} millimes (expected {$expected}), reference " .
                    ($trackingok ? 'ok' : 'different')
                ),
            ]);
            payment_mismatch::create([
                'context' => \context_system::instance(),
                'objectid' => $record->id,
                'relateduserid' => $record->userid,
                'other' => ['ref' => $record->ref, 'expected' => $expected, 'reported' => $result['amount']],
            ])->trigger();
            return $record;
        }

        $transaction = $DB->start_delegated_transaction();
        $paymentid = payment_helper::save_payment(
            (int) $record->accountid,
            $record->component,
            $record->paymentarea,
            (int) $record->itemid,
            (int) $record->userid,
            transaction::from_millimes($expected),
            'TND',
            'flouci'
        );
        $record = self::update($record, [
            'status' => transaction::STATUS_PAID,
            'paymentid' => $paymentid,
            'lasterror' => '',
        ]);
        $transaction->allow_commit();

        return self::deliver($record);
    }

    /**
     * Delivers what the customer paid for. If the component fails, the session stays in "paid"
     * and the scheduled task retries; the money is never forgotten.
     *
     * @param \stdClass $record Row in status "paid".
     * @return \stdClass
     */
    private static function deliver(\stdClass $record): \stdClass {
        $error = '';
        try {
            $ok = payment_helper::deliver_order(
                $record->component,
                $record->paymentarea,
                (int) $record->itemid,
                (int) $record->paymentid,
                (int) $record->userid
            );
            if (!$ok) {
                $error = 'deliver_order returned false';
            }
        } catch (\Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }

        if (!$ok) {
            return self::update($record, ['lasterror' => transaction::shorten('Delivery failed: ' . $error)]);
        }

        $record = self::update($record, ['status' => transaction::STATUS_COMPLETE, 'lasterror' => '']);
        payment_completed::create([
            'context' => \context_system::instance(),
            'objectid' => $record->id,
            'relateduserid' => $record->userid,
            'other' => ['ref' => $record->ref, 'amountmillimes' => (int) $record->amountmillimes],
        ])->trigger();
        return $record;
    }

    /**
     * Applies changes to a row and saves it.
     *
     * @param \stdClass $record
     * @param array $changes field => value
     * @return \stdClass
     */
    private static function update(\stdClass $record, array $changes): \stdClass {
        global $DB;
        foreach ($changes as $field => $value) {
            $record->{$field} = $value;
        }
        $record->timemodified = time();
        $DB->update_record(transaction::TABLE, $record);
        return $record;
    }

    /**
     * Only same-site URLs that fit the column are kept.
     *
     * @param string $url
     * @return string
     */
    private static function clean_returnurl(string $url): string {
        $url = clean_param($url, PARAM_LOCALURL);
        return (strlen($url) <= 255) ? $url : '';
    }
}
