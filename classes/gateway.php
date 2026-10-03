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

use core_payment\form\account_gateway;

/**
 * The gateway class for the Flouci payment gateway.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {

    /**
     * Flouci only settles in Tunisian dinars.
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        return ['TND'];
    }

    /**
     * Adds the per-account configuration fields (the API keys of the Flouci application).
     *
     * @param account_gateway $form
     */
    public static function add_configuration_to_gateway_form(account_gateway $form): void {
        $mform = $form->get_mform();

        $mform->addElement('text', 'publickey', get_string('publickey', 'paygw_flouci'),
            ['size' => 40, 'autocomplete' => 'off']);
        $mform->setType('publickey', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('publickey', 'publickey', 'paygw_flouci');

        $mform->addElement('passwordunmask', 'secretkey', get_string('secretkey', 'paygw_flouci'),
            ['size' => 40, 'autocomplete' => 'new-password']);
        $mform->setType('secretkey', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('secretkey', 'secretkey', 'paygw_flouci');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(account_gateway $form,
                                                 \stdClass $data, array $files, array &$errors): void {
        $publickey = trim((string) ($data->publickey ?? ''));
        $secretkey = trim((string) ($data->secretkey ?? ''));

        // The two keys are joined with a colon in the Authorization header, so neither may contain
        // a colon or whitespace.
        foreach (['publickey' => $publickey, 'secretkey' => $secretkey] as $field => $value) {
            if ($value !== '' && preg_match('/[\s:]/', $value)) {
                $errors[$field] = get_string('invalidkey', 'paygw_flouci');
            }
        }

        if (!empty($data->enabled) && ($publickey === '' || $secretkey === '')) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
    }
}
