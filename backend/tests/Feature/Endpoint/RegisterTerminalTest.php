<?php

namespace Tests\Feature\Endpoint;

use App\Models\Organisation;
use App\Modules\Endpoint\Models\Terminal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** T017 — POST /terminals/register (contracts/endpoint-sync-api.yaml). */
class RegisterTerminalTest extends TestCase
{
    use RefreshDatabase;

    public function test_enregistre_un_terminal_avec_un_token_valide(): void
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);

        $response = $this->postJson('/api/v1/terminals/register', [
            'device_hash' => 'device-abc',
            'platform' => 'android',
            'app_version' => '0.1.0',
            'organisation_token' => $organisation->token_enrolement,
        ]);

        $response->assertCreated()->assertJsonPath('status', 'actif');
        $this->assertDatabaseHas('terminaux', [
            'organisation_id' => $organisation->id,
            'identifiant_appareil' => 'device-abc',
        ]);
    }

    public function test_rejette_un_token_invalide(): void
    {
        $response = $this->postJson('/api/v1/terminals/register', [
            'device_hash' => 'device-abc',
            'platform' => 'android',
            'app_version' => '0.1.0',
            'organisation_token' => 'token-inexistant',
        ]);

        $response->assertStatus(401);
    }

    public function test_reenregistrer_le_meme_appareil_met_a_jour_le_terminal_existant(): void
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);

        $payload = [
            'device_hash' => 'device-abc',
            'platform' => 'android',
            'app_version' => '0.1.0',
            'organisation_token' => $organisation->token_enrolement,
        ];

        $this->postJson('/api/v1/terminals/register', $payload)->assertCreated();
        $payload['app_version'] = '0.2.0';
        $this->postJson('/api/v1/terminals/register', $payload)->assertCreated();

        $this->assertSame(1, Terminal::where('identifiant_appareil', 'device-abc')->count());
        $this->assertSame('0.2.0', Terminal::where('identifiant_appareil', 'device-abc')->first()->version_app);
    }
}
