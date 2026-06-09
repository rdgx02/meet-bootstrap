<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Habilita o WhatsApp (Evolution) no modo enfileirado para os testes, de forma
     * autossuficiente — incluindo credenciais fake, para que enabled() retorne true
     * sem depender das variáveis EVOLUTION_WHATSAPP_* do ambiente (.env local vs CI).
     *
     * Use com Queue::fake(): o job é apenas enfileirado, nunca executado, então as
     * credenciais fake são inertes (não há chamada de rede à API).
     */
    protected function fakeEvolutionWhatsApp(): void
    {
        config([
            'services.evolution_whatsapp.enabled' => true,
            'services.evolution_whatsapp.queue' => true,
            'services.evolution_whatsapp.base_url' => 'https://whatsapp.test',
            'services.evolution_whatsapp.instance' => 'test-instance',
            'services.evolution_whatsapp.api_key' => 'test-key',
        ]);
    }
}
