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
 * Tests for the pure helpers of {@see transaction}.
 *
 * @package    paygw_flouci
 * @category   test
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(transaction::class)]
final class transaction_test extends \basic_testcase {

    /**
     * Amounts are rounded, never truncated: 1.005 * 1000 is 1004.9999999999999 as a float,
     * so intval() would charge 1004 millimes.
     */
    public function test_to_millimes(): void {
        $this->assertSame(1005, transaction::to_millimes(1.005));
        $this->assertSame(1001, transaction::to_millimes(1.001));
        $this->assertSame(290, transaction::to_millimes(0.29));
        $this->assertSame(12345, transaction::to_millimes(12.345));
        $this->assertSame(10000, transaction::to_millimes(10.0));
        $this->assertSame(1, transaction::to_millimes(0.001));
        $this->assertSame(0, transaction::to_millimes(0.0));
    }

    /**
     * Round trip.
     */
    public function test_from_millimes(): void {
        $this->assertEqualsWithDelta(12.345, transaction::from_millimes(12345), 0.00001);
        $this->assertEqualsWithDelta(25.0, transaction::from_millimes(25000), 0.00001);
    }

    /**
     * References are 40 hex characters and unique.
     */
    public function test_generate_ref(): void {
        $a = transaction::generate_ref();
        $b = transaction::generate_ref();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $a);
        $this->assertNotSame($a, $b);
    }

    /**
     * Every status has a language string and is final or not as documented.
     */
    public function test_all_statuses_have_strings(): void {
        foreach (transaction::all_statuses() as $status) {
            $this->assertTrue(
                get_string_manager()->string_exists('status_' . $status, 'paygw_flouci'),
                "Missing string status_{$status}"
            );
        }
    }
}
