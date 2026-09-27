# Implementation Plan: GUARD WEB (module #2)

**Branch**: `001-guard-platform` | **Date**: 2026-09-27 | **Cadrage**: [guard-web-cadrage.md](./guard-web-cadrage.md)

**Input**: guard-web-cadrage.md (Phase 1, validé) — User Story 3 de spec.md

## Summary

Étendre le backend Laravel 10 existant (déjà en production sur guard.trugroup.cm) d'un module de surveillance de sites web **entièrement passif** : hash de la page d'accueil pour détecter un défacement, contrôle des en-têtes de sécurité HTTP, suivi de l'échéance SSL. Aucun agent terminal, aucun test d'intrusion actif. S'appuie sur le cron `schedule:run` déjà en place (queue `sync` en production, pas de worker long-running requis).

## Technical Context

**Language/Version** : PHP 8.1 (Laravel 10.50.3) — même version que le backend ENDPOINT en production, pas de changement d'environnement.

**Primary Dependencies** : `Illuminate\Http\Client` (Guzzle intégré, déjà disponible) pour les requêtes HTTP sortantes ; aucune nouvelle dépendance Composer nécessaire pour ce périmètre (pas de scanner OWASP tiers, cf. cadrage §2).

**Storage** : MySQL de production existant — 2 nouvelles tables (`sites_web_surveilles`, `evenements_web`), aucun nouveau système de stockage.

**Testing** : PHPUnit, comme ENDPOINT. Les tests HTTP sortants utilisent `Http::fake()` (pas d'appel réseau réel dans la suite de tests, ni vers de vrais sites tiers).

**Target Platform** : identique à ENDPOINT — Laravel 10 / PHP 8.1 sur Camoo (HTTP, pas de SSH), cron cPanel toutes les minutes.

**Performance Goals** : détection de défacement sous 5 min (ping toutes les 2 min, cf. spec.md §56) ; contrôle SSL quotidien ; scan d'en-têtes hebdomadaire.

**Constraints** : **aucun payload actif envoyé à un site tiers** (pas d'injection SQL/XSS — cf. cadrage §2, Principe V) ; charge HTTP sortante à dimensionner (un ping/2 min par site limite le nombre de sites surveillables sur un mutualisé) ; toutes les requêtes sortantes doivent avoir un timeout court (éviter qu'un site lent bloque le lot `queue:work --stop-when-empty --max-time=50`).

**Scale/Scope** : ce module seul (pas de dépendance à SOCIAL/ID/CODE) ; multi-tenant dès le départ (site scopé par `organisation_id`, cf. correctif T053 sur Terminal comme référence à ne pas reproduire en défaut).

## Constitution Check

| Principe | Statut | Justification |
|---|---|---|
| I. Offline-First | N/A | Module 100% backend, pas d'agent terminal — le principe ne s'applique pas à ce module |
| II. Légèreté & Sobriété Ressources | PASS | Aucun nouveau service, requêtes HTTP sortantes avec timeout court, exécuté dans la fenêtre cron existante |
| III. Threat Intelligence Africaine Locale | N/A | Ce module ne consomme pas de base de signatures |
| IV. Confidentialité & Souveraineté des Données | PASS | Aucune nouvelle donnée personnelle collectée ; URL et hash de page uniquement |
| V. Sécurité par Conception | PASS | Périmètre explicitement restreint au passif — aucun test actif sans autorisation écrite (cadrage §2) |
| VI. Explicabilité & Accessibilité des Alertes | PASS | Alerte `module_source = WEB` réutilise le modèle `Alerte` existant (titre clair, SLA) |
| VII. Testabilité Indépendante par Module | PASS | Aucune dépendance aux autres modules, testable isolément via `Http::fake()` |

Aucune violation.

## Project Structure

```text
guard/backend/app/Modules/Web/
├── Models/
│   ├── SiteWebSurveille.php
│   └── EvenementWeb.php
├── Http/Controllers/
│   └── SiteWebController.php       # CRUD sites (Command Center, auth:sanctum)
├── Jobs/
│   ├── VerifierDefacementSite.php   # hash page d'accueil, toutes les 2 min
│   ├── VerifierEnTetesSite.php      # en-têtes sécurité, hebdomadaire
│   └── VerifierSslSite.php          # échéance certificat, quotidien
└── Services/
    └── NormalisateurHtml.php        # retire timestamps/tokens/nonces avant hash

guard/backend/database/migrations/
├── xxxx_create_sites_web_surveilles_table.php
└── xxxx_create_evenements_web_table.php

guard/backend/tests/Feature/Web/
├── SiteWebControllerTest.php
├── VerifierDefacementSiteTest.php
├── VerifierEnTetesSiteTest.php
└── VerifierSslSiteTest.php
```

**Structure Decision** : suit exactement la convention `app/Modules/{Nom}/` déjà en place pour Endpoint (cf. plan.md global §65-120) — cohérence avec le code existant, aucune nouvelle convention introduite.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|---|---|---|
| — | — | — |
