<?php

namespace App\Modules\Endpoint\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Endpoint\Jobs\CreerAlerteDepuisScan;
use App\Modules\Endpoint\Models\EvenementScan;
use App\Modules\Endpoint\Models\Terminal;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** T030 — POST /scan-events, batch idempotent (contracts/endpoint-sync-api.yaml). */
class ScanEventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        /** @var Terminal $terminal authentifié par AuthenticateTerminal (corrige M3, revue backend) */
        $terminal = $request->attributes->get('terminal');

        $data = $request->validate([
            'terminal_id' => ['required', 'uuid', 'exists:terminaux,id'],
            'events' => ['required', 'array', 'min:1'],
            'events.*.client_event_id' => ['required', 'uuid'],
            'events.*.trigger' => ['required', 'in:usb,apk_install,fichier_local,sms_lien,email_piece_jointe'],
            'events.*.sha256' => ['required', 'string', 'size:64'],
            'events.*.yara_rule_matched' => ['nullable', 'string'],
            'events.*.level_reached' => ['required', 'in:1_hash,2_yara'], // 3_ia hors périmètre, cf. research.md §5
            'events.*.classification' => ['required', 'in:clean,suspect,probable_malware,malware_confirme'],
            'events.*.action_taken' => ['required', 'in:aucune,surveillance,quarantaine_proposee,quarantaine_auto'],
            'events.*.occurred_at' => ['required', 'date'],
        ]);

        if ($data['terminal_id'] !== $terminal->id) {
            abort(403, "Le token d'accès ne correspond pas à terminal_id");
        }

        $idsACreer = [];
        $doublonsConnus = 0;

        foreach ($data['events'] as $event) {
            // Idempotence (corrige M1, revue backend) : l'unicité est appliquée
            // par la contrainte DB `client_event_id` (create_evenements_scan_table),
            // pas seulement par un exists() préalable — évite la course entre
            // deux requêtes concurrentes portant le même client_event_id
            // (scénario réaliste : retry réseau en contexte 2G/offline-first).
            try {
                $evenement = EvenementScan::create([
                    'terminal_id' => $terminal->id,
                    'client_event_id' => $event['client_event_id'],
                    'declencheur' => $event['trigger'],
                    'hash_sha256' => $event['sha256'],
                    'niveau_max_atteint' => $event['level_reached'],
                    'regle_yara_correspondante' => $event['yara_rule_matched'] ?? null,
                    'classification' => $event['classification'],
                    'action_prise' => $event['action_taken'],
                    'survenu_le' => $event['occurred_at'],
                ]);

                $idsACreer[] = $evenement->id;
            } catch (QueryException $e) {
                if (! str_contains($e->getMessage(), 'client_event_id')) {
                    throw $e;
                }

                $doublonsConnus++;
            }
        }

        $terminal->update(['derniere_synchro_le' => now()]);

        foreach ($idsACreer as $id) {
            CreerAlerteDepuisScan::dispatch($id);
        }

        // Corrige M4 (revue backend) : 409 si le lot entier était déjà connu,
        // conforme à endpoint-sync-api.yaml. Un lot partiellement dupliqué
        // (cas courant en resynchronisation) reste un 202.
        if (empty($idsACreer) && $doublonsConnus > 0) {
            return response()->json(['message' => 'Tous les événements étaient déjà reçus'], 409);
        }

        return response()->json(status: 202);
    }
}
