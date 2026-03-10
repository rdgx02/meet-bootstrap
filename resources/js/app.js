import './bootstrap';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';

window.Alpine = Alpine;

Alpine.start();

flatpickr.localize(Portuguese);

const initDatePickers = () => {
    const dateInputs = document.querySelectorAll('input.js-date-picker');

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
