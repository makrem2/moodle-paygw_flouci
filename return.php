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
 * Where Flouci sends the customer back to (success_link and fail_link are the same page).
 *
 * The query string is never trusted as proof of payment: the page loads OUR session by its
 * random reference AND the logged-in user id, then asks the Flouci API for the real status.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use core\output\notification;
use core_payment\helper as payment_helper;
use paygw_flouci\processor;
use paygw_flouci\transaction;

require_login(null, false);

$ref = required_param('ref', PARAM_ALPHANUM);

$url = transaction::return_url($ref);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'paygw_flouci'));
$PAGE->set_heading(get_string('pluginname', 'paygw_flouci'));

// Only the user who started the payment can see its outcome.
$record = $DB->get_record(transaction::TABLE, ['ref' => $ref, 'userid' => $USER->id]);
if (!$record) {
    throw new moodle_exception('transactionnotfound', 'paygw_flouci');
}

$record = processor::process($record);

$backurl = !empty($record->returnurl) ? new moodle_url($record->returnurl) : new moodle_url('/');

switch ($record->status) {
    case transaction::STATUS_COMPLETE:
        $successurl = payment_helper::get_success_url($record->component, $record->paymentarea, (int) $record->itemid);
        redirect($successurl, get_string('paymentsuccessful', 'paygw_flouci'), null, notification::NOTIFY_SUCCESS);
        break;

    case transaction::STATUS_FAILED:
        redirect($backurl, get_string('paymentfailed', 'paygw_flouci'), null, notification::NOTIFY_ERROR);
        break;

    case transaction::STATUS_EXPIRED:
        redirect($backurl, get_string('paymentexpired', 'paygw_flouci'), null, notification::NOTIFY_WARNING);
        break;
}

// Anything else needs a page: still pending, paid-but-not-yet-delivered, or flagged for review.
echo $OUTPUT->header();
if ($record->status === transaction::STATUS_MISMATCH) {
    echo $OUTPUT->notification(get_string('paymentmismatch', 'paygw_flouci', $record->ref), notification::NOTIFY_ERROR);
    echo $OUTPUT->continue_button($backurl);
} else {
    $message = ($record->status === transaction::STATUS_PAID) ? 'deliverypending' : 'paymentpending';
    echo $OUTPUT->notification(get_string($message, 'paygw_flouci'), notification::NOTIFY_INFO);
    echo $OUTPUT->single_button($url, get_string('checkagain', 'paygw_flouci'), 'get');
}
echo $OUTPUT->footer();
