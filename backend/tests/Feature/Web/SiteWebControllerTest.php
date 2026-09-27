<?php

namespace Tests\Feature\Web;

use App\Models\Organisation;
use App\Models\User;
use App\Modules\Web\Models\SiteWebSurveille;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** T081-T082 */
class SiteWebControllerTest extends TestCase
{
    use RefreshDatabase;

    private function unUtilisateurAuthentifie(string $organisationNom = 'ACME'): User
    {
        $organisation = Organisation::create(['nom' => $organisationNom, 'pays' => 'CM']);

        return User::create([
            'name' => 'IT Manager',
            'email' => strtolower($organisationNom).'@acme.test',
            'password' => bcrypt('secret'),
            'organisation_id' => $organisation->id,
            'role' => 'it_manager',
        ]);
    }

    public function test_refuse_lacces_sans_authentification(): void
    {
        $this->getJson('/api/v1/sites-web')->assertUnauthorized();
    }

    public function test_cree_un_site_scope_a_lorganisation_de_lutilisateur_connecte(): void
    {
        $utilisateur = $this->unUtilisateurAuthentifie();

        $response = $this->actingAs($utilisateur, 'sanctum')
            ->postJson('/api/v1/sites-web', ['url' => 'https://exemple.cm']);

        $response->assertCreated()->assertJsonPath('url', 'https://exemple.cm');

        $this->assertDatabaseHas('sites_web_surveilles', [
            'organisation_id' => $utilisateur->organisation_id,
            'url' => 'https://exemple.cm',
        ]);
    }

    public function test_liste_uniquement_les_sites_de_lorganisation_de_lutilisateur(): void
    {
        $utilisateur = $this->unUtilisateurAuthentifie('ACME');
        $autre = $this->unUtilisateurAuthentifie('Autre');

        SiteWebSurveille::create([
            'organisation_id' => $utilisateur->organisation_id,
            'url' => 'https://acme.cm',
            'statut' => 'actif',
        ]);
        SiteWebSurveille::create([
            'organisation_id' => $autre->organisation_id,
            'url' => 'https://autre.cm',
            'statut' => 'actif',
        ]);

        $response = $this->actingAs($utilisateur, 'sanctum')->getJson('/api/v1/sites-web');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.url', 'https://acme.cm');
    }
}
