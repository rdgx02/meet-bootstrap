@php
    $responsiveCheckboxColumnName =
        \PowerComponents\LivewirePowerGrid\Components\SetUp\Responsive::CHECKBOX_COLUMN_NAME;

    $isCheckboxFixedOnResponsive =
        isset($this->setUp['responsive']) &&
        in_array($responsiveCheckboxColumnName, data_get($this->setUp, 'responsive.fixedColumns'));

    $isReservationTable = in_array($tableName, ['reservations-upcoming-table', 'reservations-history-table'], true);
@endphp
<th
    @if ($isCheckboxFixedOnResponsive) fixed @endif
    scope="col"
    @class([theme_style($theme, 'table.header.th'), theme_style($theme, 'checkbox.th')])
    wire:key="checkbox-all-{{ $tableName }}"
>
    <div class="{{ theme_style($theme, 'checkbox.base') }}">
        @if ($isReservationTable)
            <span
                class="d-inline-block"
                title="Selecao individual por linha para evitar marcacao em massa acidental."
                aria-hidden="true"
                style="width: 1rem;"
            >
                &nbsp;
            </span>
        @else
            <label class="{{ theme_style($theme, 'checkbox.label') }}">
                <input
                    class="{{ theme_style($theme, 'checkbox.input') }}"
                    type="checkbox"
                    wire:click="selectCheckboxAll"
                    wire:model="checkboxAll"
                >
            </label>
        @endif
    </div>
</th>
