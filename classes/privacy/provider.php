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


namespace paygw_flouci\privacy;

use core_payment\privacy\paygw_provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use paygw_flouci\transaction;

/**
 * Privacy Subsystem implementation for paygw_flouci.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, paygw_provider {

    /**
     * Describes the data stored and the data sent to Flouci.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(transaction::TABLE, [
            'userid' => 'privacy:metadata:paygw_flouci:userid',
            'component' => 'privacy:metadata:paygw_flouci:item',
            'paymentarea' => 'privacy:metadata:paygw_flouci:item',
            'itemid' => 'privacy:metadata:paygw_flouci:item',
            'amountmillimes' => 'privacy:metadata:paygw_flouci:amountmillimes',
            'flouciid' => 'privacy:metadata:paygw_flouci:flouciid',
            'ref' => 'privacy:metadata:paygw_flouci:ref',
            'status' => 'privacy:metadata:paygw_flouci:status',
            'timecreated' => 'privacy:metadata:paygw_flouci:time',
        ], 'privacy:metadata:paygw_flouci');

        // Only an amount and an opaque random reference leave Moodle: no name, e-mail or user id.
        $collection->add_external_location_link('flouci', [
            'amount' => 'privacy:metadata:flouci:amount',
            'trackingid' => 'privacy:metadata:flouci:trackingid',
        ], 'privacy:metadata:flouci');

        return $collection;
    }

    /**
     * Exports the Flouci data linked to a payment.
     *
     * @param \context $context
     * @param array $subcontext
     * @param \stdClass $payment The payment record
     */
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;

        $record = $DB->get_record(transaction::TABLE, ['paymentid' => $payment->id]);
        if (!$record) {
            return;
        }

        $subcontext[] = get_string('gatewayname', 'paygw_flouci');
        writer::with_context($context)->export_data($subcontext, (object) [
            'flouci_payment_id' => $record->flouciid,
            'reference' => $record->ref,
            'amount_millimes' => (int) $record->amountmillimes,
            'status' => $record->status,
            'time_created' => \core_privacy\local\request\transform::datetime($record->timecreated),
        ]);
    }

    /**
     * Deletes the Flouci data linked to the given payments.
     *
     * @param string $paymentsql SQL query that selects payment.id field for the payments
     * @param array $paymentparams Array of parameters for $paymentsql
     */
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams) {
        global $DB;

        $DB->delete_records_select(transaction::TABLE, "paymentid IN ({$paymentsql})", $paymentparams);
    }
}
