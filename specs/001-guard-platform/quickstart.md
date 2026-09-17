# Quickstart — GUARD Platform (premier incrément : Backend + GUARD ENDPOINT niveaux 1-2)

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Data model**: [data-model.md](./data-model.md) | **Contrats**: [contracts/endpoint-sync-api.yaml](./contracts/endpoint-sync-api.yaml)

Ce guide couvre la mise en route locale du **backend Laravel** (API de synchronisation + Command Center minimal) et du **socle de l'agent GUARD ENDPOINT** (niveaux 1-2 : hash SHA-256 + YARA). Le niveau 3 (IA TFLite) et les modules SOCIAL/WEB/ID/CODE sont hors périmètre de ce premier incrément (cf. research.md §5).

## 1. Pré-requis locaux

- **PHP 8.2+ et Composer** (révisé : Laravel 10/PHP 8.1 abandonnés le 2026-09-17, cf. research.md §4bis — Laravel 12 requiert PHP ≥ 8.2)
- SQLite (par défaut, généré automatiquement par `laravel/laravel` — suffisant pour le développement local de ce premier incrément) ou MySQL 8.0 (accès à la base distante `trugro9159_guard` via `pma-12.camoo.net`, réservé aux tests pré-prod, jamais pour le dev quotidien)
- Node.js LTS (pour le dashboard, phase suivante)
- Pas besoin de Redis pour ce premier incrément (driver `database`, cf. research.md §2 — c'est déjà le défaut de `laravel/laravel` 12.x, aucune configuration manuelle requise)

## 2. Configuration

```bash
cd guard
cp .env.example .env
# Renseigner DB_* avec vos identifiants (voir .env local déjà présent pour la base de prod Camoo — usage prod uniquement, ne pas pointer le dev dessus)
```

Variables clés à vérifier dans `.env` pour ce périmètre :

```env
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

## 3. Backend Laravel (déjà créé — `guard/backend/`, Laravel 12.69.2)

Réalisé (T001-T004 de tasks.md) :

```bash
composer create-project laravel/laravel backend "^12.0"   # PHP 8.2+ requis
cd backend
cp .env.example .env && php artisan key:generate
php artisan install:api        # Sanctum (JWT) — Principe V
php artisan migrate            # tables users/cache/jobs/personal_access_tokens déjà en place
vendor/bin/pint                # linting — déjà configuré et passant
```

`QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database` et `DB_CONNECTION=sqlite` sont déjà les valeurs par défaut de `laravel/laravel` 12.x — aucune modification manuelle n'a été nécessaire (cf. research.md §2).

Reste à faire (T007 et suivants, Phase 2 Foundational) :

Modules à créer selon `plan.md` (`app/Modules/Endpoint/`, `app/Services/CommandCenter/`) : migrations pour `organisations`, `utilisateurs`, `terminaux`, `evenements_scan`, `elements_quarantaine`, `versions_signatures`, `alertes`, `incidents` (cf. data-model.md).

Implémenter les endpoints de `contracts/endpoint-sync-api.yaml` :
- `POST /api/v1/terminals/register`
- `GET /api/v1/signatures/delta`
- `POST /api/v1/scan-events`
- `GET /api/v1/alerts`
- `POST /api/v1/alerts/{id}/acknowledge`

```bash
php artisan schedule:work   # simule le cron cPanel en local (schedule:run chaque minute)
php artisan serve
```

## 4. Base de signatures initiale (réalisé — T034-T036)

Le premier incrément ne couvre que les niveaux 1 (hash) et 2 (YARA) :

1. `guard/signatures/test/hashes.json` — hash SHA-256 du fichier EICAR standard : `275a021bbfb6489e54d471899f7db9d1663fc695ec2fe2a2c4538aabf651fd0f` (64 caractères — attention à ne pas tronquer une chaîne trouvée en ligne, une version à 63 caractères circule).
2. `guard/signatures/test/rules/*.yar` — 2 règles YARA de test (chaîne EICAR + marqueur générique).
3. `php artisan signatures:publish` (`guard/backend/app/Console/Commands/PublishSignatures.php`) publie un `VersionSignatures` à partir de ces fichiers ; ré-exécutable pour publier des deltas incrémentaux.

## 5. Agent GUARD ENDPOINT (squelette — `guard/mobile-android/` ou `guard/desktop-windows/`)

Pour ce premier incrément, l'agent doit uniquement :
1. Détecter la connexion d'un support externe (USB) ou une nouvelle installation d'APK.
2. Calculer le hash SHA-256 de chaque fichier détecté → comparer à la base SQLite locale (niveau 1).
3. Si non trouvé : appliquer les règles YARA locales (niveau 2).
4. Générer un `ScanEvent` local, l'ajouter à une file d'envoi différée (`client_event_id` en UUID pour idempotence).
5. Synchroniser vers `POST /scan-events` dès qu'une connexion est disponible ; tenter `GET /signatures/delta` une fois par jour ou au démarrage.

Test de validation minimal : brancher un support contenant un fichier EICAR → vérifier la quarantaine locale + l'apparition du `ScanEvent` côté backend après synchronisation (avec et sans coupure réseau simulée entre les deux étapes).

## 6. Vérification du parcours de bout en bout (P1, User Story 1 du cadrage)

```text
1. Terminal hors ligne détecte fichier EICAR sur USB → quarantaine locale immédiate (< 1s, SC-001)
2. Reconnexion réseau (même après plusieurs jours, cf. Terminal.derniere_synchro_le nullable)
3. POST /scan-events → 202 Accepted
4. Backend crée une Alerte (module_source=ENDPOINT, niveau_criticite=critique)
5. GET /alerts (Command Center) → l'alerte apparaît avec sla_due_at ≤ 24h
6. POST /alerts/{id}/acknowledge → statut = accusee_reception
```

## 7. Hors périmètre explicite de ce quickstart

- Déploiement sur l'hébergement Camoo (`/home/trugro9159/guard`) : à traiter une fois le backend validé en local, en tenant compte des contraintes de research.md §1 (confirmer PHP 8.1, Redis, cron avec le support avant bascule prod).
- Niveau 3 IA TFLite, dashboard React complet, modules SOCIAL/WEB/ID/CODE : specs et quickstarts dérivés à produire lors de leur priorisation.
