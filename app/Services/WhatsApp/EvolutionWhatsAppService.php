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
            && $this->hasValidBaseUrl()
            && filled(config('services.evolution_whatsapp.instance'))
            && filled(config('services.evolution_whatsapp.api_key'));
    }

    /**
     * Garante que a base_url é uma URL http(s) válida antes de enviar a apikey
     * para ela — evita SSRF/exfiltração de credencial por config malformada.
     */
    private function hasValidBaseUrl(): bool
    {
        $baseUrl = (string) config('services.evolution_whatsapp.base_url');

        if (! filled($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)), ['http', 'https'], true);
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
