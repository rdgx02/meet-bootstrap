import './bootstrap';

import Alpine from 'alpinejs';
import * as bootstrap from 'bootstrap';
import '../../vendor/power-components/livewire-powergrid/dist/powergrid';
import flatpickr from 'flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';

window.Alpine = Alpine;

Alpine.start();

flatpickr.localize(Portuguese);

const initDatePickers = () => {
    const dateInputs = document.querySelectorAll('input.js-date-picker');
    const timeInputs = document.querySelectorAll('input.js-time-picker');

    dateInputs.forEach((input) => {
        if (input.dataset.flatpickrReady === '1') {
            return;
        }

        const options = {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            locale: Portuguese,
            disableMobile: true,
            monthSelectorType: 'static',
            prevArrow:
                '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"/></svg>',
            nextArrow:
                '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"/></svg>',
        };

        if (input.dataset.minDate) {
            options.minDate = input.dataset.minDate;
        }

        if (input.dataset.maxDate) {
            options.maxDate = input.dataset.maxDate;
        }

        flatpickr(input, options);
        input.dataset.flatpickrReady = '1';
    });

    timeInputs.forEach((input) => {
        if (input.dataset.flatpickrReady === '1') {
            return;
        }

        flatpickr(input, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            minuteIncrement: 5,
            disableMobile: true,
            locale: Portuguese,
        });

        input.dataset.flatpickrReady = '1';
    });

    const dateFrom = document.querySelector('input[name="date_from"]');
    const dateTo = document.querySelector('input[name="date_to"]');

    if (
        dateFrom?._flatpickr &&
        dateTo?._flatpickr &&
        dateFrom.dataset.rangeSyncReady !== '1'
    ) {
        const syncRangeLimits = () => {
            const fromDate = dateFrom._flatpickr.selectedDates[0] ?? null;
            const toDate = dateTo._flatpickr.selectedDates[0] ?? null;

            dateTo._flatpickr.set('minDate', fromDate);
            dateFrom._flatpickr.set('maxDate', toDate);
        };

        syncRangeLimits();
        dateFrom._flatpickr.config.onChange.push(syncRangeLimits);
        dateTo._flatpickr.config.onChange.push(syncRangeLimits);
        dateFrom.dataset.rangeSyncReady = '1';
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDatePickers);
} else {
    initDatePickers();
}

const initReservationDeleteModal = () => {
    const modalElement = document.getElementById('reservationDeleteModal');

    if (!modalElement || document.body.dataset.reservationDeleteModalReady === '1') {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const form = modalElement.querySelector('[data-delete-form]');
    const summaryFields = {
        title: modalElement.querySelector('[data-delete-summary="title"]'),
        date: modalElement.querySelector('[data-delete-summary="date"]'),
        time: modalElement.querySelector('[data-delete-summary="time"]'),
        room: modalElement.querySelector('[data-delete-summary="room"]'),
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.js-reservation-delete-trigger');

        if (!trigger) {
            return;
        }

        form.action = trigger.dataset.deleteUrl;
        summaryFields.title.textContent = trigger.dataset.title ?? '-';
        summaryFields.date.textContent = trigger.dataset.date ?? '-';
        summaryFields.time.textContent = trigger.dataset.time ?? '-';
        summaryFields.room.textContent = trigger.dataset.room ?? '-';

        modal.show();
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        form.action = '';
        Object.values(summaryFields).forEach((field) => {
            field.textContent = '-';
        });
    });

    document.body.dataset.reservationDeleteModalReady = '1';
};

const initRoomDeleteModal = () => {
    const modalElement = document.getElementById('roomDeleteModal');

    if (!modalElement || document.body.dataset.roomDeleteModalReady === '1') {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const form = modalElement.querySelector('[data-room-delete-form]');
    const summaryFields = {
        name: modalElement.querySelector('[data-room-delete-summary="name"]'),
        status: modalElement.querySelector('[data-room-delete-summary="status"]'),
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.js-room-delete-trigger');

        if (!trigger) {
            return;
        }

        form.action = trigger.dataset.roomDeleteUrl;
        summaryFields.name.textContent = trigger.dataset.roomName ?? '-';
        summaryFields.status.textContent = trigger.dataset.roomStatus ?? '-';

        modal.show();
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        form.action = '';
        Object.values(summaryFields).forEach((field) => {
            field.textContent = '-';
        });
    });

    document.body.dataset.roomDeleteModalReady = '1';
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReservationDeleteModal);
    document.addEventListener('DOMContentLoaded', initRoomDeleteModal);
} else {
    initReservationDeleteModal();
    initRoomDeleteModal();
}
