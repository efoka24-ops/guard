<?php

namespace Tests\Feature\Endpoint;

use App\Models\Organisation;
use App\Modules\Endpoint\Jobs\CreerAlerteDepuisScan;
use App\Modules\Endpoint\Models\Terminal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/** T019 — POST /scan-events, y compris idempotence (contracts/endpoint-sync-api.yaml). */
class ScanEventsTest extends TestCase
{
    use RefreshDatabase;

    private function unTerminal(): Terminal
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);

        return Terminal::create([
            'organisation_id' => $organisation->id,
            'plateforme' => 'android',
            'identifiant_appareil' => 'device-abc',
            'version_app' => '0.1.0',
        ]);
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

    public function test_accepte_un_lot_devenements_et_202(): void
    {
        Queue::fake();
        $terminal = $this->unTerminal();

        $response = $this->postJson('/api/v1/scan-events', [
            'terminal_id' => $terminal->id,
            'events' => [$this->unEvenement()],
        ]);

        $response->assertStatus(202);
        $this->assertDatabaseCount('evenements_scan', 1);
    }

    public function test_idempotent_sur_client_event_id_deja_recu(): void
    {
        Queue::fake();
        $terminal = $this->unTerminal();
        $event = $this->unEvenement();

        $this->postJson('/api/v1/scan-events', ['terminal_id' => $terminal->id, 'events' => [$event]]);
        $this->postJson('/api/v1/scan-events', ['terminal_id' => $terminal->id, 'events' => [$event]]);

        $this->assertDatabaseCount('evenements_scan', 1);
    }

    public function test_dispatch_le_job_de_creation_dalerte_pour_un_malware_confirme(): void
    {
        Queue::fake();
        $terminal = $this->unTerminal();

        $this->postJson('/api/v1/scan-events', [
            'terminal_id' => $terminal->id,
            'events' => [$this->unEvenement(['classification' => 'malware_confirme'])],
        ]);

        Queue::assertPushed(CreerAlerteDepuisScan::class);
    }
}
