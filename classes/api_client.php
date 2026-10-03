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
 * Minimal client for the Flouci Payment API v2 (https://docs.flouci.com).
 *
 * Authentication is a single header: "Authorization: Bearer <PUBLIC_KEY>:<PRIVATE_KEY>".
 * Uses Moodle's \curl wrapper so that proxy settings and the cURL blocked-hosts security
 * layer are honoured.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_client {

    /** @var string Base URL of the API. Test and live differ only by the keys used. */
    public const BASE_URL = 'https://developers.flouci.com/api/v2';

    /** @var int Total request timeout in seconds. */
    private const TIMEOUT = 20;

    /** @var int Connection timeout in seconds. */
    private const CONNECT_TIMEOUT = 10;

    /** @var string */
    private string $publickey;

    /** @var string */
    private string $secretkey;

    /**
     * Constructor.
     *
     * @param string $publickey Public key (APP_PUBLIC) of the Flouci application.
     * @param string $secretkey Private key (APP_SECRET) of the Flouci application.
     */
    public function __construct(string $publickey, string $secretkey) {
        $this->publickey = trim($publickey);
        $this->secretkey = trim($secretkey);
        if ($this->publickey === '' || $this->secretkey === '') {
            throw new \moodle_exception('notconfigured', 'paygw_flouci');
        }
    }

    /**
     * Creates a checkout session.
     *
     * @param int $amountmillimes Amount in millimes.
     * @param string $successlink Where Flouci sends the customer after a successful payment.
     * @param string $faillink Where Flouci sends the customer after a failed payment.
     * @param string $webhook Server-to-server notification URL.
     * @param string $trackingid Our reference (no personal data).
     * @param int $timeout Session lifetime in seconds.
     * @return array{payment_id: string, link: string}
     * @throws api_exception
     */
    public function generate_payment(int $amountmillimes, string $successlink, string $faillink,
                                     string $webhook, string $trackingid, int $timeout): array {
        $body = json_encode([
            'amount' => (string) $amountmillimes,
            'accept_card' => true,
            'session_timeout_secs' => $timeout,
            'success_link' => $successlink,
            'fail_link' => $faillink,
            'webhook' => $webhook,
            'developer_tracking_id' => $trackingid,
        ]);

        $data = $this->request('POST', '/generate_payment', $body);
        $result = $data['result'] ?? null;

        if (!is_array($result) || empty($result['success']) ||
                empty($result['payment_id']) || empty($result['link'])) {
            throw new api_exception('generate_payment: unexpected response structure');
        }

        $paymentid = (string) $result['payment_id'];
        $link = (string) $result['link'];

        if (!preg_match('/^[A-Za-z0-9_\-]{6,100}$/', $paymentid)) {
            throw new api_exception('generate_payment: malformed payment_id');
        }
        if (!self::is_flouci_url($link)) {
            // Defence in depth: we are about to redirect a customer to this address.
            throw new api_exception('generate_payment: checkout link is not on flouci.com');
        }

        return ['payment_id' => $paymentid, 'link' => $link];
    }

    /**
     * Asks Flouci for the real state of a payment. This is the only source of truth:
     * query strings, redirects and webhook bodies are never trusted.
     *
     * @param string $paymentid Flouci payment_id.
     * @return array{status: string, amount: int, trackingid: ?string}
     * @throws api_exception
     */
    public function verify_payment(string $paymentid): array {
        $data = $this->request('GET', '/verify_payment/' . rawurlencode($paymentid));

        // Flouci's documentation: always check "success" before reading the payload.
        if (($data['success'] ?? false) !== true || !isset($data['result']) || !is_array($data['result'])) {
            throw new api_exception('verify_payment: success flag missing or false');
        }
        $result = $data['result'];
        if (!isset($result['status']) || !is_string($result['status']) || !isset($result['amount'])) {
            throw new api_exception('verify_payment: status/amount missing');
        }

        $tracking = $result['developer_tracking_id'] ?? null;

        return [
            'status' => strtoupper($result['status']),
            'amount' => (int) $result['amount'],
            'trackingid' => is_scalar($tracking) ? (string) $tracking : null,
        ];
    }

    /**
     * Whether a URL is an https URL on flouci.com (or a subdomain).
     *
     * @param string $url
     * @return bool
     */
    public static function is_flouci_url(string $url): bool {
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return false;
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        return (bool) preg_match('/(^|\.)flouci\.com$/i', $parts['host']);
    }

    /**
     * Performs an HTTP request and decodes the JSON answer.
     *
     * @param string $method GET or POST.
     * @param string $path Path below BASE_URL.
     * @param string|null $body JSON body for POST.
     * @return array
     * @throws api_exception
     */
    private function request(string $method, string $path, ?string $body = null): array {
        $curl = new \curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $this->publickey . ':' . $this->secretkey,
            'Accept: application/json',
            'Content-Type: application/json',
        ]);

        $options = [
            'CURLOPT_TIMEOUT' => self::TIMEOUT,
            'CURLOPT_CONNECTTIMEOUT' => self::CONNECT_TIMEOUT,
            // Never follow redirects: they could carry our Authorization header elsewhere.
            'CURLOPT_FOLLOWLOCATION' => 0,
        ];

        $url = self::BASE_URL . $path;
        $raw = ($method === 'POST') ? $curl->post($url, (string) $body, $options) : $curl->get($url, [], $options);

        $info = $curl->get_info();
        $httpcode = (int) ($info['http_code'] ?? 0);

        if ($curl->get_errno() || !is_string($raw) || $raw === '') {
            throw new api_exception("{$path}: transport error (curl errno {$curl->get_errno()}, http {$httpcode})", $httpcode);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new api_exception("{$path}: response is not JSON (http {$httpcode})", $httpcode);
        }

        if ($httpcode < 200 || $httpcode >= 300) {
            $message = '';
            if (isset($data['result']['message']) && is_string($data['result']['message'])) {
                $message = ': ' . \core_text::substr($data['result']['message'], 0, 100);
            }
            throw new api_exception("{$path}: HTTP {$httpcode}{$message}", $httpcode);
        }

        return $data;
    }
}
