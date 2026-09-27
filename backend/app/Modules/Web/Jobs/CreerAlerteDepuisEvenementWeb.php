<?php

namespace App\Modules\Web\Jobs;

use App\Modules\Web\Models\EvenementWeb;
use App\Services\CommandCenter\Models\Alerte;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** T069 — même patron que CreerAlerteDepuisScan (ENDPOINT). */
class CreerAlerteDepuisEvenementWeb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(private readonly string $evenementWebId) {}

    public function handle(): void
    {
        $evenement = EvenementWeb::with('siteWeb')->find($this->evenementWebId);

        if (! $evenement || ! $evenement->estAlertant()) {
            return;
        }

        if (! $evenement->siteWeb) {
            Log::channel('guard_alerts')->warning('Alerte WEB non créée : site introuvable', [
                'evenement_web_id' => $evenement->id,
            ]);

            return;
        }

        $niveauCriticite = match ($evenement->type) {
            'defacement', 'ssl_expire' => 'critique',
            'ssl_expiration_proche' => 'moyen',
            default => 'info',
        };

        $titre = match ($evenement->type) {
            'defacement' => "Défacement détecté sur {$evenement->siteWeb->url}",
            'ssl_expire' => "Certificat SSL expiré sur {$evenement->siteWeb->url}",
            'ssl_expiration_proche' => "Certificat SSL bientôt expiré sur {$evenement->siteWeb->url}",
            default => "Événement WEB sur {$evenement->siteWeb->url}",
        };

        $alerte = Alerte::create([
            'organisation_id' => $evenement->siteWeb->organisation_id,
            'module_source' => 'WEB',
            'site_web_id' => $evenement->site_web_id,
            'niveau_criticite' => $niveauCriticite,
            'titre' => $titre,
            'description_technique' => json_encode($evenement->detail),
            'statut' => 'nouvelle',
            'sla_echeance_le' => Alerte::slaPour($niveauCriticite),
        ]);

        Log::channel('guard_alerts')->info('Alerte WEB créée', [
            'alerte_id' => $alerte->id,
            'evenement_web_id' => $evenement->id,
            'type' => $evenement->type,
        ]);
    }
}
