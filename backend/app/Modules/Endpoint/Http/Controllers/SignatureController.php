<?php

namespace App\Modules\Endpoint\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Endpoint\Models\VersionSignatures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** T029 — GET /signatures/delta (contracts/endpoint-sync-api.yaml). */
class SignatureController extends Controller
{
    public function delta(Request $request): JsonResponse|Response
    {
        $fromVersion = $request->query('from_version');

        $versions = VersionSignatures::depuis($fromVersion);

        if ($versions->isEmpty()) {
            return response()->noContent(); // 204 — déjà à jour
        }

        $hashes = $versions->flatMap(fn (VersionSignatures $v) => $v->hashes_ajoutes)->unique()->values();
        $regles = $versions->flatMap(fn (VersionSignatures $v) => $v->regles_yara_ajoutees)->unique()->values();
        $derniere = $versions->last();

        return response()->json([
            'version' => $derniere->numero_version,
            'size_kb' => round((strlen(json_encode($hashes)) + strlen(json_encode($regles))) / 1024, 2),
            'hashes_added' => $hashes,
            'yara_rules_added' => $regles,
            'checksum' => $derniere->checksum_delta,
        ]);
    }
}
