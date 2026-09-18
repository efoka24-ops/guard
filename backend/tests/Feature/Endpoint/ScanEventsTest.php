<?php

namespace Tests\Feature\Endpoint;

use App\Models\Organisation;
use App\Modules\Endpoint\Jobs\CreerAlerteDepuisScan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Feature\Endpoint\Concerns\InteractsWithTerminalAuth;
use Tests\TestCase;

/** T019 — POST /scan-events, y compris idempotence (contracts/endpoint-sync-api.yaml). */
class ScanEventsTest extends TestCase
{
    use InteractsWithTerminalAuth, RefreshDatabase;

    private function unTerminalAuthentifie(?string $deviceHash = null): array
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);

        // identifiant_appareil est unique globalement (terminaux) : un hash
        // distinct par défaut évite qu'un second appel n'écrase le premier
        // terminal enregistré dans le même test.
        return $this->enregistrerTerminal($organisation, $deviceHash ?? 'device-'.uniqid());
    }

    private function unEvenement(array $overrides = []): array
    {
        return array_merge([
            'client_event_id' => (string) Str::uuid(),
            'trigger' => 'usb',
            'sha256' => str_repeat('a', 64),
            'level_reached' => '1_hash',
            'classification' => 'clean',
            'action_taken' => 'aucune',
            'occurred_at' => now()->toIso8601String(),
        ], $overrides);
    }

    public function test_refuse_lacces_sans_token_terminal(): void
    {
        $this->postJson('/api/v1/scan-events', ['terminal_id' => (string) Str::uuid(), 'events' => []])
            ->assertUnauthorized();
    }

    public function test_refuse_un_terminal_id_ne_correspondant_pas_au_token(): void
    {
        ['token' => $token] = $this->unTerminalAuthentifie();
        ['terminal' => $autreTerminal] = $this->unTerminalAuthentifie();

        $this->enTantQueTerminal($token)->postJson('/api/v1/scan-events', [
            'terminal_id' => $autreTerminal->id,
            'events' => [$this->unEvenement()],
        ])->assertForbidden();
    }

    public function test_accepte_un_lot_devenements_et_202(): void
    {
        Queue::fake();
        ['terminal' => $terminal, 'token' => $token] = $this->unTerminalAuthentifie();

        $response = $this->enTantQueTerminal($token)->postJson('/api/v1/scan-events', [
            'terminal_id' => $terminal->id,
            'events' => [$this->unEvenement()],
        ]);

        $response->assertStatus(202);
        $this->assertDatabaseCount('evenements_scan', 1);
    }

    public function test_idempotent_sur_client_event_id_deja_recu(): void
    {
        Queue::fake();
        ['terminal' => $terminal, 'token' => $token] = $this->unTerminalAuthentifie();
        $event = $this->unEvenement();

        $this->enTantQueTerminal($token)->postJson('/api/v1/scan-events', ['terminal_id' => $terminal->id, 'events' => [$event]]);
        $this->enTantQueTerminal($token)->postJson('/api/v1/scan-events', ['terminal_id' => $terminal->id, 'events' => [$event]])
            ->assertStatus(409);

        $this->assertDatabaseCount('evenements_scan', 1);
    }

    public function test_dispatch_le_job_de_creation_dalerte_pour_un_malware_confirme(): void
    {
        Queue::fake();
        ['terminal' => $terminal, 'token' => $token] = $this->unTerminalAuthentifie();

        $this->enTantQueTerminal($token)->postJson('/api/v1/scan-events', [
            'terminal_id' => $terminal->id,
            'events' => [$this->unEvenement(['classification' => 'malware_confirme'])],
        ]);

        Queue::assertPushed(CreerAlerteDepuisScan::class);
    }
}
