<?php

namespace Tests\Feature\Endpoint;

use App\Models\Organisation;
use App\Models\User;
use App\Services\CommandCenter\Models\Alerte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Endpoint\Concerns\InteractsWithTerminalAuth;
use Tests\TestCase;

/**
 * T021 — Parcours complet de spec.md User Story 1 / quickstart.md §6 :
 * EICAR offline -> sync -> alerte critique dans le délai SLA (SC-001, FR-024).
 * Reproduit le scénario validé manuellement lors de l'implémentation T017-T036.
 */
class EndToEndScanTest extends TestCase
{
    use InteractsWithTerminalAuth, RefreshDatabase;

    private const HASH_EICAR = '275a021bbfb6489e54d471899f7db9d1663fc695ec2fe2a2c4538aabf651fd0f';

    public function test_scan_eicar_offline_declenche_une_alerte_critique_sous_le_sla(): void
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);

        // 1. Terminal enregistré (peut avoir eu lieu il y a plusieurs jours,
        //    hors ligne) — l'access_token est conservé localement par l'agent.
        ['terminal' => $terminal, 'token' => $token] = $this->enregistrerTerminal(
            $organisation,
            'device-eicar-test'
        );

        // 2. Le terminal détecte l'EICAR hors ligne (niveau 1 hash), puis se
        //    resynchronise plus tard — occurred_at antérieur à l'envoi.
        $this->enTantQueTerminal($token)->postJson('/api/v1/scan-events', [
            'terminal_id' => $terminal->id,
            'events' => [[
                'client_event_id' => (string) Str::uuid(),
                'trigger' => 'usb',
                'sha256' => self::HASH_EICAR,
                'level_reached' => '1_hash',
                'classification' => 'malware_confirme',
                'action_taken' => 'quarantaine_auto',
                'occurred_at' => now()->subDays(3)->toIso8601String(),
            ]],
        ])->assertStatus(202);

        // 3. Le job de création d'alerte tourne en synchrone dans les tests
        //    (QUEUE_CONNECTION=sync par défaut dans phpunit.xml) : l'alerte
        //    doit donc déjà exister.
        $alerte = Alerte::where('organisation_id', $organisation->id)->first();
        $this->assertNotNull($alerte, "L'alerte n'a pas été créée après le scan EICAR");
        $this->assertSame('critique', $alerte->niveau_criticite);
        $this->assertTrue(
            $alerte->sla_echeance_le->lessThanOrEqualTo($alerte->created_at->addHours(24)),
            'Le SLA doit être <= 24h pour une alerte critique (FR-024)'
        );

        // 4. L'alerte est visible et acquittable via le Command Center.
        $utilisateur = User::create([
            'name' => 'IT Manager',
            'email' => 'it@acme.test',
            'password' => bcrypt('secret'),
            'organisation_id' => $organisation->id,
            'role' => 'it_manager',
        ]);

        $this->actingAs($utilisateur, 'sanctum')
            ->getJson('/api/v1/alerts')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($utilisateur, 'sanctum')
            ->postJson("/api/v1/alerts/{$alerte->id}/acknowledge")
            ->assertOk()
            ->assertJsonPath('status', 'accusee_reception');
    }
}
