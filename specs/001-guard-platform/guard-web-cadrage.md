# Cadrage — GUARD WEB (module #2)

**Statut** : Draft — Phase 1 (cadrage), à valider avant passage en plan/tasks.
**Base** : User Story 3 du spec.md (§46-59), research.md §5-6 (choix du module #2).

## 1. Pourquoi ce module, et pourquoi maintenant

GUARD ENDPOINT est en production et validé de bout en bout. GUARD WEB a été retenu comme module #2 dès le cadrage initial car :
- **Aucun agent terminal** : tourne entièrement côté backend Laravel déjà déployé (Camoo).
- **Aucune API tierce payante ou à validation longue** (contrairement à SOCIAL/Meta ou ID/threat intel) : ping HTTP, hash de page, TLS, headers — tout est accessible sans compte partenaire.
- **Compatible avec l'hébergement mutualisé actuel** : pas de worker long-running requis, le cron `schedule:run` toutes les minutes suffit (research.md §1).

## 2. Périmètre du premier incrément GUARD WEB

Reprend les 3 scénarios d'acceptation de la User Story 3 (spec.md §54-58) :

1. **Détection de défacement** — hash de référence de la page d'accueil, alerte sous 5 min si le contenu change de façon non autorisée, avec preuve horodatée.
2. **Scan hebdomadaire de vulnérabilités basiques** — pas un scanner OWASP complet (hors périmètre d'un mutualisé sans outillage dédié type ZAP/Nikto) : dans ce premier incrément, on se limite à des vérifications passives et légales sans usage offensif — en-têtes de sécurité manquants (CSP, HSTS, X-Frame-Options), formulaires sans protection CSRF visible, cookies sans `Secure`/`HttpOnly`. **Explicitement hors périmètre** : injection SQL/XSS actives (nécessiterait d'envoyer des payloads d'attaque vers des sites tiers sans autorisation écrite préalable de leur propriétaire — risque légal et éthique, cf. Principe V de la constitution). Ce test actif sera un incrément séparé, réservé aux sites où GUARD a une autorisation explicite de pentest.
3. **Suivi de certificat SSL** — alertes à J-30/J-14/J-7/J-1 avant expiration.

**Hors périmètre de cet incrément** (comme pour ENDPOINT niveau 3) : CVSS 3.1 complet, guide de remédiation adapté par hébergeur détecté, capture d'écran signée RFC 3161 (signature horodatée qualifiée — nécessite une autorité de temps tierce, coût et intégration à évaluer séparément).

## 3. Entité nouvelle : SiteWebSurveille

Référencée mais non modélisée dans data-model.md §151. Proposition minimale pour cet incrément :

| Champ | Type | Notes |
|---|---|---|
| id | uuid | |
| organisation_id | uuid (FK) | cloisonnement multi-tenant, comme Terminal (cf. correctif T053 — l'unicité de l'URL doit être scopée par organisation, pas globale) |
| url | string | normalisée (schéma + host, sans trailing slash) |
| hash_page_accueil | string | SHA-256 du contenu HTML normalisé (hors éléments dynamiques : timestamps, tokens CSRF, nonces) |
| derniere_verification_le | timestamp | |
| statut_ssl | enum(valide, expire_bientot, expire, absent) | |
| ssl_expiration_le | date, nullable | |
| statut | enum(actif, en_pause, supprime) | |
| timestamps | | |

Table `evenements_web` (défacement détecté, en-tête manquant, alerte SSL) suit le même schéma que `evenements_scan` d'ENDPOINT, avec `module_source = WEB` dans `Alerte` (déjà prévu dans data-model.md §116).

## 4. Job planifié

`Schedule::command('web:verifier-sites')->everyTwoMinutes()` pour le hash de page (spec.md exige une détection sous 5 min), `->weekly()` pour le scan d'en-têtes, `->daily()` pour le contrôle SSL — tous exécutés dans la fenêtre `queue:work --stop-when-empty --max-time=50` du cron existant (aucun nouveau mécanisme d'ordonnancement à ajouter).

**Attention charge** : sur un hébergement mutualisé, un ping toutes les 2 minutes par site limite le nombre de sites surveillables simultanément. À dimensionner selon le nombre de clients réels avant de committer sur ce SLA de 5 minutes dans un contrat client.

## 5. Prochaine étape

Une fois ce cadrage validé : dérouler `/plan` puis `/tasks` comme pour ENDPOINT (contrats d'API si un endpoint Command Center expose SiteWebSurveille, migrations, tests, implémentation). Peut démarrer sans dépendance à SOCIAL/ID/CODE.
