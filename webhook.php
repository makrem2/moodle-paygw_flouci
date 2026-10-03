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
 * Server-to-server notification endpoint called by Flouci.
 *
 * Nothing in the request is trusted. The only thing we take from it is a candidate payment_id,
 * which is looked up in OUR table: unknown ids and finished sessions cause no outbound call and
 * no change. For a known pending session the real status is fetched from the Flouci API.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../../config.php');

use paygw_flouci\processor;
use paygw_flouci\transaction;

/**
 * Sends a plain-text answer and stops.
 *
 * @param int $code HTTP status code
 * @param string $text Body
 */
function paygw_flouci_respond(int $code, string $text): never {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo $text;
    die();
}

if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'POST'], true)) {
    paygw_flouci_respond(405, 'Method not allowed');
}

// Flouci's webhook payload is not documented in detail, so accept the id from the query string,
// a form body or a JSON body, under a few plausible names.
$candidates = [];
$names = ['payment_id', 'paymentId', 'payment_ref', 'id'];
foreach ($names as $name) {
    $candidates[] = $_GET[$name] ?? null;
    $candidates[] = $_POST[$name] ?? null;
}
$body = file_get_contents('php://input', false, null, 0, 65536);
if (is_string($body) && $body !== '') {
    $json = json_decode($body, true);
    if (is_array($json)) {
        foreach ($names as $name) {
            $candidates[] = $json[$name] ?? null;
            $candidates[] = $json['result'][$name] ?? null;
        }
    }
}

$flouciid = '';
foreach ($candidates as $candidate) {
    if (is_string($candidate) && preg_match('/^[A-Za-z0-9_\-]{6,100}$/', $candidate)) {
        $flouciid = $candidate;
        break;
    }
}
if ($flouciid === '') {
    paygw_flouci_respond(400, 'Missing payment id');
}

$record = $DB->get_record(transaction::TABLE, ['flouciid' => $flouciid], '*', IGNORE_MULTIPLE);
if (!$record) {
    paygw_flouci_respond(404, 'Unknown payment');
}

if (in_array($record->status, [transaction::STATUS_PENDING, transaction::STATUS_PAID], true)) {
    try {
        processor::process($record);
    } catch (Throwable $e) {
        // Let Flouci retry later.
        paygw_flouci_respond(500, 'Temporary error');
    }
}

paygw_flouci_respond(200, 'OK');
