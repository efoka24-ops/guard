<?php

namespace App\Modules\Endpoint\Jobs;

use App\Modules\Endpoint\Models\EvenementScan;
use App\Services\CommandCenter\Models\Alerte;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * T031 — Traité par le driver de queue `database` (research.md §2, pas de
 * worker long-running garanti sur mutualisé : consommé via schedule:run).
 */
class CreerAlerteDepuisScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(private readonly string $evenementScanId) {}

    public function handle(): void
    {
        $evenement = EvenementScan::with('terminal')->find($this->evenementScanId);

        if (! $evenement || ! $evenement->estAlertant()) {
            return;
        }

        $niveauCriticite = $evenement->classification === 'malware_confirme' ? 'critique' : 'eleve';

        $alerte = Alerte::create([
            'organisation_id' => $evenement->terminal->organisation_id,
            'module_source' => 'ENDPOINT',
            'terminal_id' => $evenement->terminal_id,
            'niveau_criticite' => $niveauCriticite,
            'titre' => match ($evenement->classification) {
                'malware_confirme' => 'Fichier malveillant confirmé détecté et mis en quarantaine',
                default => 'Fichier suspect détecté, action recommandée',
            },
            'description_technique' => sprintf(
                'Hash %s, déclencheur %s, niveau atteint %s, règle YARA %s',
                $evenement->hash_sha256,
                $evenement->declencheur,
                $evenement->niveau_max_atteint,
                $evenement->regle_yara_correspondante ?? 'n/a'
            ),
            'statut' => 'nouvelle',
            'sla_echeance_le' => Alerte::slaPour($niveauCriticite),
        ]);

        Log::channel('guard_alerts')->info('Alerte ENDPOINT créée', [
            'alerte_id' => $alerte->id,
            'evenement_scan_id' => $evenement->id,
            'classification' => $evenement->classification,
        ]);
    }
}
