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


declare(strict_types=1);

namespace paygw_flouci\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use paygw_flouci\processor;

/**
 * Web service used by the payment modal: creates a Flouci session and returns its checkout URL.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class start_payment extends external_api {

    /**
     * Parameters. Note there is deliberately no amount or currency: they come from the server.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component that sells the item'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'Item id in the context of the component area'),
            'returnurl' => new external_value(PARAM_LOCALURL, 'Page to return to if the payment is not completed',
                VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Creates the session.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @param string $returnurl
     * @return array
     */
    public static function execute(string $component, string $paymentarea, int $itemid, string $returnurl = ''): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'returnurl' => $returnurl,
        ]);

        if (!isloggedin() || isguestuser()) {
            throw new \require_login_exception('Guests cannot pay');
        }

        $record = processor::start(
            $params['component'],
            $params['paymentarea'],
            $params['itemid'],
            (int) $USER->id,
            $params['returnurl']
        );

        return ['redirecturl' => $record->checkouturl];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'redirecturl' => new external_value(PARAM_URL, 'Flouci checkout URL'),
        ]);
    }
}
