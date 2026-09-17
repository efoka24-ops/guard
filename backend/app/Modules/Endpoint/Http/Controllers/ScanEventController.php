<?php

namespace App\Modules\Endpoint\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Endpoint\Jobs\CreerAlerteDepuisScan;
use App\Modules\Endpoint\Models\EvenementScan;
use App\Modules\Endpoint\Models\Terminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** T030 — POST /scan-events, batch idempotent (contracts/endpoint-sync-api.yaml). */
class ScanEventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
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

        $terminal = Terminal::findOrFail($data['terminal_id']);
        $idsACreer = [];

        DB::transaction(function () use ($data, $terminal, &$idsACreer) {
            foreach ($data['events'] as $event) {
                // Idempotence : un scan-event déjà connu (client_event_id) n'est pas recréé,
                // permet la resynchronisation sûre après une coupure réseau.
                if (EvenementScan::where('client_event_id', $event['client_event_id'])->exists()) {
                    continue;
                }

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
            }

            $terminal->update(['derniere_synchro_le' => now()]);
        });

        foreach ($idsACreer as $id) {
            CreerAlerteDepuisScan::dispatch($id);
        }

        return response()->json(status: 202);
    }
}
