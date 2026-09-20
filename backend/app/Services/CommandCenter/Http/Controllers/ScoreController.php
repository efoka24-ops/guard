<?php

namespace App\Services\CommandCenter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * T048 — GET /organisations/{id}/score : score de sécurité global (FR-023,
 * EF-CC-01). Calculé à partir des alertes encore actives (non résolues/
 * ignorées) de l'organisation, pondérées par criticité — même logique de
 * principe que le scoring GUARD CODE (EF-CD-24) de la SFD, adaptée aux
 * alertes multi-modules plutôt qu'aux findings de code.
 */
class ScoreController extends Controller
{
    private const POIDS_PAR_CRITICITE = [
        'critique' => 25,
        'eleve' => 10,
        'moyen' => 5,
        'faible' => 2,
        'info' => 0,
    ];

    public function show(Request $request, string $organisationId): JsonResponse
    {
        // Même cloisonnement que AlertController : un utilisateur ne voit
        // que le score de sa propre organisation (sauf admin_guard).
        if ($request->user()->role !== 'admin_guard' && $request->user()->organisation_id !== $organisationId) {
            abort(403);
        }

        $organisation = Organisation::findOrFail($organisationId);

        $statutsActifs = ['nouvelle', 'accusee_reception', 'assignee'];

        $comptesParCriticite = $organisation->alertes()
            ->whereIn('statut', $statutsActifs)
            ->selectRaw('niveau_criticite, count(*) as total')
            ->groupBy('niveau_criticite')
            ->pluck('total', 'niveau_criticite');

        $penalite = 0;
        foreach ($comptesParCriticite as $criticite => $total) {
            $penalite += ($total * (self::POIDS_PAR_CRITICITE[$criticite] ?? 0));
        }

        $score = max(0, 100 - $penalite);

        return response()->json([
            'organisation_id' => $organisation->id,
            'score' => $score,
            'alertes_actives_par_criticite' => $comptesParCriticite,
        ]);
    }
}
