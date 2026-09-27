<?php

namespace App\Modules\Endpoint\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Modules\Endpoint\Models\Terminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** T028 — POST /terminals/register (contracts/endpoint-sync-api.yaml). */
class TerminalController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_hash' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:android,windows'],
            'app_version' => ['required', 'string', 'max:32'],
            'organisation_token' => ['required', 'string'],
        ]);

        $organisation = Organisation::where('token_enrolement', $data['organisation_token'])->first();

        if (! $organisation) {
            return response()->json(['message' => 'organisation_token invalide'], 401);
        }

        // Corrige la faille de cloisonnement multi-tenant relevée en
        // durcissement (T053) : identifiant_appareil n'est plus une clé
        // globale suffisante pour l'updateOrCreate — un device_hash déjà
        // enregistré par une AUTRE organisation ne doit jamais être
        // réassigné silencieusement (ça révoquait le token de la
        // première organisation et exposait son historique de scan).
        $conflit = Terminal::where('identifiant_appareil', $data['device_hash'])
            ->where('organisation_id', '!=', $organisation->id)
            ->exists();

        if ($conflit) {
            return response()->json(['message' => 'device_hash déjà enregistré sous une autre organisation'], 409);
        }

        // Token d'accès (corrige M3, revue backend) : régénéré à chaque
        // (ré)enregistrement, comme un mot de passe changé — seul son hash
        // est persisté, la valeur en clair n'est renvoyée qu'une fois ici.
        $tokenAcces = Str::random(64);

        $terminal = Terminal::updateOrCreate(
            [
                'organisation_id' => $organisation->id,
                'identifiant_appareil' => $data['device_hash'],
            ],
            [
                'plateforme' => $data['platform'],
                'version_app' => $data['app_version'],
                'statut' => 'actif',
                'token_acces_hash' => hash('sha256', $tokenAcces),
            ]
        );

        return response()->json([
            'id' => $terminal->id,
            'platform' => $terminal->plateforme,
            'signature_version' => $terminal->version_signatures,
            'status' => $terminal->statut,
            'access_token' => $tokenAcces,
        ], 201);
    }
}
