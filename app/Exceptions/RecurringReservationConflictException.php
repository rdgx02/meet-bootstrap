<?php

namespace App\Exceptions;

use DomainException;

class RecurringReservationConflictException extends DomainException
{
    public function __construct(
        private readonly array $conflicts,
        string $message = 'Existem conflitos nas ocorrências informadas.'
    ) {
        parent::__construct($message);
    }

    public static function forOccurrences(array $conflicts): self
    {
        $count = count($conflicts);

        return new self(
            $conflicts,
            $count === 1
                ? 'Existe 1 ocorrência com conflito no período informado.'
                : sprintf('Existem %d ocorrências com conflito no período informado.', $count)
        );
    }

    public function conflicts(): array
    {
        return $this->conflicts;
    }
}
