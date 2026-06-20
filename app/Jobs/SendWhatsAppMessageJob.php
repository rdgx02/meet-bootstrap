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
            'phone' => self::maskPhone($this->phone),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Mascara o telefone para o log (LGPD): mantém só os 4 últimos dígitos.
     */
    public static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) <= 4) {
            return '****';
        }

        return str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }
}
