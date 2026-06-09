<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Janela de expediente
    |--------------------------------------------------------------------------
    |
    | Faixa de horário (hora de parede, formato HH:MM) em que reservas podem
    | ocorrer. Usada tanto pela validação de backend (App\Rules\WithinBusinessHours)
    | quanto pela tela de Disponibilidade. Fonte única — não repita 08/18 no código.
    |
    */

    'business_hours' => [
        'start' => env('RESERVATION_OPENING_TIME', '08:00'),
        'end' => env('RESERVATION_CLOSING_TIME', '18:00'),
    ],

];
