<?php

namespace Tests\Feature\CommandCenter;

use App\Models\Organisation;
use App\Models\User;
use App\Services\CommandCenter\Models\Alerte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** T020 — GET /alerts, POST /alerts/{id}/acknowledge (contracts/endpoint-sync-api.yaml). */
class AlertsTest extends TestCase
{
    use RefreshDatabase;

    private function unUtilisateurAuthentifie(): User
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);

        return User::create([
            'name' => 'IT Manager',
            'email' => 'it@acme.test',
            'password' => bcrypt('secret'),
            'organisation_id' => $organisation->id,
            'role' => 'it_manager',
        ]);
    }

    public function test_refuse_lacces_sans_authentification(): void
    {
        $this->getJson('/api/v1/alerts')->assertUnauthorized();
    }

    public function test_liste_uniquement_les_alertes_de_lorganisation_de_lutilisateur(): void
    {
        $utilisateur = $this->unUtilisateurAuthentifie();
        $autreOrganisation = Organisation::create(['nom' => 'Autre', 'pays' => 'RW']);

        Alerte::create([
            'organisation_id' => $utilisateur->organisation_id,
            'module_source' => 'ENDPOINT',
            'niveau_criticite' => 'critique',
            'titre' => 'Alerte ACME',
            'statut' => 'nouvelle',
            'sla_echeance_le' => now()->addDay(),
        ]);
        Alerte::create([
            'organisation_id' => $autreOrganisation->id,
            'module_source' => 'ENDPOINT',
            'niveau_criticite' => 'critique',
            'titre' => 'Alerte Autre — ne doit pas apparaître',
            'statut' => 'nouvelle',
            'sla_echeance_le' => now()->addDay(),
        ]);

        $response = $this->actingAs($utilisateur, 'sanctum')->getJson('/api/v1/alerts');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Alerte ACME');
    }

    public function test_accuser_reception_dune_alerte(): void
    {
        $utilisateur = $this->unUtilisateurAuthentifie();
        $alerte = Alerte::create([
            'organisation_id' => $utilisateur->organisation_id,
            'module_source' => 'ENDPOINT',
            'niveau_criticite' => 'critique',
            'titre' => 'Alerte ACME',
            'statut' => 'nouvelle',
            'sla_echeance_le' => now()->addDay(),
        ]);

        $response = $this->actingAs($utilisateur, 'sanctum')
            ->postJson("/api/v1/alerts/{$alerte->id}/acknowledge");

        $response->assertOk()->assertJsonPath('status', 'accusee_reception');
        $this->assertSame('accusee_reception', $alerte->fresh()->statut);
    }
}
