<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cloisonnement multi-tenant applicatif (Row Level Security) : refuse toute
 * requête authentifiée dont l'utilisateur n'a pas d'organisation rattachée
 * (sauf admin_guard, qui opère en mode multi-organisations — FR-025), et
 * expose organisation_id() pour que les contrôleurs scopent leurs requêtes.
 */
class ScopeToOrganisation
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if ($utilisateur && $utilisateur->role !== 'admin_guard' && ! $utilisateur->organisation_id) {
            abort(403, 'Aucune organisation rattachée à cet utilisateur.');
        }

        return $next($request);
    }
}
