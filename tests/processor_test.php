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

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * End-to-end tests of the payment flow against a real enrol_fee instance, with Flouci mocked.
 *
 * @package    paygw_flouci
 * @category   test
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(processor::class)]
final class processor_test extends \advanced_testcase {

    /** @var \stdClass */
    private $course;
    /** @var \stdClass */
    private $user;
    /** @var \stdClass enrol_fee instance */
    private $instance;

    /**
     * Course sold for 25.000 TND through enrol_fee, with Flouci enabled on the payment account.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        \core\plugininfo\paygw::enable_plugin('flouci', 1);

        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account();
        \core_payment\helper::save_payment_gateway((object) [
            'accountid' => $account->get('id'),
            'gateway' => 'flouci',
            'enabled' => 1,
            'config' => json_encode(['publickey' => 'pub', 'secretkey' => 'sec']),
        ]);

        $this->course = $generator->create_course();
        $this->user = $generator->create_user();

        $plugin = enrol_get_plugin('fee');
        $studentrole = $this->getDataGenerator()->create_role(); // Any role will do for the test.
        $id = $plugin->add_instance($this->course, [
            'status' => ENROL_INSTANCE_ENABLED,
            'roleid' => $studentrole,
            'cost' => 25,
            'currency' => 'TND',
            'customint1' => $account->get('id'),
        ]);
        $this->instance = (object) ['id' => $id];
    }

    /**
     * Mocks Flouci's generate_payment answer.
     */
    private function mock_generate(): void {
        \curl::mock_response(json_encode(['result' => [
            'success' => true,
            'payment_id' => 'TESTPAYMENTID123',
            'link' => 'https://checkout.flouci.com/test/TESTPAYMENTID123',
        ]]));
    }

    /**
     * Mocks Flouci's verify_payment answer.
     *
     * @param string $status Flouci status
     * @param int $amount Millimes
     * @param string|null $tracking developer_tracking_id
     */
    private function mock_verify(string $status, int $amount, ?string $tracking): void {
        \curl::mock_response(json_encode([
            'success' => true,
            'result' => ['status' => $status, 'amount' => $amount, 'developer_tracking_id' => $tracking],
        ]));
    }

    /**
     * Starts a session for the test user.
     *
     * @return \stdClass
     */
    private function start(): \stdClass {
        $this->mock_generate();
        return processor::start('enrol_fee', 'fee', (int) $this->instance->id, (int) $this->user->id, '/course/view.php?id=1');
    }

    /**
     * The amount comes from the server (25 TND = 25000 millimes) and a second click reuses the session.
     */
    public function test_start_uses_server_price_and_is_idempotent(): void {
        global $DB;

        $record = $this->start();
        $this->assertSame(25000, (int) $record->amountmillimes);
        $this->assertSame('TND', $record->currency);
        $this->assertSame('TESTPAYMENTID123', $record->flouciid);
        $this->assertSame(transaction::STATUS_PENDING, $record->status);

        // No mock queued: reusing the live session must not call Flouci again.
        $again = processor::start('enrol_fee', 'fee', (int) $this->instance->id, (int) $this->user->id);
        $this->assertSame($record->id, $again->id);
        $this->assertSame(1, $DB->count_records(transaction::TABLE));
    }

    /**
     * A genuine payment is recorded, delivered exactly once, and later calls change nothing.
     */
    public function test_successful_payment_is_delivered_once(): void {
        global $DB;

        $record = $this->start();
        $this->mock_verify('SUCCESS', 25000, $record->ref);

        $record = processor::process($record);
        $this->assertSame(transaction::STATUS_COMPLETE, $record->status);
        $this->assertGreaterThan(0, (int) $record->paymentid);
        $this->assertTrue(is_enrolled(\context_course::instance($this->course->id), $this->user));
        $this->assertSame(1, $DB->count_records('payments', ['gateway' => 'flouci']));

        // Replays (browser reload, webhook, cron) must not create another payment. No mock is queued.
        $record = processor::process($record);
        $this->assertSame(transaction::STATUS_COMPLETE, $record->status);
        $this->assertSame(1, $DB->count_records('payments', ['gateway' => 'flouci']));
    }

    /**
     * SUCCESS for the wrong amount (e.g. a session replayed for a dearer item) is never delivered.
     */
    public function test_amount_mismatch_is_not_delivered(): void {
        global $DB;

        $record = $this->start();
        $this->mock_verify('SUCCESS', 1000, $record->ref);

        $record = processor::process($record);
        $this->assertSame(transaction::STATUS_MISMATCH, $record->status);
        $this->assertFalse(is_enrolled(\context_course::instance($this->course->id), $this->user));
        $this->assertSame(0, $DB->count_records('payments', ['gateway' => 'flouci']));
    }

    /**
     * A SUCCESS that carries a different tracking reference is not ours.
     */
    public function test_reference_mismatch_is_not_delivered(): void {
        $record = $this->start();
        $this->mock_verify('SUCCESS', 25000, str_repeat('a', 40));

        $record = processor::process($record);
        $this->assertSame(transaction::STATUS_MISMATCH, $record->status);
        $this->assertFalse(is_enrolled(\context_course::instance($this->course->id), $this->user));
    }

    /**
     * Failure and expiry are final and deliver nothing.
     */
    public function test_failure_and_expiry(): void {
        $record = $this->start();
        $this->mock_verify('FAILURE', 25000, $record->ref);
        $this->assertSame(transaction::STATUS_FAILED, processor::process($record)->status);

        $other = $this->getDataGenerator()->create_user();
        $this->mock_generate();
        $second = processor::start('enrol_fee', 'fee', (int) $this->instance->id, (int) $other->id);
        $this->mock_verify('EXPIRED', 25000, $second->ref);
        $this->assertSame(transaction::STATUS_EXPIRED, processor::process($second)->status);

        $this->assertFalse(is_enrolled(\context_course::instance($this->course->id), $this->user));
        $this->assertFalse(is_enrolled(\context_course::instance($this->course->id), $other));
    }

    /**
     * A PENDING answer leaves the session open for the next attempt.
     */
    public function test_pending_stays_pending(): void {
        $record = $this->start();
        $this->mock_verify('PENDING', 25000, $record->ref);

        $this->assertSame(transaction::STATUS_PENDING, processor::process($record)->status);
    }

    /**
     * A transport problem must not lose the session: it stays pending with the error recorded.
     */
    public function test_api_error_keeps_session_pending(): void {
        $record = $this->start();
        \curl::mock_response('not json');

        $record = processor::process($record);
        $this->assertSame(transaction::STATUS_PENDING, $record->status);
        $this->assertNotSame('', $record->lasterror);
    }
}
