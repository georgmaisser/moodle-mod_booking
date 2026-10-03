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
 * Live side-preview for a one-click trial-instance provisioning job.
 *
 * Renders a spinner + countdown immediately and then polls the provisioner status
 * webservice every few seconds until the instance is ready (or fails), swapping in
 * a link to the live site when it is done.
 *
 * @module     bookingextension_oneclick/spawn_preview
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';

/** Poll interval in milliseconds (the API recommends ~10s). */
const POLL_INTERVAL_MS = 10000;

/** Hard client-side stop after this long (a run usually takes 3-10 minutes). */
const MAX_POLL_MS = 20 * 60 * 1000;

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
 * Format remaining seconds as m:ss.
 *
 * @param {number} seconds
 * @return {string}
 */
const formatCountdown = (seconds) => {
    const safe = Math.max(0, Math.floor(seconds));
    const mins = Math.floor(safe / 60);
    const secs = safe % 60;
    return mins + ':' + String(secs).padStart(2, '0');
};

/**
 * Build the initial preview markup.
 *
 * @param {object} strings Resolved language strings.
 * @param {object} payload Skill payload {jobid, host, review, eta}.
 * @return {string}
 */
const buildMarkup = (strings, payload) => {
    const heading = payload.review ? strings.reviewHeading : strings.startedHeading;
    const intro = payload.review ? strings.reviewIntro : strings.startedIntro;
    const countdownBlock = payload.review
        ? ''
        : '<div class="oneclick-countdown display-4 my-2" data-region="oneclick-countdown">'
            + escapeHtml(formatCountdown(payload.eta)) + '</div>';

    return '<div class="oneclick-spawn-preview card border-0" data-region="oneclick-spawn">'
        + '<div class="card-body text-center p-4">'
        + '<div class="spinner-border text-primary mb-3" role="status" data-region="oneclick-spinner">'
        + '<span class="sr-only visually-hidden">' + escapeHtml(strings.loading) + '</span>'
        + '</div>'
        + '<h5 class="mb-2" data-region="oneclick-heading">' + escapeHtml(heading) + '</h5>'
        + '<p class="text-muted mb-1" data-region="oneclick-status">' + escapeHtml(intro) + '</p>'
        + countdownBlock
        + '<div class="oneclick-result mt-3" data-region="oneclick-result"></div>'
        + '</div></div>';
};

/**
 * Resolve the preview container element.
 *
 * @return {HTMLElement|null}
 */
const getContainer = () => document.getElementById(PREVIEW_CONTAINER_ID);

/**
 * Wire up the live countdown and status polling once the markup is in the DOM.
 *
 * @param {object} strings
 * @param {object} payload
 * @param {number} contextid
 * @return {void}
 */
const startLiveUpdates = (strings, payload, contextid) => {
    const container = getContainer();
    if (!container || !container.querySelector('[data-region="oneclick-spawn"]')) {
        return;
    }

    const startedAt = Date.now();
    let stopped = false;
    let countdownTimer = null;
    let pollTimer = null;

    const query = (region) => container.querySelector('[data-region="' + region + '"]');

    const stop = () => {
        stopped = true;
        if (countdownTimer) {
            window.clearInterval(countdownTimer);
        }
        if (pollTimer) {
            window.clearInterval(pollTimer);
        }
        const spinner = query('oneclick-spinner');
        if (spinner) {
            spinner.classList.add('d-none');
        }
        const countdown = query('oneclick-countdown');
        if (countdown) {
            countdown.classList.add('d-none');
        }
    };

    // Live countdown (only present when not under review).
    let remaining = Number(payload.eta) || 0;
    const countdownEl = query('oneclick-countdown');
    if (countdownEl) {
        countdownTimer = window.setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                countdownEl.textContent = strings.almostReady;
            } else {
                countdownEl.textContent = formatCountdown(remaining);
            }
        }, 1000);
    }

    const renderReady = (url) => {
        stop();
        const heading = query('oneclick-heading');
        const status = query('oneclick-status');
        const result = query('oneclick-result');
        if (heading) {
            heading.textContent = strings.readyHeading;
        }
        if (status) {
            status.textContent = strings.readyIntro;
        }
        if (result && url) {
            result.innerHTML = '<a class="btn btn-success" target="_blank" rel="noopener" href="'
                + escapeHtml(url) + '">' + escapeHtml(strings.openSite) + '</a>'
                + '<div class="small text-muted mt-2">' + escapeHtml(url) + '</div>';
        }
    };

    const renderFailure = (headingText, detail) => {
        stop();
        const heading = query('oneclick-heading');
        const status = query('oneclick-status');
        const result = query('oneclick-result');
        if (heading) {
            heading.textContent = headingText;
        }
        if (status) {
            status.textContent = detail || '';
        }
        if (result) {
            result.innerHTML = '';
        }
    };

    const handleResponse = (response) => {
        if (stopped) {
            return;
        }
        const status = String(response.status || '');
        if (response.reviewstatus === 'rejected') {
            renderFailure(strings.rejectedHeading, strings.rejectedIntro);
            return;
        }
        switch (status) {
            case 'ready':
                renderReady(response.url);
                break;
            case 'failed':
                renderFailure(strings.failedHeading, response.errorsummary || strings.failedIntro);
                break;
            case 'cancelled':
                renderFailure(strings.cancelledHeading, strings.cancelledIntro);
                break;
            case 'expired':
                renderFailure(strings.expiredHeading, strings.expiredIntro);
                break;
            default:
                // Still pending/running: keep the status line informative.
                if (response.reviewstatus === 'pending') {
                    const statusEl = query('oneclick-status');
                    if (statusEl) {
                        statusEl.textContent = strings.reviewIntro;
                    }
                }
                break;
        }
    };

    const poll = () => {
        if (stopped) {
            return;
        }
        if (Date.now() - startedAt > MAX_POLL_MS) {
            renderFailure(strings.timeoutHeading, strings.timeoutIntro);
            return;
        }
        const request = Ajax.call([{
            methodname: 'bookingextension_oneclick_get_job_status',
            args: {jobid: payload.jobid, contextid: contextid},
        }])[0];
        request.then((response) => {
            handleResponse(response);
            return response;
        }).catch(() => {
            // Ignore a single failed poll; the next tick retries.
            return null;
        });
    };

    pollTimer = window.setInterval(poll, POLL_INTERVAL_MS);
    // Kick off an immediate first poll so a fast/already-ready job resolves quickly.
    poll();
};

