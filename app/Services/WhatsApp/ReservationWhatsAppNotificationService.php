<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Reservation;
use App\Models\ReservationSeries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReservationWhatsAppNotificationService
{
    public function __construct(
        private readonly EvolutionWhatsAppService $evolutionWhatsApp
    ) {}

    public function notifyReservationCreated(Reservation $reservation): void
    {
        $reservation->loadMissing(['room', 'owner']);

        $this->dispatch(
            phone: (string) $reservation->phone,
            message: implode("\n", [
                'CONFIRMAÇÃO DE AGENDAMENTO - SALAS',
                '',
                $this->reservationSummary($reservation),
                '',
                'Status: Confirmado',
            ]),
            contextType: 'reservation_created',
            contextId: (int) $reservation->id,
        );
    }

    public function notifyReservationUpdated(Reservation $reservation): void
    {
        $reservation->loadMissing(['room', 'owner']);

        $this->dispatch(
            phone: (string) $reservation->phone,
            message: implode("\n", [
                'AGENDAMENTO ATUALIZADO - SALAS',
                '',
                $this->reservationSummary($reservation),
                '',
                'Status: Atualizado',
            ]),
            contextType: 'reservation_updated',
            contextId: (int) $reservation->id,
        );
    }

    public function notifyReservationCancelled(Reservation $reservation): void
    {
        $reservation->loadMissing(['room', 'owner']);

        $this->dispatch(
            phone: (string) $reservation->phone,
            message: implode("\n", [
                'AGENDAMENTO CANCELADO - SALAS',
                '',
                $this->reservationSummary($reservation),
                '',
                'Status: Cancelado',
            ]),
            contextType: 'reservation_cancelled',
            contextId: (int) $reservation->id,
        );
    }

    public function notifySeriesCreated(ReservationSeries $series): void
    {
        $series->loadMissing(['room', 'owner']);

        $this->dispatch(
            phone: (string) $series->phone,
            message: implode("\n", [
                'SÉRIE RECORRENTE CRIADA - SALAS',
                '',
                $this->seriesSummary($series),
                '',
                'Status: Ativa',
            ]),
            contextType: 'series_created',
            contextId: (int) $series->id,
        );
    }

    public function notifySeriesUpdated(ReservationSeries $series): void
    {
        $series->loadMissing(['room', 'owner']);

        $this->dispatch(
            phone: (string) $series->phone,
            message: implode("\n", [
                'SÉRIE RECORRENTE ATUALIZADA - SALAS',
                '',
                $this->seriesSummary($series),
                '',
                'Status: Atualizada',
            ]),
            contextType: 'series_updated',
            contextId: (int) $series->id,
        );
    }

    public function notifySeriesCancelled(ReservationSeries $series): void
    {
        $series->loadMissing(['room', 'owner']);

        $this->dispatch(
            phone: (string) $series->phone,
            message: implode("\n", [
                'SÉRIE RECORRENTE CANCELADA - SALAS',
                '',
                $this->seriesSummary($series),
                '',
                'Status: Cancelada',
            ]),
            contextType: 'series_cancelled',
            contextId: (int) $series->id,
        );
    }

    public function notifySeriesTrimmed(ReservationSeries $series, string $removedFromDate): void
    {
        $series->loadMissing(['room', 'owner']);

        $this->dispatch(
            phone: (string) $series->phone,
            message: implode("\n", [
                'SÉRIE RECORRENTE AJUSTADA - SALAS',
                '',
                $this->seriesSummary($series),
                sprintf('Ocorrências removidas a partir de: %s', Carbon::parse($removedFromDate)->format('d/m/Y')),
                '',
                'Status: Ajustada',
            ]),
            contextType: 'series_trimmed',
            contextId: (int) $series->id,
        );
    }

    private function reservationSummary(Reservation $reservation): string
    {
        return implode("\n", [
            sprintf('Código: %s', $this->reservationCode((int) $reservation->id)),
            sprintf('Titular: %s', $reservation->owner?->name ?? $reservation->requester),
            sprintf('Solicitante: %s', $reservation->requester),
            sprintf('Sala: %s', $reservation->room?->name ?? '-'),
            sprintf('Data: %s', Carbon::parse($reservation->date)->format('d/m/Y')),
            sprintf(
                'Horário: %s às %s',
                Carbon::parse($reservation->start_time)->format('H:i'),
                Carbon::parse($reservation->end_time)->format('H:i')
            ),
            sprintf('Título: %s', $reservation->title),
        ]);
    }

    private function seriesSummary(ReservationSeries $series): string
    {
        return implode("\n", [
            sprintf('Código: %s', $this->seriesCode((int) $series->id)),
            sprintf('Titular: %s', $series->owner?->name ?? $series->requester),
            sprintf('Solicitante: %s', $series->requester),
            sprintf('Sala: %s', $series->room?->name ?? '-'),
            sprintf(
                'Período: %s até %s',
                Carbon::parse($series->starts_on)->format('d/m/Y'),
                Carbon::parse($series->ends_on)->format('d/m/Y')
            ),
            sprintf(
                'Horário: %s às %s',
                Carbon::parse($series->start_time)->format('H:i'),
                Carbon::parse($series->end_time)->format('H:i')
            ),
            sprintf('Título: %s', $series->title),
            sprintf('Frequência: %s', $series->frequency_label),
        ]);
    }

    private function dispatch(string $phone, string $message, string $contextType, int $contextId): void
    {
        if (! $this->evolutionWhatsApp->enabled()) {
            return;
        }

        if (trim($phone) === '' || trim($message) === '') {
            return;
        }

        if ((bool) config('services.evolution_whatsapp.queue', true)) {
            SendWhatsAppMessageJob::dispatch($phone, $message, $contextType, $contextId);

            return;
        }

        try {
            $this->evolutionWhatsApp->sendText($phone, $message);
        } catch (Throwable $exception) {
            Log::warning('Falha ao enviar mensagem de WhatsApp em modo síncrono.', [
                'context_type' => $contextType,
                'context_id' => $contextId,
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function reservationCode(int $id): string
    {
        return 'AG-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    private function seriesCode(int $id): string
    {
        return 'SR-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }
}
