<?php

namespace App\Exceptions;

use DomainException;

class RecurringReservationConflictException extends DomainException
{
    public function __construct(
        private readonly array $conflicts,
        string $message = 'Existem conflitos nas ocorrencias informadas.'
    ) {
        parent::__construct($message);
    }

    public static function forOccurrences(array $conflicts): self
    {
        $count = count($conflicts);

        return new self(
            $conflicts,
            $count === 1
                ? 'Existe 1 ocorrencia com conflito no periodo informado.'
                : sprintf('Existem %d ocorrencias com conflito no periodo informado.', $count)
        );
    }

    public function conflicts(): array
    {
        return $this->conflicts;
    }
}
