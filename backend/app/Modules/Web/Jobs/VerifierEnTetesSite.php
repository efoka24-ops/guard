<?php

namespace App\Modules\Web\Jobs;

use App\Modules\Web\Models\EvenementWeb;
use App\Modules\Web\Models\SiteWebSurveille;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * T073 — Contrôle passif des en-têtes de sécurité HTTP (User Story 3
 * scénario 2, version restreinte : aucun payload d'attaque envoyé,
 * cf. guard-web-cadrage.md §2).
 */
class VerifierEnTetesSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    /** Liste blanche des en-têtes attendus sur une réponse HTTPS. */
    private const EN_TETES_ATTENDUS = [
        'Content-Security-Policy',
        'Strict-Transport-Security',
        'X-Frame-Options',
    ];

    public function __construct(private readonly string $siteWebId) {}

    public function handle(): void
    {
        $site = SiteWebSurveille::find($this->siteWebId);

        if (! $site || $site->statut !== 'actif') {
            return;
        }

        try {
            $reponse = Http::timeout(10)->get($site->url);
        } catch (Throwable) {
            return;
        }

        if (! $reponse->successful()) {
            return;
        }

        $manquants = array_values(array_filter(
            self::EN_TETES_ATTENDUS,
            fn (string $entete) => ! $reponse->hasHeader($entete)
        ));

        if ($manquants !== []) {
            EvenementWeb::create([
                'site_web_id' => $site->id,
                'type' => 'en_tete_manquant',
                'detail' => ['entetes_manquants' => $manquants],
                'detecte_le' => now(),
            ]);
        }

        $this->verifierCookies($site, $reponse->headers()['Set-Cookie'] ?? []);
    }

    /** @param array<int, string> $cookies */
    private function verifierCookies(SiteWebSurveille $site, array $cookies): void
    {
        $nonSecurises = array_values(array_filter(
            $cookies,
            fn (string $cookie) => ! str_contains(strtolower($cookie), 'secure')
                || ! str_contains(strtolower($cookie), 'httponly')
        ));

        if ($nonSecurises !== []) {
            EvenementWeb::create([
                'site_web_id' => $site->id,
                'type' => 'cookie_non_securise',
                'detail' => ['cookies' => $nonSecurises],
                'detecte_le' => now(),
            ]);
        }
    }
}
