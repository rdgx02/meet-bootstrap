<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Garante que um horário (HH:MM) está dentro da janela de expediente configurada
 * em config/reservations.php.
 *
 * Como start_time/end_time são colunas TIME (hora de parede, sem data/fuso), a
 * comparação é feita por string "HH:MM" zero-paddada — cronologicamente correta
 * e independente de timezone.
 *
 * - 'start': válido em [abertura, fechamento) — não pode iniciar no fechamento.
 * - 'end':   válido em (abertura, fechamento] — pode terminar exatamente no fechamento.
 */
class WithinBusinessHours implements ValidationRule
{
    public function __construct(
        private readonly string $bound = 'start'
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Formato é responsabilidade do date_format:H:i; aqui só validamos a janela.
        if (! is_string($value) || preg_match('/^\d{2}:\d{2}$/', $value) !== 1) {
            return;
        }

        $open = (string) config('reservations.business_hours.start', '08:00');
        $close = (string) config('reservations.business_hours.end', '18:00');

        if ($this->bound === 'start') {
            if ($value < $open || $value >= $close) {
                $fail("O horário de início deve estar entre {$open} e {$close} (e antes do fechamento).");
            }

            return;
        }

        if ($value <= $open || $value > $close) {
            $fail("O horário de fim deve estar entre {$open} e {$close}.");
        }
    }
}
