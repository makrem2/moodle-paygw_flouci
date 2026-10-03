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
 * Flouci repository module: wraps the AJAX calls to the server.
 *
 * @module     paygw_flouci/repository
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Asks the server to create a Flouci checkout session for the item.
 *
 * The browser never sends an amount or a currency: the server computes both.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @param {string} returnUrl Local page to come back to if the payment is not completed
 * @returns {Promise<{redirecturl: string}>}
 */
export const startPayment = (component, paymentArea, itemId, returnUrl) => {
    const request = {
        methodname: 'paygw_flouci_start_payment',
        args: {
            component,
            paymentarea: paymentArea,
            itemid: itemId,
            returnurl: returnUrl,
        },
    };

    return Ajax.call([request])[0];
};
