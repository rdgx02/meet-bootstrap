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
    const idsInput = form.querySelector('input[name="ids"]');
    const message = modalElement.querySelector('[data-delete-message]');
    const submitLabel = modalElement.querySelector('[data-delete-submit-label]');
    const singleSummary = modalElement.querySelector('[data-delete-single-summary]');
    const bulkSummary = modalElement.querySelector('[data-delete-bulk-summary]');
    const bulkCount = modalElement.querySelector('[data-delete-bulk-count]');
    const bulkList = modalElement.querySelector('[data-delete-bulk-list]');
    const summaryFields = {
        title: modalElement.querySelector('[data-delete-summary="title"]'),
        date: modalElement.querySelector('[data-delete-summary="date"]'),
        time: modalElement.querySelector('[data-delete-summary="time"]'),
        room: modalElement.querySelector('[data-delete-summary="room"]'),
    };

    modalElement.addEventListener('hidden.bs.modal', () => {
        form.action = '';
        idsInput.value = '';
        message.textContent = 'Este registro sera removido da base operacional e nao podera ser recuperado.';
        submitLabel.textContent = 'Confirmar exclusao';
        singleSummary.classList.remove('d-none');
        bulkSummary.classList.add('d-none');
        bulkCount.textContent = '0 agendamentos';
        bulkList.innerHTML = '';
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

const initReservationBulkToolbar = () => {
    if (document.body.dataset.reservationBulkToolbarReady === '1') {
        return;
    }

    const getSelectedInputs = (tableName) =>
        Array.from(
            document.querySelectorAll(`input[data-pg-bulk-table="${tableName}"]:checked`)
        );

    const requireSingleSelection = (tableName, actionLabel) => {
        const selectedInputs = getSelectedInputs(tableName);

        if (selectedInputs.length === 0) {
            window.alert(`Selecione um agendamento para ${actionLabel}.`);
            return null;
        }

        if (selectedInputs.length > 1) {
            window.alert(`Selecione apenas um agendamento para ${actionLabel}.`);
            return null;
        }

        return selectedInputs[0];
    };

    document.addEventListener('click', (event) => {
        const toolbar = event.target.closest('[data-reservation-toolbar]');

        if (!toolbar) {
            return;
        }

        const tableName = toolbar.dataset.tableName;

        if (event.target.closest('.js-reservation-bulk-view')) {
            event.preventDefault();

            const input = requireSingleSelection(tableName, 'visualizar');

            if (!input) {
                return;
            }

            window.location.href = input.dataset.showUrl;
            return;
        }

        if (event.target.closest('.js-reservation-bulk-edit')) {
            event.preventDefault();

            const input = requireSingleSelection(tableName, 'editar');

            if (!input) {
                return;
            }

            window.location.href = input.dataset.editUrl;
            return;
        }

        if (event.target.closest('.js-reservation-bulk-delete')) {
            event.preventDefault();

            const selectedInputs = getSelectedInputs(tableName);

            if (selectedInputs.length === 0) {
                window.alert('Selecione ao menos um agendamento para excluir.');
                return;
            }

            const modalElement = document.getElementById('reservationDeleteModal');

            if (!modalElement) {
                return;
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const form = modalElement.querySelector('[data-delete-form]');
            const idsInput = form.querySelector('input[name="ids"]');
            const message = modalElement.querySelector('[data-delete-message]');
            const submitLabel = modalElement.querySelector('[data-delete-submit-label]');
            const singleSummary = modalElement.querySelector('[data-delete-single-summary]');
            const bulkSummary = modalElement.querySelector('[data-delete-bulk-summary]');
            const bulkCount = modalElement.querySelector('[data-delete-bulk-count]');
            const bulkList = modalElement.querySelector('[data-delete-bulk-list]');
            const summaryFields = {
                title: modalElement.querySelector('[data-delete-summary="title"]'),
                date: modalElement.querySelector('[data-delete-summary="date"]'),
                time: modalElement.querySelector('[data-delete-summary="time"]'),
                room: modalElement.querySelector('[data-delete-summary="room"]'),
            };

            if (selectedInputs.length === 1) {
                const input = selectedInputs[0];

                form.action = input.dataset.deleteUrl;
                idsInput.value = '';
                message.textContent = 'Este registro sera removido da base operacional e nao podera ser recuperado.';
                submitLabel.textContent = 'Confirmar exclusao';
                singleSummary.classList.remove('d-none');
                bulkSummary.classList.add('d-none');
                bulkCount.textContent = '0 agendamentos';
                bulkList.innerHTML = '';
                summaryFields.title.textContent = input.dataset.title ?? '-';
                summaryFields.date.textContent = input.dataset.date ?? '-';
                summaryFields.time.textContent = input.dataset.time ?? '-';
                summaryFields.room.textContent = input.dataset.room ?? '-';
            } else {
                form.action = form.dataset.deleteSelectedUrl;
                idsInput.value = selectedInputs.map((input) => input.dataset.reservationId).join(',');
                message.textContent = 'Os agendamentos selecionados serao removidos da base operacional. Essa acao nao podera ser desfeita.';
                submitLabel.textContent = 'Excluir selecionados';
                singleSummary.classList.add('d-none');
                bulkSummary.classList.remove('d-none');
                bulkCount.textContent = `${selectedInputs.length} agendamentos`;
                bulkList.innerHTML = selectedInputs
                    .slice(0, 6)
                    .map((input) => `
                        <div class="lims-bulk-delete-item">
                            <strong>${input.dataset.title ?? '-'}</strong>
                            <small>${input.dataset.date ?? '-'} | ${input.dataset.time ?? '-'} | Sala ${input.dataset.room ?? '-'}</small>
                        </div>
                    `)
                    .join('');

                if (selectedInputs.length > 6) {
                    bulkList.insertAdjacentHTML(
                        'beforeend',
                        `<div class="lims-bulk-delete-item"><small>Mais ${selectedInputs.length - 6} agendamentos selecionados.</small></div>`
                    );
                }
            }

            modal.show();
            return;
        }

        const exportButton = event.target.closest('.js-reservation-bulk-export');

        if (exportButton) {
            event.preventDefault();

            const selectedInputs = getSelectedInputs(tableName);

            if (selectedInputs.length === 0) {
                window.alert('Selecione ao menos um agendamento para exportar.');
                return;
            }

            const ids = selectedInputs.map((input) => input.dataset.reservationId).join(',');
            const exportUrl = new URL(exportButton.dataset.exportUrl, window.location.origin);

            exportUrl.searchParams.set('ids', ids);
            window.location.href = exportUrl.toString();
        }
    });

    document.body.dataset.reservationBulkToolbarReady = '1';
};

const initStickyGridHeaders = () => {
    if (document.body.dataset.stickyGridHeadersReady === '1') {
        return;
    }

    let syncScheduled = false;

    const syncStickyOffsets = () => {
        syncScheduled = false;

        document.querySelectorAll('.app-grid-table-wrap').forEach((wrap) => {
            const firstHeaderRow = wrap.querySelector('.power-grid-table thead tr');
            const filterRow = wrap.querySelector('.power-grid-table thead tr.app-grid-inline-filters');

            if (!firstHeaderRow) {
                wrap.style.removeProperty('--app-grid-sticky-header-offset');
                return;
            }

            const firstRowHeight = Math.ceil(firstHeaderRow.getBoundingClientRect().height);

            wrap.style.setProperty('--app-grid-sticky-header-offset', `${firstRowHeight}px`);
            wrap.classList.toggle('app-grid-has-inline-filters', Boolean(filterRow));
        });
    };

    const queueStickyOffsetSync = () => {
        if (syncScheduled) {
            return;
        }

        syncScheduled = true;
        window.requestAnimationFrame(syncStickyOffsets);
    };

    const observer = new MutationObserver(() => {
        queueStickyOffsetSync();
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class', 'style'],
    });

    window.addEventListener('resize', queueStickyOffsetSync);
    window.addEventListener('load', queueStickyOffsetSync);

    if (document.fonts?.ready) {
        document.fonts.ready.then(queueStickyOffsetSync);
    }

    queueStickyOffsetSync();
    document.body.dataset.stickyGridHeadersReady = '1';
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReservationDeleteModal);
    document.addEventListener('DOMContentLoaded', initRoomDeleteModal);
    document.addEventListener('DOMContentLoaded', initReservationBulkToolbar);
    document.addEventListener('DOMContentLoaded', initStickyGridHeaders);
} else {
    initReservationDeleteModal();
    initRoomDeleteModal();
    initReservationBulkToolbar();
    initStickyGridHeaders();
}
