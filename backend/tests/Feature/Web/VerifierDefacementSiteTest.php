<?php

namespace Tests\Feature\Web;

use App\Models\Organisation;
use App\Modules\Web\Jobs\CreerAlerteDepuisEvenementWeb;
use App\Modules\Web\Jobs\VerifierDefacementSite;
use App\Modules\Web\Models\SiteWebSurveille;
use App\Modules\Web\Services\NormalisateurHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** T065-T067 */
class VerifierDefacementSiteTest extends TestCase
{
    use RefreshDatabase;

    private function unSite(array $overrides = []): SiteWebSurveille
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);

        return SiteWebSurveille::create(array_merge([
            'organisation_id' => $organisation->id,
            'url' => 'https://exemple.cm',
            'statut' => 'actif',
        ], $overrides));
    }

    public function test_contenu_identique_ne_cree_aucun_evenement(): void
    {
        Queue::fake();
        $normalisateur = new NormalisateurHtml;
        $html = '<html><body>Bienvenue chez ACME</body></html>';
        $site = $this->unSite(['hash_page_accueil' => $normalisateur->hash($html)]);

        Http::fake(['exemple.cm' => Http::response($html, 200)]);

        (new VerifierDefacementSite($site->id))->handle($normalisateur);

        $this->assertDatabaseCount('evenements_web', 0);
        Queue::assertNothingPushed();
    }

    public function test_contenu_different_cree_un_evenement_de_defacement_et_dispatche_lalerte(): void
    {
        Queue::fake();
        $normalisateur = new NormalisateurHtml;
        $site = $this->unSite(['hash_page_accueil' => $normalisateur->hash('<html>Ancien contenu</html>')]);

        Http::fake(['exemple.cm' => Http::response('<html>DEFACE PAR UN ATTAQUANT</html>', 200)]);

        (new VerifierDefacementSite($site->id))->handle($normalisateur);

        $this->assertDatabaseHas('evenements_web', [
            'site_web_id' => $site->id,
            'type' => 'defacement',
        ]);
        Queue::assertPushed(CreerAlerteDepuisEvenementWeb::class);
    }

    public function test_site_injoignable_cree_un_evenement_indisponible_sans_alerte_de_defacement(): void
    {
        Queue::fake();
        $normalisateur = new NormalisateurHtml;
        $site = $this->unSite(['hash_page_accueil' => $normalisateur->hash('<html>Ancien contenu</html>')]);

        Http::fake(['exemple.cm' => Http::response('', 503)]);

        (new VerifierDefacementSite($site->id))->handle($normalisateur);

        $this->assertDatabaseHas('evenements_web', [
            'site_web_id' => $site->id,
            'type' => 'indisponible',
        ]);
        $this->assertDatabaseMissing('evenements_web', ['type' => 'defacement']);
        Queue::assertNothingPushed();
    }

    public function test_premier_passage_pose_la_reference_sans_alerte(): void
    {
        Queue::fake();
        $normalisateur = new NormalisateurHtml;
        $site = $this->unSite(['hash_page_accueil' => null]);

        Http::fake(['exemple.cm' => Http::response('<html>Contenu initial</html>', 200)]);

        (new VerifierDefacementSite($site->id))->handle($normalisateur);

        $this->assertNotNull($site->fresh()->hash_page_accueil);
        $this->assertDatabaseCount('evenements_web', 0);
        Queue::assertNothingPushed();
    }
}
