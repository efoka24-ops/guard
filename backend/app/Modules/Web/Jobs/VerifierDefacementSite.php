<?php

namespace App\Modules\Web\Jobs;

use App\Modules\Web\Models\EvenementWeb;
use App\Modules\Web\Models\SiteWebSurveille;
use App\Modules\Web\Services\NormalisateurHtml;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * T068 — Détection de défacement (User Story 3 scénario 1, spec.md §56).
 * Aucun payload actif envoyé : une simple requête GET, comme un navigateur.
 */
class VerifierDefacementSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(private readonly string $siteWebId) {}

    public function handle(NormalisateurHtml $normalisateur): void
    {
        $site = SiteWebSurveille::find($this->siteWebId);

        if (! $site || $site->statut !== 'actif') {
            return;
        }

        try {
            $reponse = Http::timeout(10)->get($site->url);
        } catch (Throwable) {
            $this->enregistrerIndisponible($site);

            return;
        }

        if (! $reponse->successful()) {
            $this->enregistrerIndisponible($site);

            return;
        }

        $nouveauHash = $normalisateur->hash($reponse->body());
        $ancienHash = $site->hash_page_accueil;

        $site->update([
            'hash_page_accueil' => $nouveauHash,
            'derniere_verification_le' => now(),
        ]);

        // Premier passage : on pose la référence, pas d'alerte (rien à comparer).
        if ($ancienHash === null) {
            return;
        }

        if ($nouveauHash !== $ancienHash) {
            $evenement = EvenementWeb::create([
                'site_web_id' => $site->id,
                'type' => 'defacement',
                'detail' => ['hash_precedent' => $ancienHash, 'nouveau_hash' => $nouveauHash],
                'detecte_le' => now(),
            ]);

            CreerAlerteDepuisEvenementWeb::dispatch($evenement->id);
        }
    }

    private function enregistrerIndisponible(SiteWebSurveille $site): void
    {
        EvenementWeb::create([
            'site_web_id' => $site->id,
            'type' => 'indisponible',
            'detail' => null,
            'detecte_le' => now(),
        ]);

        $site->update(['derniere_verification_le' => now()]);
    }
}
