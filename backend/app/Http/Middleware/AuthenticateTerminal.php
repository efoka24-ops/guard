<?php

namespace App\Http\Middleware;

use App\Modules\Endpoint\Models\Terminal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corrige M3 (revue backend) : authentifie les requêtes terminal (scan-events,
 * signatures/delta) via un token d'accès délivré à l'enrôlement — le
 * `terminal_id` seul n'est pas un secret (visible dans les réponses HTTP et
 * les logs réseau), il ne peut donc pas suffire à prouver l'identité.
 */
class AuthenticateTerminal
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            abort(401, 'Token d\'accès terminal manquant');
        }

        $terminal = Terminal::where('token_acces_hash', hash('sha256', $token))->first();

        if (! $terminal) {
            abort(401, 'Token d\'accès terminal invalide');
        }

        $request->attributes->set('terminal', $terminal);

        return $next($request);
    }
}
