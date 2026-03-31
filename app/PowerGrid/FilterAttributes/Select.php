<?php

namespace App\PowerGrid\FilterAttributes;

use Illuminate\View\ComponentAttributeBag;

class Select
{
    public function __invoke(string $field, string $title): array
    {
        return [
            'selectAttributes' => new ComponentAttributeBag([
                'wire:model.live' => 'filters.select.'.$field,
                'wire:change' => 'filterSelect(\''.$field.'\', \''.addslashes($title).'\')',
            ]),
        ];
    }
}
