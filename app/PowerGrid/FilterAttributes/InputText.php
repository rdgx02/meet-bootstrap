<?php

namespace App\PowerGrid\FilterAttributes;

use Illuminate\View\ComponentAttributeBag;

class InputText
{
    public function __invoke(string $field, string $title): array
    {
        return [
            'inputAttributes' => new ComponentAttributeBag([
                'wire:model.live.debounce.600ms' => 'filters.input_text.'.$field,
                'wire:input.debounce.600ms' => "filterInputText('{$field}', \$event.target.value, '{$title}')",
            ]),
            'selectAttributes' => new ComponentAttributeBag([
                'wire:model.live' => 'filters.input_text_options.'.$field,
                'wire:change' => "filterInputTextOptions('{$field}', \$event.target.value, '{$title}')",
            ]),
        ];
    }
}
