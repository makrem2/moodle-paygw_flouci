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
 * Admin overview of Flouci payment sessions.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use core\output\notification;
use paygw_flouci\transaction;

admin_externalpage_setup('paygw_flouci_transactions');

$status = optional_param('status', '', PARAM_ALPHA);
$pageurl = new moodle_url('/payment/gateway/flouci/transactions.php');

$params = [];
if ($status !== '' && in_array($status, transaction::all_statuses(), true)) {
    $params['status'] = $status;
    $pageurl->param('status', $status);
} else {
    $status = '';
}

$records = $DB->get_records(transaction::TABLE, $params, 'id DESC', '*', 0, 200);
$mismatches = $DB->count_records(transaction::TABLE, ['status' => transaction::STATUS_MISMATCH]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('transactions', 'paygw_flouci'));

if ($mismatches) {
    echo $OUTPUT->notification(get_string('mismatchesfound', 'paygw_flouci', $mismatches), notification::NOTIFY_ERROR);
}

$options = [];
foreach (transaction::all_statuses() as $s) {
    $options[$s] = get_string('status_' . $s, 'paygw_flouci');
}
echo $OUTPUT->single_select($pageurl, 'status', $options, $status, ['' => get_string('all')], null, [
    'label' => get_string('status'),
]);

$table = new html_table();
$table->head = [
    get_string('id', 'paygw_flouci'),
    get_string('time'),
    get_string('user'),
    get_string('item', 'paygw_flouci'),
    get_string('amount', 'paygw_flouci'),
    get_string('status'),
    get_string('flouciid', 'paygw_flouci'),
    get_string('lasterror', 'paygw_flouci'),
];
$table->attributes['class'] = 'generaltable table-sm';

foreach ($records as $r) {
    $userlink = html_writer::link(new moodle_url('/user/view.php', ['id' => $r->userid]), (string) $r->userid);
    $table->data[] = [
        $r->id,
        userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        $userlink,
        s($r->component . ' / ' . $r->paymentarea . ' / ' . $r->itemid),
        format_float(transaction::from_millimes((int) $r->amountmillimes), 3) . ' ' . s($r->currency),
        get_string('status_' . $r->status, 'paygw_flouci'),
        s($r->flouciid),
        s($r->lasterror),
    ];
}

if ($table->data) {
    echo html_writer::div(html_writer::table($table), 'table-responsive');
} else {
    echo $OUTPUT->notification(get_string('notransactions', 'paygw_flouci'), notification::NOTIFY_INFO);
}

echo $OUTPUT->footer();
