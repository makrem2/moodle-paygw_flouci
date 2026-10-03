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


namespace paygw_flouci\task;

use paygw_flouci\transaction;

/**
 * Housekeeping: closes sessions that never got a final status and purges old failed/expired
 * sessions (they hold a user id but no payment, so we do not keep them).
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup extends \core\task\scheduled_task {

    /** @var int Days to keep failed/expired sessions. */
    private const KEEPDAYS = 30;

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup', 'paygw_flouci');
    }

    /**
     * Executes the task.
     */
    public function execute() {
        global $DB;
        $now = time();

        // Pending for more than a day: the Flouci session lasts 20 minutes, so it is dead.
        $where = 'status = :status AND timecreated < :before';
        $params = ['status' => transaction::STATUS_PENDING, 'before' => $now - DAYSECS];
        $DB->set_field_select(transaction::TABLE, 'lasterror', 'No final status received from Flouci', $where, $params);
        $DB->set_field_select(transaction::TABLE, 'status', transaction::STATUS_EXPIRED, $where, $params);

        [$insql, $inparams] = $DB->get_in_or_equal(
            [transaction::STATUS_FAILED, transaction::STATUS_EXPIRED],
            SQL_PARAMS_NAMED
        );
        $DB->delete_records_select(
            transaction::TABLE,
            "status {$insql} AND paymentid = 0 AND timemodified < :before",
            $inparams + ['before' => $now - (self::KEEPDAYS * DAYSECS)]
        );
    }
}
