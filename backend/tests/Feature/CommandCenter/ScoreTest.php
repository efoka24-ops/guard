<?php

namespace Tests\Feature\CommandCenter;

use App\Models\Organisation;
use App\Models\User;
use App\Services\CommandCenter\Models\Alerte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** T048 — GET /organisations/{id}/score. */
class ScoreTest extends TestCase
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

    public function test_score_100_sans_alerte_active(): void
    {
        $utilisateur = $this->unUtilisateurAuthentifie();

        $response = $this->actingAs($utilisateur, 'sanctum')
            ->getJson("/api/v1/organisations/{$utilisateur->organisation_id}/score");

        $response->assertOk()->assertJsonPath('score', 100);
    }

    public function test_score_penalise_par_alerte_critique(): void
    {
        $utilisateur = $this->unUtilisateurAuthentifie();

        Alerte::create([
            'organisation_id' => $utilisateur->organisation_id,
            'module_source' => 'ENDPOINT',
            'niveau_criticite' => 'critique',
            'titre' => 'Test',
            'statut' => 'nouvelle',
            'sla_echeance_le' => now()->addDay(),
        ]);

        $response = $this->actingAs($utilisateur, 'sanctum')
            ->getJson("/api/v1/organisations/{$utilisateur->organisation_id}/score");

        $response->assertOk()->assertJsonPath('score', 75); // 100 - 25 (critique)
    }

    public function test_alerte_resolue_ne_penalise_plus_le_score(): void
    {
        $utilisateur = $this->unUtilisateurAuthentifie();

        Alerte::create([
            'organisation_id' => $utilisateur->organisation_id,
            'module_source' => 'ENDPOINT',
            'niveau_criticite' => 'critique',
            'titre' => 'Test',
            'statut' => 'resolue',
            'sla_echeance_le' => now()->addDay(),
        ]);

        $response = $this->actingAs($utilisateur, 'sanctum')
            ->getJson("/api/v1/organisations/{$utilisateur->organisation_id}/score");

        $response->assertOk()->assertJsonPath('score', 100);
    }

    public function test_refuse_lacces_au_score_dune_autre_organisation(): void
    {
        $utilisateur = $this->unUtilisateurAuthentifie();
        $autreOrganisation = Organisation::create(['nom' => 'Autre', 'pays' => 'RW']);

        $response = $this->actingAs($utilisateur, 'sanctum')
            ->getJson("/api/v1/organisations/{$autreOrganisation->id}/score");

        $response->assertForbidden();
    }
}
