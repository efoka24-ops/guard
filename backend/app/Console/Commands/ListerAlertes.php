<?php

namespace App\Console\Commands;

use App\Models\Organisation;
use App\Services\CommandCenter\Models\Alerte;
use Illuminate\Console\Command;

/**
 * T049 — Vue minimale des alertes actives, pour validation manuelle du
 * parcours GUARD ENDPOINT sans attendre le dashboard React (Command Center
 * complet, hors périmètre de ce premier incrément).
 */
class ListerAlertes extends Command
{
    protected $signature = 'alertes:lister {organisation_id? : Filtre sur une organisation}';

    protected $description = 'Liste les alertes actives (nouvelle/accusee_reception/assignee) avec leur SLA';

    public function handle(): int
    {
        $query = Alerte::query()
            ->whereIn('statut', ['nouvelle', 'accusee_reception', 'assignee']);

        if ($organisationId = $this->argument('organisation_id')) {
            $query->where('organisation_id', $organisationId);
        }

        // Tri en PHP plutôt qu'en SQL (FIELD() est spécifique à MySQL, non
        // portable vers SQLite utilisé en dev local, cf. research.md §2).
        $ordreCriticite = ['critique' => 0, 'eleve' => 1, 'moyen' => 2, 'faible' => 3, 'info' => 4];
        $alertes = $query->get()->sortBy([
            fn (Alerte $a, Alerte $b) => ($ordreCriticite[$a->niveau_criticite] ?? 5) <=> ($ordreCriticite[$b->niveau_criticite] ?? 5),
            fn (Alerte $a, Alerte $b) => $a->sla_echeance_le <=> $b->sla_echeance_le,
        ]);

        if ($alertes->isEmpty()) {
            $this->info('Aucune alerte active.');

            return self::SUCCESS;
        }

        $this->table(
            ['Organisation', 'Module', 'Criticité', 'Titre', 'Statut', 'SLA'],
            $alertes->map(function (Alerte $alerte) {
                $nomOrganisation = Organisation::find($alerte->organisation_id)?->nom ?? $alerte->organisation_id;
                $slaDepasse = $alerte->sla_echeance_le->isPast() ? ' [DÉPASSÉ]' : '';

                return [
                    $nomOrganisation,
                    $alerte->module_source,
                    strtoupper($alerte->niveau_criticite),
                    $alerte->titre,
                    $alerte->statut,
                    $alerte->sla_echeance_le->diffForHumans().$slaDepasse,
                ];
            })
        );

        return self::SUCCESS;
    }
}
