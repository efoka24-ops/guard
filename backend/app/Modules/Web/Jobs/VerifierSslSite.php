<?php

namespace App\Modules\Web\Jobs;

use App\Modules\Web\Models\EvenementWeb;
use App\Modules\Web\Models\SiteWebSurveille;
use App\Modules\Web\Services\LecteurCertificatSsl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** T078-T079 — Suivi SSL (User Story 3 scénario 3, spec.md §58). */
class VerifierSslSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    /** Seuils d'alerte, du plus lointain au plus proche (spec.md §58). */
    private const SEUILS_JOURS = [30, 14, 7, 1];

    public function __construct(private readonly string $siteWebId) {}

    public function handle(LecteurCertificatSsl $lecteur): void
    {
        $site = SiteWebSurveille::find($this->siteWebId);

        if (! $site || $site->statut !== 'actif') {
            return;
        }

        $host = parse_url($site->url, PHP_URL_HOST) ?: $site->url;
        $expiration = $lecteur->expirationLe($host);

        if ($expiration === null) {
            $site->update(['statut_ssl' => 'absent']);

            return;
        }

        $joursRestants = now()->diffInDays($expiration, false);

        if ($joursRestants < 0) {
            $site->update(['statut_ssl' => 'expire', 'ssl_expiration_le' => $expiration->toDateString()]);
            $this->creerEvenementSiPasDeja($site, 'ssl_expire', null);

            return;
        }

        $seuilAtteint = null;
        foreach (self::SEUILS_JOURS as $seuil) {
            if ($joursRestants <= $seuil) {
                $seuilAtteint = $seuil;
            }
        }

        $statutSsl = $seuilAtteint !== null ? 'expire_bientot' : 'valide';
        $site->update(['statut_ssl' => $statutSsl, 'ssl_expiration_le' => $expiration->toDateString()]);

        if ($seuilAtteint !== null) {
            $this->creerEvenementSiPasDeja($site, 'ssl_expiration_proche', $seuilAtteint);
        }
    }

    /**
     * Idempotence (T076) : un seuil donné ne déclenche jamais deux fois la
     * même alerte pour un même site — on ne recrée l'événement que si aucun
     * événement de ce type et de ce seuil n'existe déjà pour ce site.
     */
    private function creerEvenementSiPasDeja(SiteWebSurveille $site, string $type, ?int $seuil): void
    {
        $dejaCree = EvenementWeb::where('site_web_id', $site->id)
            ->where('type', $type)
            ->when($seuil !== null, fn ($q) => $q->whereJsonContains('detail->seuil_jours', $seuil))
            ->exists();

        if ($dejaCree) {
            return;
        }

        $evenement = EvenementWeb::create([
            'site_web_id' => $site->id,
            'type' => $type,
            'detail' => $seuil !== null ? ['seuil_jours' => $seuil] : null,
            'detecte_le' => now(),
        ]);

        CreerAlerteDepuisEvenementWeb::dispatch($evenement->id);
    }
}
