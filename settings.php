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
 * Admin settings for the Flouci payment gateway.
 *
 * API keys are NOT configured here: like every Moodle payment gateway they are
 * stored per payment account (Site administration > Payments > Payment accounts).
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('paygw_flouci_settings', '', get_string('pluginname_desc', 'paygw_flouci')));

    \core_payment\helper::add_common_gateway_settings($settings, 'paygw_flouci');
}

// Operational view of all Flouci payment sessions (useful to spot mismatches).
$ADMIN->add('paymentgateways', new admin_externalpage(
    'paygw_flouci_transactions',
    get_string('transactions', 'paygw_flouci'),
    new moodle_url('/payment/gateway/flouci/transactions.php'),
    'moodle/site:config'
));
