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

        // Token d'accès (corrige M3, revue backend) : régénéré à chaque
        // (ré)enregistrement, comme un mot de passe changé — seul son hash
        // est persisté, la valeur en clair n'est renvoyée qu'une fois ici.
        $tokenAcces = Str::random(64);

        $terminal = Terminal::updateOrCreate(
            ['identifiant_appareil' => $data['device_hash']],
            [
                'organisation_id' => $organisation->id,
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
