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
 * Flouci content of the Moodle payment gateways modal.
 *
 * Flouci is a redirect-based gateway: we ask the server for a checkout URL and send the browser
 * there. The customer is brought back to /payment/gateway/flouci/return.php, which verifies the
 * payment server-side and then redirects to the item's success page.
 *
 * @module     paygw_flouci/gateways_modal
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from './repository';

/**
 * Starts the payment.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @param {string} description Description of the payment (unused: Flouci shows its own page)
 * @returns {Promise<string>} Never resolves on success because the page navigates away; rejects with a message.
 */
export const process = (component, paymentArea, itemId, description) => { // eslint-disable-line no-unused-vars
    return Repository.startPayment(component, paymentArea, itemId, window.location.href)
        .then(({redirecturl}) => {
            window.location.assign(redirecturl);

            // Deliberately never settle. If we resolved, core_payment would treat the payment as done
            // and redirect to the success page before the customer has paid.
            return new Promise(() => {});
        })
        // core_payment shows whatever we reject with, so turn the exception into plain text.
        .catch(error => Promise.reject((error && error.message) ? error.message : String(error)));
};
