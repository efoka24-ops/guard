<?php

namespace App\Modules\Web\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Web\Models\SiteWebSurveille;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** T083 — CRUD sites surveillés, réservé au Command Center (auth:sanctum + scope.organisation). */
class SiteWebController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sites = SiteWebSurveille::parOrganisation($request->user()->organisation_id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($sites->through(fn (SiteWebSurveille $s) => $this->presenter($s)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:255'],
        ]);

        $url = $this->normaliserUrl($data['url']);

        $site = SiteWebSurveille::create([
            'organisation_id' => $request->user()->organisation_id,
            'url' => $url,
            'statut' => 'actif',
        ]);

        return response()->json($this->presenter($site), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $site = SiteWebSurveille::parOrganisation($request->user()->organisation_id)->findOrFail($id);

        return response()->json($this->presenter($site));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $site = SiteWebSurveille::parOrganisation($request->user()->organisation_id)->findOrFail($id);
        $site->update(['statut' => 'supprime']);

        return response()->json(status: 204);
    }

    private function normaliserUrl(string $url): string
    {
        $url = rtrim($url, '/');

        return Str::startsWith($url, ['http://', 'https://']) ? $url : "https://{$url}";
    }

    private function presenter(SiteWebSurveille $site): array
    {
        return [
            'id' => $site->id,
            'url' => $site->url,
            'statut_ssl' => $site->statut_ssl,
            'ssl_expiration_le' => $site->ssl_expiration_le,
            'derniere_verification_le' => $site->derniere_verification_le,
            'statut' => $site->statut,
        ];
    }
}
