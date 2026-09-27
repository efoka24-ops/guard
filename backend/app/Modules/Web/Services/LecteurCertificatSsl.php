<?php

namespace App\Modules\Web\Services;

use Carbon\Carbon;

/**
 * T078 — Lecture du certificat TLS d'un hôte via un flux `ssl://` PHP natif
 * (aucune dépendance Composer). Isolé dans son propre service pour rester
 * mockable en test (pas d'appel réseau réel dans la suite PHPUnit).
 */
class LecteurCertificatSsl
{
    /** Retourne la date d'expiration du certificat, ou null si injoignable/absent. */
    public function expirationLe(string $host, int $port = 443): ?Carbon
    {
        $contexte = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $flux = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $contexte
        );

        if (! $flux) {
            return null;
        }

        $params = stream_context_get_params($flux);
        fclose($flux);

        $certResource = $params['options']['ssl']['peer_certificate'] ?? null;

        if (! $certResource) {
            return null;
        }

        $infos = openssl_x509_parse($certResource);

        if (! $infos || ! isset($infos['validTo_time_t'])) {
            return null;
        }

        return Carbon::createFromTimestamp($infos['validTo_time_t']);
    }
}
