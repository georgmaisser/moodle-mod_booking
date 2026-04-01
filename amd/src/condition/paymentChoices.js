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
 * @module     mod_booking/condition/paymentChoices
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import DynamicForm from 'core_form/dynamicform';

const SELECTOR = {
    FORMCONTAINER: '.condition-paymentchoices',
    PREPAGEBODY: '.prepage-body',
    CONTINUEBUTTON: ' div.prepage-booking-footer .continue-button',
};

/**
 * Init function.
 */
export async function init() {
    let container = document.querySelector('div.modal.show ' + SELECTOR.FORMCONTAINER);

    // If we don't find the container like this, we use the inline form.
    if (!container) {
        const containers = document.querySelectorAll('div.prepage-body ' + SELECTOR.FORMCONTAINER);
        containers.forEach(el => {
            if (!isHidden(el)) {
                container = el;
            }
        });

        if (!container) {
            return;
        }
    }

    const id = container.dataset.id;
    const userid = container.dataset.userid;

    const dynamicForm = new DynamicForm(container, 'mod_booking\\form\\condition\\paymentchoices_form');

    await dynamicForm.load({
        id,
        userid,
    });

    let continuebutton = container.closest(SELECTOR.PREPAGEBODY).querySelector(SELECTOR.CONTINUEBUTTON);

    dynamicForm.addEventListener(dynamicForm.events.FORM_SUBMITTED, e => {
        const response = e.detail;

        if (response) {
            if (!continuebutton) {
                continuebutton = container.closest(SELECTOR.PREPAGEBODY).querySelector(SELECTOR.CONTINUEBUTTON);
            }
            if (continuebutton) {
                continuebutton.dataset.blocked = 'false';
                continuebutton.click();
            }
        }
    });

    // This goes on continue button and submits the dynamic form first.
    if (continuebutton) {
        continuebutton.dataset.blocked = true;

        continuebutton.addEventListener('click', e => {
            if (continuebutton.dataset.blocked == 'true') {
                e.preventDefault();
                dynamicForm.submitFormAjax();
            }
        });
    }
}

/**
 * Function to check visibility of element.
 *
 * @param {*} el
 * @returns {boolean}
 */
function isHidden(el) {
    var style = window.getComputedStyle(el);
    return ((style.display === 'none') || (style.visibility === 'hidden'));
}
