<?php

namespace Tests\Feature\Endpoint\Concerns;

use App\Models\Organisation;
use App\Modules\Endpoint\Models\Terminal;

/** Enregistre un terminal via l'API réelle pour obtenir un access_token valide (corrige M3, revue backend). */
trait InteractsWithTerminalAuth
{
    private function enregistrerTerminal(Organisation $organisation, string $deviceHash = 'device-abc'): array
    {
        $response = $this->postJson('/api/v1/terminals/register', [
            'device_hash' => $deviceHash,
            'platform' => 'android',
            'app_version' => '0.1.0',
            'organisation_token' => $organisation->token_enrolement,
        ])->assertCreated();

        return [
            'terminal' => Terminal::findOrFail($response->json('id')),
            'token' => $response->json('access_token'),
        ];
    }

    private function enTantQueTerminal(string $token): self
    {
        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }
}
