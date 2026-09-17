<?php

namespace App\Services\CommandCenter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CommandCenter\Models\Alerte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** T032 — GET /alerts, POST /alerts/{id}/acknowledge (contracts/endpoint-sync-api.yaml). */
class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'in:nouvelle,accusee_reception,assignee,resolue,ignoree'],
            'severity' => ['sometimes', 'in:info,faible,moyen,eleve,critique'],
        ]);

        $alertes = Alerte::query()
            ->where('organisation_id', $request->user()->organisation_id)
            ->when($request->query('status'), fn ($q, $statut) => $q->where('statut', $statut))
            ->when($request->query('severity'), fn ($q, $niveau) => $q->where('niveau_criticite', $niveau))
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($alertes->through(fn (Alerte $a) => [
            'id' => $a->id,
            'module_source' => $a->module_source,
            'severity' => $a->niveau_criticite,
            'title' => $a->titre,
            'status' => $a->statut,
            'sla_due_at' => $a->sla_echeance_le,
            'created_at' => $a->created_at,
        ]));
    }

    public function acknowledge(string $id): JsonResponse
    {
        $alerte = Alerte::where('organisation_id', request()->user()->organisation_id)->findOrFail($id);

        $alerte->update(['statut' => 'accusee_reception']);

        return response()->json(['id' => $alerte->id, 'status' => $alerte->statut]);
    }
}
