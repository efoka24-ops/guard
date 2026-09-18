<?php

namespace Tests\Feature\Endpoint;

use App\Models\Organisation;
use App\Modules\Endpoint\Models\VersionSignatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Endpoint\Concerns\InteractsWithTerminalAuth;
use Tests\TestCase;

/** T018 — GET /signatures/delta (contracts/endpoint-sync-api.yaml). */
class SignatureDeltaTest extends TestCase
{
    use InteractsWithTerminalAuth, RefreshDatabase;

    public function test_refuse_lacces_sans_token_terminal(): void
    {
        $this->getJson('/api/v1/signatures/delta')->assertUnauthorized();
    }

    public function test_retourne_204_quand_aucune_signature_publiee(): void
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);
        ['token' => $token] = $this->enregistrerTerminal($organisation);

        $this->enTantQueTerminal($token)
            ->getJson('/api/v1/signatures/delta')
            ->assertNoContent();
    }

    public function test_retourne_le_paquet_complet_sans_from_version(): void
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);
        ['token' => $token] = $this->enregistrerTerminal($organisation);

        VersionSignatures::create([
            'numero_version' => '2026.01.01',
            'taille_delta_ko' => 1,
            'hashes_ajoutes' => ['a'.str_repeat('0', 63)],
            'regles_yara_ajoutees' => ['rule A { condition: true }'],
            'checksum_delta' => 'checksum-1',
            'publie_le' => now()->subDay(),
        ]);

        $response = $this->enTantQueTerminal($token)->getJson('/api/v1/signatures/delta');

        $response->assertOk()
            ->assertJsonPath('version', '2026.01.01')
            ->assertJsonCount(1, 'hashes_added');
    }

    public function test_retourne_uniquement_le_delta_depuis_from_version(): void
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);
        ['token' => $token] = $this->enregistrerTerminal($organisation);

        VersionSignatures::create([
            'numero_version' => '2026.01.01',
            'taille_delta_ko' => 1,
            'hashes_ajoutes' => ['a'.str_repeat('0', 63)],
            'regles_yara_ajoutees' => [],
            'checksum_delta' => 'checksum-1',
            'publie_le' => now()->subDays(7),
        ]);
        VersionSignatures::create([
            'numero_version' => '2026.01.08',
            'taille_delta_ko' => 1,
            'hashes_ajoutes' => ['b'.str_repeat('0', 63)],
            'regles_yara_ajoutees' => [],
            'checksum_delta' => 'checksum-2',
            'publie_le' => now(),
        ]);

        $response = $this->enTantQueTerminal($token)
            ->getJson('/api/v1/signatures/delta?from_version=2026.01.01');

        $response->assertOk()
            ->assertJsonPath('version', '2026.01.08')
            ->assertJsonPath('hashes_added.0', 'b'.str_repeat('0', 63));
    }
}
