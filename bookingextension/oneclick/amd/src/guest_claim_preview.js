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
 * Side-preview claim form for guest-checkout users who want a trial instance.
 *
 * A temporary guest account cannot own an instance: it has no real email. This
 * preview offers the lightest possible upgrade — enter just an email address —
 * next to the full log-in/registration link. Submitting calls the claim
 * webservice, which converts the guest account (shopping_cart conversion:
 * real email, cleanup cancelled, set-password mail for later verification).
 * The email is sent form → webservice directly and never enters the chat.
 *
 * @module     bookingextension_oneclick/guest_claim_preview
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';

/** The shared side-preview container the engine injects our HTML into. */
const PREVIEW_CONTAINER_ID = 'booking-ai-side-preview';

/**
 * Escape a string for safe insertion into HTML.
 *
 * @param {string} value
 * @return {string}
 */
const escapeHtml = (value) => String(value === null || value === undefined ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

/**
 * Build the claim form markup.
 *
 * @param {object} strings Resolved language strings.
 * @param {object} payload {sitename, registerurl}.
 * @return {string}
 */
const buildMarkup = (strings, payload) => {
    const loginButton = payload.registerurl
        ? '<a class="btn btn-outline-secondary btn-block w-100" data-region="oneclick-claim-login" href="'
            + escapeHtml(payload.registerurl) + '">' + escapeHtml(strings.loginButton) + '</a>'
        : '';

    return '<div class="oneclick-guest-claim card border-0" data-region="oneclick-claim">'
        + '<div class="card-body p-4">'
        + '<h5 class="mb-2" data-region="oneclick-claim-heading">' + escapeHtml(strings.heading) + '</h5>'
        + '<p class="text-muted mb-3" data-region="oneclick-claim-intro">' + escapeHtml(strings.intro) + '</p>'
        + '<div class="form-group mb-2">'
        + '<label for="oneclick-claim-email">' + escapeHtml(strings.emailLabel) + '</label>'
        + '<input type="email" class="form-control" id="oneclick-claim-email" autocomplete="email"'
        + ' data-region="oneclick-claim-email" />'
        + '</div>'
        + '<div class="text-danger small mb-2 d-none" data-region="oneclick-claim-error" role="alert"></div>'
        + '<button type="button" class="btn btn-primary btn-block w-100 mb-3" data-region="oneclick-claim-submit">'
        + escapeHtml(strings.submit) + '</button>'
        + '<div class="text-center text-muted small mb-3">' + escapeHtml(strings.or) + '</div>'
        + loginButton
        + '</div></div>';
};

/**
 * Build the success markup shown after the email has been saved.
 *
 * @param {object} strings Resolved language strings.
 * @param {boolean} continued Whether the chat request was re-sent automatically.
 * @return {string}
 */
const buildSuccessMarkup = (strings, continued) => '<div class="oneclick-guest-claim card border-0" '
    + 'data-region="oneclick-claim">'
    + '<div class="card-body text-center p-4">'
    + '<div class="text-success display-4 mb-2" aria-hidden="true">✓</div>'
    + '<h5 class="mb-2">' + escapeHtml(strings.successHeading) + '</h5>'
    + '<p class="text-muted mb-0">' + escapeHtml(continued ? strings.successIntro : strings.successIntroManual) + '</p>'
    + '</div></div>';

/**
 * Continue the conversation through the regular chat pipeline.
 *
 * Fills the agent chat input and triggers its send button, so the follow-up request
 * runs the full normal path (privacy precheck, thread, rendering, previews). When the
 * chat is busy (send button disabled) or the UI is not present, the message is left
 * in the input (or skipped) and the success card tells the user to ask again instead.
 *
 * @param {string} message The user-language follow-up message to send.
 * @return {boolean} Whether the message was actually sent.
 */
const continueConversation = (message) => {
    const input = document.getElementById('booking-ai-input');
    const sendButton = document.getElementById('booking-ai-send');
    if (!(input instanceof HTMLTextAreaElement || input instanceof HTMLInputElement)
        || !(sendButton instanceof HTMLElement) || sendButton.disabled) {
        return false;
    }
    input.value = message;
    sendButton.click();
    // The click handler consumes the input value; if it did not (e.g. another handler
    // intercepted), the prefilled message stays visible for a manual send.
    return input.value === '';
};

/**
 * Wire the form once the engine has injected the markup into the DOM.
 *
 * @param {object} strings
 * @param {number} contextid
 * @return {void}
 */
const wireForm = (strings, contextid) => {
    const container = document.getElementById(PREVIEW_CONTAINER_ID);
    if (!container || !container.querySelector('[data-region="oneclick-claim"]')) {
        return;
    }

    const query = (region) => container.querySelector('[data-region="' + region + '"]');
    const emailInput = query('oneclick-claim-email');
    const submitButton = query('oneclick-claim-submit');
    const errorBox = query('oneclick-claim-error');
    if (!emailInput || !submitButton) {
        return;
    }

    const showError = (message) => {
        if (errorBox) {
            errorBox.textContent = message || strings.errorGeneric;
            errorBox.classList.remove('d-none');
        }
    };

    const submit = () => {
        const email = String(emailInput.value || '').trim();
        if (email === '') {
            emailInput.focus();
            return;
        }
        submitButton.disabled = true;
        submitButton.textContent = strings.sending;
        if (errorBox) {
            errorBox.classList.add('d-none');
        }

        const request = Ajax.call([{
            methodname: 'bookingextension_oneclick_claim_guest_email',
            args: {contextid: contextid, email: email},
        }])[0];
        request.then((response) => {
            if (response.status === 'ok') {
                // Email set: immediately re-issue the instance request through the chat
                // so the flow continues without the user having to ask again.
                const continued = continueConversation(strings.continueMessage);
                const claimCard = query('oneclick-claim');
                if (claimCard) {
                    claimCard.outerHTML = buildSuccessMarkup(strings, continued);
                }
            } else {
                showError(response.message);
                submitButton.disabled = false;
                submitButton.textContent = strings.submit;
            }
            return response;
        }).catch(() => {
            showError(strings.errorGeneric);
            submitButton.disabled = false;
            submitButton.textContent = strings.submit;
            return null;
        });
    };

    submitButton.addEventListener('click', submit);
    emailInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            submit();
        }
    });
};

