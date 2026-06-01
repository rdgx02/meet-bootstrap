<?php

namespace App\Jobs;

use App\Services\WhatsApp\EvolutionWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
        public readonly string $contextType,
        public readonly int $contextId
    ) {}

    public function handle(EvolutionWhatsAppService $evolutionWhatsApp): void
    {
        if (trim($this->phone) === '' || trim($this->message) === '') {
            return;
        }

        if (! $evolutionWhatsApp->enabled()) {
            return;
        }

        $evolutionWhatsApp->sendText($this->phone, $this->message);
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Falha ao enviar mensagem de WhatsApp.', [
            'context_type' => $this->contextType,
            'context_id' => $this->contextId,
            'phone' => $this->phone,
            'error' => $exception->getMessage(),
        ]);
    }
}
