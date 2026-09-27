<?php

namespace Tests\Feature\Web;

use App\Models\Organisation;
use App\Modules\Web\Jobs\VerifierEnTetesSite;
use App\Modules\Web\Models\SiteWebSurveille;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** T071-T072 */
class VerifierEnTetesSiteTest extends TestCase
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

    public function test_entetes_manquants_creent_un_evenement_avec_le_detail(): void
    {
        $site = $this->unSite();

        Http::fake(['exemple.cm' => Http::response('<html></html>', 200)]);

        (new VerifierEnTetesSite($site->id))->handle();

        $this->assertDatabaseHas('evenements_web', [
            'site_web_id' => $site->id,
            'type' => 'en_tete_manquant',
        ]);
        $evenement = $site->evenementsWeb()->where('type', 'en_tete_manquant')->first();
        $this->assertContains('Content-Security-Policy', $evenement->detail['entetes_manquants']);
        $this->assertContains('Strict-Transport-Security', $evenement->detail['entetes_manquants']);
    }

    public function test_tous_les_entetes_presents_ne_cree_aucun_evenement(): void
    {
        $site = $this->unSite();

        Http::fake(['exemple.cm' => Http::response('<html></html>', 200, [
            'Content-Security-Policy' => "default-src 'self'",
            'Strict-Transport-Security' => 'max-age=31536000',
            'X-Frame-Options' => 'DENY',
        ])]);

        (new VerifierEnTetesSite($site->id))->handle();

        $this->assertDatabaseMissing('evenements_web', ['type' => 'en_tete_manquant']);
    }

    public function test_cookie_sans_secure_ni_httponly_cree_un_evenement_dedie(): void
    {
        $site = $this->unSite();

        Http::fake(['exemple.cm' => Http::response('<html></html>', 200, [
            'Content-Security-Policy' => "default-src 'self'",
            'Strict-Transport-Security' => 'max-age=31536000',
            'X-Frame-Options' => 'DENY',
            'Set-Cookie' => 'session_id=abc123; Path=/',
        ])]);

        (new VerifierEnTetesSite($site->id))->handle();

        $this->assertDatabaseHas('evenements_web', [
            'site_web_id' => $site->id,
            'type' => 'cookie_non_securise',
        ]);
    }
}