/**
 * Render the preview. Called by the agent's dispatchSkillPreview for our js_module.
 *
 * The engine injects the returned HTML into the side preview, so we schedule the
 * live wiring on the next tick once that injection has happened.
 *
 * @param {object} payload {jobid, host, review, eta}
 * @param {number} contextid
 * @return {Promise<string>} HTML to inject.
 */
export const render = async(payload, contextid) => {
    const safePayload = {
        jobid: parseInt(payload && payload.jobid, 10) || 0,
        host: (payload && payload.host) || '',
        review: Boolean(payload && payload.review),
        eta: parseInt(payload && payload.eta, 10) || 120,
    };

    const stringRequest = [
        {key: 'preview_loading', component: 'bookingextension_oneclick'},
        {key: 'preview_started_heading', component: 'bookingextension_oneclick'},
        {key: 'preview_started_intro', component: 'bookingextension_oneclick'},
        {key: 'preview_review_heading', component: 'bookingextension_oneclick'},
        {key: 'preview_review_intro', component: 'bookingextension_oneclick'},
        {key: 'preview_almost_ready', component: 'bookingextension_oneclick'},
        {key: 'preview_ready_heading', component: 'bookingextension_oneclick'},
        {key: 'preview_ready_intro', component: 'bookingextension_oneclick'},
        {key: 'preview_open_site', component: 'bookingextension_oneclick'},
        {key: 'preview_failed_heading', component: 'bookingextension_oneclick'},
        {key: 'preview_failed_intro', component: 'bookingextension_oneclick'},
        {key: 'preview_cancelled_heading', component: 'bookingextension_oneclick'},
        {key: 'preview_cancelled_intro', component: 'bookingextension_oneclick'},
        {key: 'preview_expired_heading', component: 'bookingextension_oneclick'},
        {key: 'preview_expired_intro', component: 'bookingextension_oneclick'},
        {key: 'preview_rejected_heading', component: 'bookingextension_oneclick'},
        {key: 'preview_rejected_intro', component: 'bookingextension_oneclick'},
        {key: 'preview_timeout_heading', component: 'bookingextension_oneclick'},
        {key: 'preview_timeout_intro', component: 'bookingextension_oneclick'},
    ];

    let resolved;
    try {
        const values = await getStrings(stringRequest);
        resolved = {
            loading: values[0],
            startedHeading: values[1],
            startedIntro: values[2],
            reviewHeading: values[3],
            reviewIntro: values[4],
            almostReady: values[5],
            readyHeading: values[6],
            readyIntro: values[7],
            openSite: values[8],
            failedHeading: values[9],
            failedIntro: values[10],
            cancelledHeading: values[11],
            cancelledIntro: values[12],
            expiredHeading: values[13],
            expiredIntro: values[14],
            rejectedHeading: values[15],
            rejectedIntro: values[16],
            timeoutHeading: values[17],
            timeoutIntro: values[18],
        };
    } catch (e) {
        resolved = {
            loading: 'Loading', startedHeading: 'Creating your instance',
            startedIntro: 'This will take about two minutes.', reviewHeading: 'Under review',
            reviewIntro: 'Your request is awaiting approval.', almostReady: 'Almost ready…',
            readyHeading: 'Your instance is ready', readyIntro: 'Open your new site below.',
            openSite: 'Open my instance', failedHeading: 'Provisioning failed', failedIntro: 'Please try again later.',
            cancelledHeading: 'Request cancelled', cancelledIntro: 'An operator cancelled this request.',
            expiredHeading: 'Trial expired', expiredIntro: 'This trial has been removed.',
            rejectedHeading: 'Request denied', rejectedIntro: 'An operator denied this request.',
            timeoutHeading: 'Still working', timeoutIntro: 'This is taking longer than usual.',
        };
    }

    // Schedule the live wiring after the engine injects this HTML.
    window.setTimeout(() => startLiveUpdates(resolved, safePayload, contextid), 50);

    return buildMarkup(resolved, safePayload);
};
