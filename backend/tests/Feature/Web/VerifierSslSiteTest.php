<?php

namespace Tests\Feature\Web;

use App\Models\Organisation;
use App\Modules\Web\Jobs\CreerAlerteDepuisEvenementWeb;
use App\Modules\Web\Jobs\VerifierSslSite;
use App\Modules\Web\Models\SiteWebSurveille;
use App\Modules\Web\Services\LecteurCertificatSsl;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * T075-T077 — Aucun appel réseau réel : LecteurCertificatSsl est remplacé
 * par un double de test retournant une date d'expiration fixe.
 */
class VerifierSslSiteTest extends TestCase
{
    use RefreshDatabase;

    private function unSite(): SiteWebSurveille
    {
        $organisation = Organisation::create(['nom' => 'ACME', 'pays' => 'CM']);

        return SiteWebSurveille::create([
            'organisation_id' => $organisation->id,
            'url' => 'https://exemple.cm',
            'statut' => 'actif',
        ]);
    }

    private function lecteurAvecExpirationDans(int $jours): LecteurCertificatSsl
    {
        return new class($jours) extends LecteurCertificatSsl
        {
            public function __construct(private readonly int $jours) {}

            public function expirationLe(string $host, int $port = 443): ?Carbon
            {
                return now()->addDays($this->jours);
            }
        };
    }

    public function test_certificat_expirant_dans_35_jours_ne_declenche_aucune_alerte(): void
    {
        // 35 jours est strictement au-dessus du plus grand seuil (30) : le
        // certificat vient d'être détecté loin de toute échéance. À 25 jours,
        // le seuil J-30 serait déjà franchi et DOIT alerter (comportement
        // volontaire, pas un oubli : mieux vaut alerter tard qu'omettre un
        // certificat ajouté après son 30e jour restant).
        Queue::fake();
        $site = $this->unSite();

        (new VerifierSslSite($site->id))->handle($this->lecteurAvecExpirationDans(35));

        $this->assertSame('valide', $site->fresh()->statut_ssl);
        $this->assertDatabaseCount('evenements_web', 0);
        Queue::assertNothingPushed();
    }

    public function test_certificat_expirant_dans_6_jours_cree_une_alerte_une_seule_fois(): void
    {
        Queue::fake();
        $site = $this->unSite();

        (new VerifierSslSite($site->id))->handle($this->lecteurAvecExpirationDans(6));
        (new VerifierSslSite($site->id))->handle($this->lecteurAvecExpirationDans(6)); // ré-exécution du cron

        $this->assertSame('expire_bientot', $site->fresh()->statut_ssl);
        $this->assertDatabaseCount('evenements_web', 1);
        Queue::assertPushed(CreerAlerteDepuisEvenementWeb::class, 1);
    }

    public function test_certificat_deja_expire_declenche_une_alerte_critique(): void
    {
        Queue::fake();
        $site = $this->unSite();

        (new VerifierSslSite($site->id))->handle($this->lecteurAvecExpirationDans(-3));

        $this->assertSame('expire', $site->fresh()->statut_ssl);
        $this->assertDatabaseHas('evenements_web', ['site_web_id' => $site->id, 'type' => 'ssl_expire']);
        Queue::assertPushed(CreerAlerteDepuisEvenementWeb::class);
    }
}
