<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Str;

class EvolutionWhatsAppService
{
    public function __construct(
        private readonly HttpFactory $http
    ) {}

    public function enabled(): bool
    {
        return (bool) config('services.evolution_whatsapp.enabled')
            && filled(config('services.evolution_whatsapp.base_url'))
            && filled(config('services.evolution_whatsapp.instance'))
            && filled(config('services.evolution_whatsapp.api_key'));
    }

    public function sendText(string $phone, string $message): void
    {
        $endpoint = sprintf(
            '%s/message/sendText/%s',
            rtrim((string) config('services.evolution_whatsapp.base_url'), '/'),
            trim((string) config('services.evolution_whatsapp.instance'))
        );

        $this->http->asJson()
            ->withHeaders([
                'apikey' => (string) config('services.evolution_whatsapp.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->timeout((int) config('services.evolution_whatsapp.timeout', 10))
            ->post($endpoint, [
                'number' => $this->normalizePhoneForProvider($phone),
                'text' => $message,
            ])
            ->throw();
    }

    private function normalizePhoneForProvider(string $phone): string
    {
        return Str::of($phone)->replaceMatches('/\D+/', '')->value();
    }
}
