@php
    $inputAttributes = new \Illuminate\View\ComponentAttributeBag([
        'class' => theme_style($theme, 'checkbox.input'),
    ]);

    $rules = collect($row->__powergrid_rules)
        ->where('apply', true)
        ->where('forAction', \PowerComponents\LivewirePowerGrid\Components\Rules\RuleManager::TYPE_CHECKBOX)
        ->last();

    if (isset($rules['attributes'])) {
        foreach ($rules['attributes'] as $key => $value) {
            $inputAttributes = $inputAttributes->merge([
                $key => $value,
            ]);
        }
    }

    $disable = (bool) data_get($rules, 'disable');
    $hide = (bool) data_get($rules, 'hide');

@endphp

@if ($hide)
    <td
        class="{{ theme_style($theme, 'checkbox.th') }}"
    >
    </td>
@elseif($disable)
    <td
        class="{{ theme_style($theme, 'checkbox.th') }}"
    >
        <div class="{{ theme_style($theme, 'checkbox.base') }}">
            <label class="{{ theme_style($theme, 'checkbox.label') }}">
                <input
                    {{ $inputAttributes }}
                    disabled
                    type="checkbox"
                >
            </label>
        </div>
    </td>
@else
    <td
        class="{{ theme_style($theme, 'checkbox.th') }}"
    >
        @php
            $isReservationTable = in_array($tableName, ['reservations-upcoming-table', 'reservations-history-table'], true);
        @endphp
        <div class="{{ theme_style($theme, 'checkbox.base') }}">
            <label class="{{ theme_style($theme, 'checkbox.label') }}">
                <input
                    type="checkbox"
                    {{ $inputAttributes }}
                    wire:model="checkboxValues"
                    value="{{ $attribute }}"
                    data-pg-bulk-table="{{ $tableName }}"
                    @if ($isReservationTable)
                        data-reservation-id="{{ $attribute }}"
                        data-show-url="{{ route('reservations.show', $attribute) }}"
                        data-edit-url="{{ route('reservations.edit', $attribute) }}"
                        data-delete-url="{{ route('reservations.destroy', $attribute) }}"
                        data-title="{{ data_get($row, 'title', '-') }}"
                        data-date="{{ \Carbon\Carbon::parse(data_get($row, 'date'))->format('d/m/Y') }}"
                        data-time="{{ \Carbon\Carbon::parse(data_get($row, 'start_time'))->format('H:i') }} - {{ \Carbon\Carbon::parse(data_get($row, 'end_time'))->format('H:i') }}"
                        data-room="{{ data_get($row, 'room.name', '-') }}"
                    @endif
                >
            </label>
        </div>
    </td>
@endif
