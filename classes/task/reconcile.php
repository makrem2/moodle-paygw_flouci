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

use paygw_flouci\processor;
use paygw_flouci\transaction;

/**
 * Safety net: finishes payments whose customer never came back and whose webhook never arrived,
 * and retries deliveries that failed.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reconcile extends \core\task\scheduled_task {

    /** @var int Maximum sessions handled per run (keeps us well below Flouci's rate limits). */
    private const BATCH = 50;

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_reconcile', 'paygw_flouci');
    }

    /**
     * Executes the task.
     */
    public function execute() {
        global $DB;
        $now = time();

        // Sessions still pending: give the customer/webhook 90 seconds before we ask.
        $pending = $DB->get_records_select(
            transaction::TABLE,
            'status = :status AND flouciid <> :empty AND timecreated < :before',
            ['status' => transaction::STATUS_PENDING, 'empty' => '', 'before' => $now - 90],
            'timemodified ASC',
            '*',
            0,
            self::BATCH
        );
        // Paid but not delivered yet (delivery failed earlier).
        $paid = $DB->get_records_select(
            transaction::TABLE,
            'status = :status AND timemodified < :before',
            ['status' => transaction::STATUS_PAID, 'before' => $now - 60],
            'timemodified ASC',
            '*',
            0,
            self::BATCH
        );

        foreach ($pending + $paid as $record) {
            try {
                $updated = processor::process($record);
                mtrace("  paygw_flouci #{$record->id}: {$record->status} -> {$updated->status}");
            } catch (\Throwable $e) {
                mtrace("  paygw_flouci #{$record->id}: " . $e->getMessage());
            }
        }
    }
}