/**
 * Render the preview. Called by the agent's dispatchSkillPreview for our js_module.
 *
 * The engine injects the returned HTML into the side preview, so we schedule the
 * form wiring on the next tick once that injection has happened.
 *
 * @param {object} payload {sitename, registerurl}
 * @param {number} contextid
 * @return {Promise<string>} HTML to inject.
 */
export const render = async(payload, contextid) => {
    const safePayload = {
        sitename: (payload && payload.sitename) || '',
        registerurl: (payload && payload.registerurl) || '',
    };

    // The skill ships all texts server-rendered in the CONVERSATION language (payload.strings)
    // — the client-side get_string below only knows the user's UI language, which for a
    // guest account is the site default and may not match the chat language. The client
    // string request stays as a fallback for older skill payloads without the map.
    const serverStrings = (payload && typeof payload.strings === 'object' && payload.strings) || null;

    const stringRequest = [
        {key: 'claim_heading', component: 'bookingextension_oneclick'},
        {key: 'claim_intro', component: 'bookingextension_oneclick', param: safePayload.sitename},
        {key: 'claim_email_label', component: 'bookingextension_oneclick'},
        {key: 'claim_submit', component: 'bookingextension_oneclick'},
        {key: 'claim_sending', component: 'bookingextension_oneclick'},
        {key: 'claim_or', component: 'bookingextension_oneclick'},
        {key: 'claim_login_button', component: 'bookingextension_oneclick'},
        {key: 'claim_success_heading', component: 'bookingextension_oneclick'},
        {key: 'claim_success_intro', component: 'bookingextension_oneclick'},
        {key: 'claim_success_intro_manual', component: 'bookingextension_oneclick'},
        {key: 'claim_error_generic', component: 'bookingextension_oneclick'},
        {key: 'claim_continue_message', component: 'bookingextension_oneclick', param: safePayload.sitename},
    ];

    let resolved;
    try {
        if (serverStrings) {
            resolved = serverStrings;
        } else {
            const values = await getStrings(stringRequest);
            resolved = {
                heading: values[0],
                intro: values[1],
                emailLabel: values[2],
                submit: values[3],
                sending: values[4],
                or: values[5],
                loginButton: values[6],
                successHeading: values[7],
                successIntro: values[8],
                successIntroManual: values[9],
                errorGeneric: values[10],
                continueMessage: values[11],
            };
        }
    } catch (e) {
        resolved = {
            heading: 'Almost there!',
            intro: 'To create your instance we just need an email address.',
            emailLabel: 'Your email address',
            submit: 'Use this email',
            sending: 'Saving…',
            or: 'or',
            loginButton: 'Log in or register',
            successHeading: 'Email saved!',
            successIntro: 'Your request now continues automatically in the chat.',
            successIntroManual: 'You can now ask again in the chat to create your instance.',
            errorGeneric: 'Sorry, that did not work. Please try again.',
            continueMessage: 'Please create my trial instance named "' + safePayload.sitename + '".',
        };
    }

    // Schedule the form wiring after the engine injects this HTML.
    window.setTimeout(() => wireForm(resolved, contextid), 50);

    return buildMarkup(resolved, safePayload);
};
