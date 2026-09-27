<?php

namespace App\Modules\Web\Services;

/**
 * T064 — Retire les éléments dynamiques d'une page HTML avant hachage, pour
 * éviter les faux positifs de défacement sur un site dont le contenu
 * "métier" n'a pas changé (token CSRF régénéré à chaque requête, timestamp
 * affiché en pied de page, nonce CSP par requête).
 */
class NormalisateurHtml
{
    private const PATRONS = [
        // value="..." des champs nommés _token, csrf-token, csrf_token (insensible à la casse)
        '/(name=["\'](?:_token|csrf[-_]token)["\']\s+value=["\'])[^"\']*(["\'])/i',
        '/(value=["\'][^"\']*["\']\s+name=["\'](?:_token|csrf[-_]token)["\'])/i',
        // attribut nonce="..." (CSP)
        '/nonce=["\'][^"\']*["\']/i',
        // dates ISO 8601 (ex. horodatage de génération en pied de page)
        '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})?/',
    ];

    public function normaliser(string $html): string
    {
        $resultat = preg_replace(self::PATRONS, '', $html);

        return $resultat ?? $html;
    }

    public function hash(string $html): string
    {
        return hash('sha256', $this->normaliser($html));
    }
}
