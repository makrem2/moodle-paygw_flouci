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
 * Tests for the Flouci API client, using Moodle's curl mocking.
 *
 * @package    paygw_flouci
 * @category   test
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(api_client::class)]
#[CoversClass(api_exception::class)]
final class api_client_test extends \advanced_testcase {

    /**
     * Builds a client with dummy keys.
     *
     * @return api_client
     */
    private function client(): api_client {
        return new api_client('public_key', 'secret_key');
    }

    /**
     * Calls generate_payment with dummy arguments.
     *
     * @param api_client $client
     * @return array
     */
    private function generate(api_client $client): array {
        return $client->generate_payment(25000, 'https://m.test/r', 'https://m.test/r', 'https://m.test/w', 'ref123', 1200);
    }

    /**
     * Empty keys are rejected before anything is sent.
     */
    public function test_keys_are_required(): void {
        $this->expectException(\moodle_exception::class);
        new api_client('', 'x');
    }

    /**
     * A well-formed generate_payment answer is parsed.
     */
    public function test_generate_payment_success(): void {
        \curl::mock_response(json_encode(['result' => [
            'success' => true,
            'payment_id' => 'AgCKuBm0S5uLPghBo571MQ',
            'link' => 'https://checkout.flouci.com/company/AgCKuBm0S5uLPghBo571MQ',
        ], 'code' => 0]));

        $session = $this->generate($this->client());
        $this->assertSame('AgCKuBm0S5uLPghBo571MQ', $session['payment_id']);
        $this->assertSame('https://checkout.flouci.com/company/AgCKuBm0S5uLPghBo571MQ', $session['link']);
    }

    /**
     * A checkout link that is not on flouci.com is refused (we would redirect a customer there).
     */
    public function test_generate_payment_rejects_foreign_link(): void {
        \curl::mock_response(json_encode(['result' => [
            'success' => true,
            'payment_id' => 'AgCKuBm0S5uLPghBo571MQ',
            'link' => 'https://evil.example.com/pay',
        ]]));

        $this->expectException(api_exception::class);
        $this->generate($this->client());
    }

    /**
     * An error-shaped answer is refused.
     */
    public function test_generate_payment_rejects_error_payload(): void {
        \curl::mock_response(json_encode(['result' => ['status' => 400, 'message' => 'Bad Request']]));

        $this->expectException(api_exception::class);
        $this->generate($this->client());
    }

    /**
     * Garbage is refused.
     */
    public function test_non_json_response_is_refused(): void {
        \curl::mock_response('<html>502 Bad Gateway</html>');

        $this->expectException(api_exception::class);
        $this->generate($this->client());
    }

    /**
     * verify_payment parses the documented structure.
     */
    public function test_verify_payment_success(): void {
        \curl::mock_response(json_encode([
            'success' => true,
            'result' => [
                'type' => 'wallet',
                'amount' => 1250,
                'status' => 'SUCCESS',
                'developer_tracking_id' => 'ref123',
                'settlement_status' => 'AVAILABLE',
            ],
        ]));

        $result = $this->client()->verify_payment('AgCKuBm0S5uLPghBo571MQ');
        $this->assertSame('SUCCESS', $result['status']);
        $this->assertSame(1250, $result['amount']);
        $this->assertSame('ref123', $result['trackingid']);
    }

    /**
     * Flouci's documentation requires checking the "success" flag first.
     */
    public function test_verify_payment_requires_success_flag(): void {
        \curl::mock_response(json_encode([
            'success' => false,
            'result' => ['amount' => 1250, 'status' => 'SUCCESS'],
        ]));

        $this->expectException(api_exception::class);
        $this->client()->verify_payment('AgCKuBm0S5uLPghBo571MQ');
    }

    /**
     * Only https URLs on flouci.com are accepted.
     */
    public function test_is_flouci_url(): void {
        $good = ['https://flouci.com/pay/x', 'https://checkout.flouci.com/c/x', 'https://FLOUCI.COM/x'];
        $bad = [
            'http://checkout.flouci.com/x',
            'https://flouci.com.evil.com/x',
            'https://evilflouci.com/x',
            'https://flouci.com@evil.com/x',
            'https://user:pw@flouci.com/x',
            'javascript:alert(1)',
            '//flouci.com/x',
            '',
        ];
        foreach ($good as $url) {
            $this->assertTrue(api_client::is_flouci_url($url), $url);
        }
        foreach ($bad as $url) {
            $this->assertFalse(api_client::is_flouci_url($url), $url);
        }
    }
}
