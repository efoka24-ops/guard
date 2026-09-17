<?php

namespace App\Modules\Endpoint\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Modules\Endpoint\Models\Terminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $terminal = Terminal::updateOrCreate(
            ['identifiant_appareil' => $data['device_hash']],
            [
                'organisation_id' => $organisation->id,
                'plateforme' => $data['platform'],
                'version_app' => $data['app_version'],
                'statut' => 'actif',
            ]
        );

        return response()->json([
            'id' => $terminal->id,
            'platform' => $terminal->plateforme,
            'signature_version' => $terminal->version_signatures,
            'status' => $terminal->statut,
        ], 201);
    }
}
