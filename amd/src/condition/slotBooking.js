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
 * @module     mod_booking/condition/slotBooking
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import DynamicForm from 'core_form/dynamicform';
import {init as initSlotCalendarPicker} from 'mod_booking/slotCalendarPicker';
import Notification from 'core/notification';

const SELECTOR = {
    FORMCONTAINER: '.booking-slotbooking-prepage',
    PREPAGEBODY: '.prepage-body',
    CONTINUEBUTTON: ' div.prepage-booking-footer .continue-button',
};

const parseSlots = (jsonInput) => {
    if (!jsonInput) {
        return [];
    }

    try {
        const parsed = JSON.parse(jsonInput.value || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
};

const parseTeacherSelection = (input) => {
    if (!input || !input.value) {
        return {};
    }

    try {
        const parsed = JSON.parse(input.value || '{}');
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
        return {};
    }
};

const serializeTeacherSelection = (input, selection) => {
    if (!input) {
        return;
    }
    input.value = JSON.stringify(selection || {});
};

const getSelectionInput = (container) => {
    return container.querySelector('input[name="slot_selection"]')
        || container.querySelector('select[name="slot_selection"]')
        || container.querySelector('select[name="slot_selection[]"]');
};

const toLocalTimeValue = (timestamp) => {
    const date = new Date(Number(timestamp) * 1000);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
};

const toTimestampForDay = (dayTimestamp, timeValue) => {
    if (!timeValue || !/^\d{2}:\d{2}$/.test(timeValue)) {
        return 0;
    }

    const day = new Date(Number(dayTimestamp) * 1000);
    const [hours, minutes] = timeValue.split(':').map(Number);
    day.setHours(hours, minutes, 0, 0);
    return Math.floor(day.getTime() / 1000);
};

const snapStartTimestamp = (timestamp, openFrom, openUntil, duration, intervalSeconds) => {
    const minStart = Number(openFrom || 0);
    const maxStart = Math.max(minStart, Number(openUntil || 0) - Math.max(1, Number(duration || 0)));
    const interval = Math.max(1, Number(intervalSeconds || 0));
    const raw = Math.max(minStart, Math.min(Number(timestamp || 0), maxStart));
    const stepsFromOpen = Math.ceil((raw - minStart) / interval);
    const snapped = minStart + (Math.max(0, stepsFromOpen) * interval);
    return Math.max(minStart, Math.min(snapped, maxStart));
};

const renderCustomDayEditor = (container, daySlot, hiddenStartInput, durationSelect) => {
    container.innerHTML = '';
    if (!daySlot || !hiddenStartInput || !durationSelect) {
        return;
    }

    const openFrom = Number(daySlot.openfrom || 0);
    const openUntil = Number(daySlot.openuntil || 0);
    const startIntervalMinutes = Math.max(1, Number(daySlot.startintervalminutes || 30));
    const startIntervalSeconds = startIntervalMinutes * 60;
    if (openFrom <= 0 || openUntil <= openFrom) {
        return;
    }

    const existingStart = Number(hiddenStartInput.value || 0);
    const selectedDuration = Number(durationSelect.value || 0);
    const defaultStart = Math.max(openFrom, Math.min(existingStart || openFrom, openUntil - Math.max(1, selectedDuration)));

    const info = document.createElement('div');
    info.className = 'small text-muted mb-2';
    info.textContent = `${daySlot.daylabel}: ${toLocalTimeValue(openFrom)} - ${toLocalTimeValue(openUntil)}`;
    container.appendChild(info);

    const controls = document.createElement('div');
    controls.className = 'd-flex align-items-center gap-2 mb-2';

    const label = document.createElement('label');
    label.className = 'small mb-0';
    label.textContent = 'Start';
    controls.appendChild(label);

    const timeInput = document.createElement('input');
    timeInput.type = 'time';
    timeInput.className = 'form-control form-control-sm';
    timeInput.style.maxWidth = '10rem';
    timeInput.step = String(startIntervalSeconds);
    timeInput.min = toLocalTimeValue(openFrom);
    timeInput.max = toLocalTimeValue(openUntil);
    timeInput.value = toLocalTimeValue(defaultStart);
    controls.appendChild(timeInput);

    container.appendChild(controls);

    const timelineWrapper = document.createElement('div');
    timelineWrapper.className = 'd-flex align-items-stretch gap-1';
    container.appendChild(timelineWrapper);

    const labelsCol = document.createElement('div');
    labelsCol.className = 'position-relative flex-shrink-0';
    labelsCol.style.width = '2.8rem';
    labelsCol.style.height = '140px';
    timelineWrapper.appendChild(labelsCol);

    const timeline = document.createElement('div');
    timeline.className = 'border rounded position-relative flex-grow-1';
    timeline.style.height = '140px';
    timeline.style.background = 'linear-gradient(to bottom, #f8f9fa, #ffffff)';
    timeline.style.cursor = 'crosshair';
    timeline.style.overflow = 'hidden';
    timelineWrapper.appendChild(timeline);

    const timelineSpan = openUntil - openFrom;
    if (timelineSpan > 0) {
        const tickCandidates = [5 * 60, 10 * 60, 15 * 60, 20 * 60, 30 * 60, 3600, 2 * 3600, 3 * 3600];
        const tickInterval = tickCandidates.find(c => timelineSpan / c <= 8) || 3600;
        const firstTick = Math.ceil(openFrom / tickInterval) * tickInterval;
        for (let tick = firstTick; tick <= openUntil; tick += tickInterval) {
            const ratio = (tick - openFrom) / timelineSpan;

            const lbl = document.createElement('div');
            lbl.className = 'position-absolute text-muted';
            lbl.style.top = `${ratio * 100}%`;
            lbl.style.transform = 'translateY(-50%)';
            lbl.style.left = '0';
            lbl.style.right = '0';
            lbl.style.fontSize = '0.65rem';
            lbl.style.lineHeight = '1';
            lbl.style.textAlign = 'right';
            lbl.style.whiteSpace = 'nowrap';
            lbl.textContent = toLocalTimeValue(tick);
            labelsCol.appendChild(lbl);

            const tickLine = document.createElement('div');
            tickLine.className = 'position-absolute';
            tickLine.style.left = '0';
            tickLine.style.right = '0';
            tickLine.style.top = `${ratio * 100}%`;
            tickLine.style.height = '1px';
            tickLine.style.background = 'rgba(0,0,0,0.10)';
            tickLine.style.pointerEvents = 'none';
            timeline.appendChild(tickLine);
        }
    }

    const addBookedBlock = (start, end) => {
        const span = openUntil - openFrom;
        if (span <= 0) {
            return;
        }

        const clippedStart = Math.max(openFrom, Number(start || 0));
        const clippedEnd = Math.min(openUntil, Number(end || 0));
        if (clippedEnd <= clippedStart) {
            return;
        }

        const top = ((clippedStart - openFrom) / span) * 100;
        const height = ((clippedEnd - clippedStart) / span) * 100;

        const block = document.createElement('div');
        block.className = 'position-absolute';
        block.style.left = '0';
        block.style.right = '0';
        block.style.top = `${top}%`;
        block.style.height = `${Math.max(2, height)}%`;
        block.style.background = 'rgba(220,53,69,0.18)';
        block.style.borderTop = '1px solid rgba(220,53,69,0.35)';
        block.style.borderBottom = '1px solid rgba(220,53,69,0.35)';
        timeline.appendChild(block);
    };

    (Array.isArray(daySlot.bookedranges) ? daySlot.bookedranges : []).forEach(range => {
        addBookedBlock(range.start, range.end);
    });

    const selectionBlock = document.createElement('div');
    selectionBlock.className = 'position-absolute';
    selectionBlock.style.left = '0';
    selectionBlock.style.right = '0';
    selectionBlock.style.top = '0';
    selectionBlock.style.height = '2px';
    selectionBlock.style.background = 'rgba(13,110,253,0.20)';
    selectionBlock.style.borderTop = '1px solid rgba(13,110,253,0.75)';
    selectionBlock.style.borderBottom = '1px solid rgba(13,110,253,0.75)';
    timeline.appendChild(selectionBlock);

    const syncStart = (timestamp) => {
        const duration = Math.max(1, Number(durationSelect.value || 0));
        const clamped = snapStartTimestamp(
            timestamp,
            openFrom,
            openUntil,
            duration,
            startIntervalSeconds
        );
        hiddenStartInput.value = String(clamped);
        timeInput.value = toLocalTimeValue(clamped);

        const span = openUntil - openFrom;
        const top = span > 0 ? ((clamped - openFrom) / span) * 100 : 0;
        const height = span > 0 ? (duration / span) * 100 : 0;
        selectionBlock.style.top = `${Math.max(0, Math.min(100, top))}%`;
        selectionBlock.style.height = `${Math.max(2, Math.min(100, height))}%`;
    };

    timeInput.addEventListener('change', () => {
        syncStart(toTimestampForDay(daySlot.start, timeInput.value));
    });

    durationSelect.addEventListener('change', () => {
        syncStart(Number(hiddenStartInput.value || openFrom));
    });

    timeline.addEventListener('click', (event) => {
        const rect = timeline.getBoundingClientRect();
        const ratio = rect.height > 0 ? (event.clientY - rect.top) / rect.height : 0;
        const timestamp = openFrom + Math.round((openUntil - openFrom) * Math.max(0, Math.min(1, ratio)));
        syncStart(timestamp);
    });

    syncStart(defaultStart);
};

const getSelectedSlotKeys = (selectionInput) => {
    if (!selectionInput) {
        return [];
    }

    if (selectionInput.tagName === 'SELECT') {
        if (selectionInput.multiple) {
            return Array.from(selectionInput.selectedOptions || [])
                .map(option => String(option.value || '').trim())
                .filter(Boolean);
        }

        const singleValue = String(selectionInput.value || '').trim();
        return singleValue ? [singleValue] : [];
    }

    return String(selectionInput.value || '')
        .split(',')
        .map(value => value.trim())
        .filter(Boolean);
};

const ensureTeacherContainer = (container, anchor) => {
    let teacherContainer = container.querySelector('[data-region="slot-teacher-selection"]');
    if (teacherContainer) {
        return teacherContainer;
    }

    teacherContainer = document.createElement('div');
    teacherContainer.dataset.region = 'slot-teacher-selection';
    teacherContainer.className = 'mt-3';

    if (anchor && anchor.parentNode) {
        anchor.parentNode.insertBefore(teacherContainer, anchor.nextSibling);
    } else {
        container.appendChild(teacherContainer);
    }

    return teacherContainer;
};

const renderTeacherSelection = (
    teacherContainer,
    selectedSlotKeys,
    slotsMap,
    requiredCount,
    hiddenInput,
    examinersLabel
) => {
    const currentSelection = parseTeacherSelection(hiddenInput);

    const selectedSet = new Set(selectedSlotKeys);
    Object.keys(currentSelection).forEach(slotKey => {
        if (!selectedSet.has(slotKey)) {
            delete currentSelection[slotKey];
        }
    });

    teacherContainer.innerHTML = '';

    if (requiredCount <= 0 || selectedSlotKeys.length === 0) {
        serializeTeacherSelection(hiddenInput, {});
        return;
    }

    const heading = document.createElement('div');
    heading.className = 'small fw-bold mb-2';
    heading.textContent = `${examinersLabel}: ${requiredCount}`;
    teacherContainer.appendChild(heading);

    selectedSlotKeys.forEach(slotKey => {
        const slot = slotsMap.get(slotKey);
        if (!slot) {
            return;
        }

        const row = document.createElement('div');
        row.className = 'mb-2 p-2 border rounded';

        const slotLabel = document.createElement('div');
        slotLabel.className = 'small fw-bold mb-1';
        slotLabel.textContent = `${slot.daylabel || ''} · ${slot.timelabel || slotKey}`;
        row.appendChild(slotLabel);

        const teachers = Array.isArray(slot.teachers) ? slot.teachers : [];
        const availableIds = teachers
            .map(teacher => Number(teacher.id || 0))
            .filter(id => id > 0);

        const existing = Array.isArray(currentSelection[slotKey]) ? currentSelection[slotKey] : [];
        const preselected = existing
            .map(id => Number(id || 0))
            .filter(id => id > 0 && availableIds.includes(id));

        const select = document.createElement('select');
        select.className = 'form-control form-control-sm';
        select.dataset.slotKey = slotKey;

        if (requiredCount > 1) {
            select.multiple = true;
            select.size = Math.min(8, Math.max(requiredCount + 1, teachers.length));
        } else {
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = '-';
            select.appendChild(emptyOption);
        }

        teachers.forEach(teacher => {
            const id = Number(teacher.id || 0);
            if (id <= 0) {
                return;
            }

            const option = document.createElement('option');
            option.value = String(id);
            option.textContent = String(teacher.fullname || id);
            option.selected = preselected.includes(id);
            select.appendChild(option);
        });

        const persistSelection = () => {
            const selectedIds = Array.from(select.selectedOptions || [])
                .map(option => Number(option.value || 0))
                .filter(id => id > 0);

            const normalized = Array.from(new Set(selectedIds));
            if (requiredCount > 0 && normalized.length > requiredCount) {
                normalized.splice(requiredCount);
            }

            if (normalized.length === 0) {
                delete currentSelection[slotKey];
            } else {
                currentSelection[slotKey] = normalized;
            }

            serializeTeacherSelection(hiddenInput, currentSelection);
        };

        select.addEventListener('change', persistSelection);
        row.appendChild(select);

        teacherContainer.appendChild(row);
    });

    serializeTeacherSelection(hiddenInput, currentSelection);
};

/**
 * Init function.
 */
export async function init() {
    let container = document.querySelector('div.modal.show ' + SELECTOR.FORMCONTAINER);

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

    const optionid = container.dataset.optionid;
    const userid = container.dataset.userid;

    const dynamicForm = new DynamicForm(container.querySelector('[data-region="slotbooking-form"]'),
        'mod_booking\\form\\condition\\slotbooking_form');

    await dynamicForm.load({
        id: optionid,
        userid,
    });
    const setupInteractiveUi = () => {
        const calendarRoot = container.querySelector('[data-region="slot-calendar-picker"]');
        const selectionInput = getSelectionInput(container);
        const jsonInput = container.querySelector('input[name="slot_calendar_data"]');
        const customEditorRoot = container.querySelector('[data-region="slot-custom-editor"]');
        const customStartInput = container.querySelector('input[name="slot_custom_start"]');
        const customDurationSelect = container.querySelector('select[name="slot_custom_duration"]');
        const teacherSelectionInput = container.querySelector('input[name="slot_teacher_selection"]');
        const examinersLabelInput = container.querySelector('input[name="slot_examiners_per_slot_label"]');
        const teachersRequiredInput = container.querySelector('input[name="slot_teachers_required_count"]');
        const examinersLabel = (examinersLabelInput?.value || 'Examiners per slot').trim();

        if (!selectionInput) {
            return;
        }

        const slots = parseSlots(jsonInput);

        if (calendarRoot && customStartInput && customDurationSelect && customEditorRoot && slots.length > 0) {
            if (!calendarRoot.dataset.slotCalendarInitialized) {
                initSlotCalendarPicker(calendarRoot, {
                    slots,
                    maxSelection: 1,
                    dayCountFormatter: (daySlots) => {
                        const daySlot = Array.isArray(daySlots) ? daySlots[0] : null;
                        return daySlot && daySlot.bookable ? 'Buchbar' : 'Nicht buchbar';
                    },
                    dayStateResolver: (daySlots) => {
                        const daySlot = Array.isArray(daySlots) ? daySlots[0] : null;
                        return daySlot && daySlot.bookable ? '' : 'full';
                    },
                    slotFilter: () => false,
                    emptySlotListText: '',
                    onChange: () => {
                        // Custom mode persists start/duration via dedicated inputs.
                    },
                    onDayChange: (dayKey) => {
                        const daySlot = slots.find(slot => {
                            const d = new Date(Number(slot.start) * 1000);
                            const year = d.getFullYear();
                            const month = String(d.getMonth() + 1).padStart(2, '0');
                            const day = String(d.getDate()).padStart(2, '0');
                            const key = `${year}-${month}-${day}`;
                            return key === dayKey;
                        }) || null;

                        renderCustomDayEditor(customEditorRoot, daySlot, customStartInput, customDurationSelect);
                    },
                });

                calendarRoot.dataset.slotCalendarInitialized = '1';
                if (slots.length > 0) {
                    renderCustomDayEditor(customEditorRoot, slots[0], customStartInput, customDurationSelect);
                }
            }

            return;
        }

        const slotsMap = new Map();
        slots.forEach(slot => {
            const key = String(slot.key || `${slot.start}:${slot.end}`);
            slotsMap.set(key, slot);
        });

        const teacherContainer = ensureTeacherContainer(container, calendarRoot || selectionInput);
        const teachersRequired = Math.max(0, Number(teachersRequiredInput?.value || 0));

        const refreshTeacherSelection = () => {
            const selectedSlotKeys = getSelectedSlotKeys(selectionInput);
            renderTeacherSelection(
                teacherContainer,
                selectedSlotKeys,
                slotsMap,
                teachersRequired,
                teacherSelectionInput,
                examinersLabel
            );
        };

        if (calendarRoot && !calendarRoot.dataset.slotCalendarInitialized) {
            const maxInput = container.querySelector('input[name="slot_max_selection"]');

            const initialSelection = selectionInput.value
                ? selectionInput.value.split(',').map(value => value.trim()).filter(Boolean)
                : [];

            initSlotCalendarPicker(calendarRoot, {
                slots,
                maxSelection: Number(maxInput?.value || 1),
                initialSelection,
                onChange: (selection) => {
                    selectionInput.value = selection.join(',');
                    selectionInput.dispatchEvent(new Event('change', {bubbles: true}));
                },
            });

            calendarRoot.dataset.slotCalendarInitialized = '1';
        }

        if (!selectionInput.dataset.slotSelectionBound) {
            selectionInput.addEventListener('change', refreshTeacherSelection);
            selectionInput.dataset.slotSelectionBound = '1';
        }

        refreshTeacherSelection();
    };

    setupInteractiveUi();

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

    dynamicForm.addEventListener(dynamicForm.events.SERVER_VALIDATION_ERROR, () => {
        setupInteractiveUi();
        showValidationFeedback(container);
    });

    dynamicForm.addEventListener(dynamicForm.events.CLIENT_VALIDATION_ERROR, () => {
        setupInteractiveUi();
        showValidationFeedback(container);
    });

    if (continuebutton) {
        continuebutton.dataset.blocked = 'true';

        continuebutton.addEventListener('click', (event) => {
            if (continuebutton.dataset.blocked === 'true') {
                event.preventDefault();
                event.stopPropagation();
                dynamicForm.submitFormAjax();
            }
        });
    }
}

/**
 * Show first validation error from the current prepage form.
 *
 * @param {HTMLElement} container
 */
function showValidationFeedback(container) {
    const validationMessages = Array.from(container.querySelectorAll('.invalid-feedback'))
        .map(element => (element.textContent || '').trim())
        .filter(Boolean);

    if (validationMessages.length > 0) {
        Notification.addNotification({
            message: validationMessages[0],
            type: 'warning',
        });
    }
}

/**
 * Function to check visibility of element.
 * @param {*} el
 * @returns {boolean}
 */
function isHidden(el) {
    var style = window.getComputedStyle(el);
    return ((style.display === 'none') || (style.visibility === 'hidden'));
}
